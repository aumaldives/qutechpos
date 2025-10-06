<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use App\Transaction;
use App\TransactionSellLine;
use App\Contact;
use App\Product;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use Carbon\Carbon;
use Exception;

class BidirectionalSyncManager
{
    /**
     * Process WooCommerce order webhook for bidirectional sync
     */
    public static function processOrderWebhook(array $webhookData, int $businessId, int $locationId = null): array
    {
        try {
            $eventType = $webhookData['event_type'] ?? 'order.updated';
            $orderData = $webhookData['order'] ?? $webhookData;
            $orderId = $orderData['id'] ?? null;

            if (!$orderId) {
                throw new Exception('Order ID not found in webhook data');
            }

            // Log the webhook event
            $eventId = self::logWebhookEvent($businessId, $locationId, $eventType, $orderId, $webhookData);

            // Find the appropriate location setting
            $locationSetting = self::findLocationSettingForOrder($businessId, $locationId, $orderData);
            
            if (!$locationSetting || !$locationSetting->enable_bidirectional_sync) {
                return [
                    'success' => false,
                    'message' => 'Bidirectional sync not enabled for this location',
                    'event_id' => $eventId
                ];
            }

            // Get status mapping configuration
            $statusMapping = $locationSetting->order_status_mapping ?? self::getDefaultStatusMapping();
            $wooStatus = $orderData['status'] ?? 'pending';
            $targetPosInvoiceType = $statusMapping[$wooStatus] ?? 'draft';

            Log::info('Processing order webhook', [
                'business_id' => $businessId,
                'location_id' => $locationSetting->location_id,
                'woo_order_id' => $orderId,
                'woo_status' => $wooStatus,
                'target_pos_type' => $targetPosInvoiceType,
                'event_type' => $eventType
            ]);

            // Check if POS transaction already exists
            $existingTransaction = Transaction::where('business_id', $businessId)
                                             ->where('woocommerce_order_id', $orderId)
                                             ->first();

            $result = [];

            if ($existingTransaction) {
                // Update existing POS transaction
                $result = self::updateExistingPosTransaction(
                    $existingTransaction, 
                    $targetPosInvoiceType, 
                    $orderData,
                    $locationSetting
                );
            } else {
                // Create new POS transaction if configured to do so
                if ($locationSetting->create_draft_on_webhook) {
                    $result = self::createPosTransactionFromWebhook(
                        $orderData,
                        $businessId,
                        $locationSetting->location_id,
                        $targetPosInvoiceType
                    );
                } else {
                    $result = [
                        'success' => true,
                        'action' => 'skipped',
                        'message' => 'Auto-creation of POS transactions disabled'
                    ];
                }
            }

            // Update webhook event log
            self::updateWebhookEventResult($eventId, 'completed', $result);

            return array_merge($result, ['event_id' => $eventId]);

        } catch (Exception $e) {
            Log::error('Failed to process order webhook', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);

            // Update webhook event log with error
            if (isset($eventId)) {
                self::updateWebhookEventResult($eventId, 'failed', [
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'event_id' => $eventId ?? null
            ];
        }
    }

    /**
     * Sync POS transaction status back to WooCommerce
     */
    public static function syncPosTransactionToWooCommerce(Transaction $transaction): array
    {
        try {
            if (!$transaction->woocommerce_order_id) {
                return [
                    'success' => false,
                    'message' => 'Transaction not linked to WooCommerce order'
                ];
            }

            // Get location setting
            $locationSetting = WoocommerceLocationSetting::where('business_id', $transaction->business_id)
                                                        ->where('location_id', $transaction->location_id)
                                                        ->first();

            if (!$locationSetting || !$locationSetting->auto_update_woo_status || !$locationSetting->enable_bidirectional_sync) {
                return [
                    'success' => false,
                    'message' => 'Auto-update to WooCommerce disabled'
                ];
            }

            // Determine WooCommerce status from POS transaction status
            $wooStatus = self::mapPosStatusToWooCommerce($transaction, $locationSetting);
            
            if (!$wooStatus) {
                return [
                    'success' => false,
                    'message' => 'No WooCommerce status mapping for current POS status'
                ];
            }

            // Get WooCommerce client
            $client = $locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client');
            }

            // Update order status in WooCommerce
            $updateData = [
                'status' => $wooStatus,
                'meta_data' => [
                    [
                        'key' => '_pos_sync_timestamp',
                        'value' => now()->toISOString()
                    ],
                    [
                        'key' => '_pos_transaction_id',
                        'value' => $transaction->id
                    ]
                ]
            ];

            $response = $client->put('orders/' . $transaction->woocommerce_order_id, $updateData);

            Log::info('POS transaction status synced to WooCommerce', [
                'business_id' => $transaction->business_id,
                'pos_transaction_id' => $transaction->id,
                'woo_order_id' => $transaction->woocommerce_order_id,
                'new_woo_status' => $wooStatus,
                'pos_status' => $transaction->status
            ]);

            return [
                'success' => true,
                'action' => 'status_updated',
                'woocommerce_status' => $wooStatus,
                'pos_status' => $transaction->status
            ];

        } catch (Exception $e) {
            Log::error('Failed to sync POS transaction to WooCommerce', [
                'transaction_id' => $transaction->id,
                'woo_order_id' => $transaction->woocommerce_order_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update existing POS transaction based on WooCommerce status change
     */
    private static function updateExistingPosTransaction(
        Transaction $transaction, 
        string $targetPosInvoiceType, 
        array $orderData,
        WoocommerceLocationSetting $locationSetting
    ): array {
        try {
            $originalStatus = $transaction->status;
            $transactionUtil = new TransactionUtil();

            Log::info('Updating existing POS transaction', [
                'transaction_id' => $transaction->id,
                'original_status' => $originalStatus,
                'target_type' => $targetPosInvoiceType,
                'woo_order_id' => $transaction->woocommerce_order_id
            ]);

            switch ($targetPosInvoiceType) {
                case 'draft':
                    // Keep as draft or revert to draft
                    if ($transaction->status !== 'draft') {
                        // Note: Reverting finalized transactions is complex and may not be advisable
                        Log::warning('Cannot revert finalized transaction to draft', [
                            'transaction_id' => $transaction->id,
                            'current_status' => $transaction->status
                        ]);
                        
                        return [
                            'success' => false,
                            'action' => 'revert_not_supported',
                            'message' => 'Cannot revert finalized transaction to draft'
                        ];
                    }
                    break;

                case 'proforma':
                    // Convert to proforma if currently draft
                    if ($transaction->status === 'draft') {
                        // Update transaction type but don't finalize
                        $transaction->update([
                            'invoice_no' => $transaction->invoice_no ?: $transactionUtil->getInvoiceNumber($transaction->business_id, $transaction->status, $transaction->location_id),
                            'status' => 'proforma'
                        ]);
                        
                        return [
                            'success' => true,
                            'action' => 'converted_to_proforma',
                            'message' => 'Transaction converted to proforma invoice'
                        ];
                    }
                    break;

                case 'final':
                    // Finalize the transaction if not already final
                    if (in_array($transaction->status, ['draft', 'proforma'])) {
                        
                        if ($locationSetting->auto_finalize_pos_sales) {
                            // Finalize the transaction
                            $finalizeResult = self::finalizeTransaction($transaction, $transactionUtil);
                            
                            if ($finalizeResult['success']) {
                                return [
                                    'success' => true,
                                    'action' => 'finalized',
                                    'message' => 'POS sale automatically finalized from WooCommerce status',
                                    'original_status' => $originalStatus,
                                    'new_status' => 'final'
                                ];
                            } else {
                                return $finalizeResult;
                            }
                        } else {
                            return [
                                'success' => false,
                                'action' => 'auto_finalize_disabled',
                                'message' => 'Auto-finalization disabled for this location'
                            ];
                        }
                    } elseif ($transaction->status === 'final') {
                        return [
                            'success' => true,
                            'action' => 'already_finalized',
                            'message' => 'Transaction already finalized'
                        ];
                    }
                    break;

                case 'cancelled':
                    // Cancel the transaction
                    $transaction->update(['status' => 'cancelled']);
                    return [
                        'success' => true,
                        'action' => 'cancelled',
                        'message' => 'Transaction cancelled'
                    ];

                case 'refunded':
                    // Create refund (this would require more complex logic)
                    return [
                        'success' => false,
                        'action' => 'refund_not_implemented',
                        'message' => 'Refund processing not yet implemented'
                    ];
            }

            return [
                'success' => true,
                'action' => 'no_change_needed',
                'message' => 'No status change required'
            ];

        } catch (Exception $e) {
            Log::error('Failed to update existing POS transaction', [
                'transaction_id' => $transaction->id,
                'target_type' => $targetPosInvoiceType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new POS transaction from webhook
     */
    private static function createPosTransactionFromWebhook(
        array $orderData, 
        int $businessId, 
        int $locationId, 
        string $invoiceType
    ): array {
        try {
            $transactionUtil = new TransactionUtil();

            // Create or get customer
            $customer = self::createOrGetCustomerFromOrder($orderData, $businessId);

            // Prepare transaction data
            $transactionData = [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'type' => 'sell',
                'status' => $invoiceType === 'final' ? 'final' : 'draft',
                'contact_id' => $customer->id,
                'transaction_date' => Carbon::parse($orderData['date_created'] ?? now())->format('Y-m-d'),
                'woocommerce_order_id' => $orderData['id'],
                'total_before_tax' => $orderData['total'] ?? 0,
                'tax_amount' => $orderData['total_tax'] ?? 0,
                'final_total' => $orderData['total'] ?? 0,
                'payment_status' => self::mapWooPaymentStatus($orderData['status']),
                'created_by' => 1, // System user
            ];

            // Create the transaction
            $transaction = Transaction::create($transactionData);

            // Add line items
            self::addLineItemsFromOrder($transaction, $orderData, $businessId);

            Log::info('Created POS transaction from webhook', [
                'transaction_id' => $transaction->id,
                'woo_order_id' => $orderData['id'],
                'invoice_type' => $invoiceType,
                'customer_id' => $customer->id
            ]);

            return [
                'success' => true,
                'action' => 'created',
                'transaction_id' => $transaction->id,
                'invoice_type' => $invoiceType,
                'message' => 'POS transaction created from WooCommerce order'
            ];

        } catch (Exception $e) {
            Log::error('Failed to create POS transaction from webhook', [
                'woo_order_id' => $orderData['id'] ?? 'unknown',
                'business_id' => $businessId,
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Finalize a POS transaction
     */
    private static function finalizeTransaction(Transaction $transaction, TransactionUtil $transactionUtil): array
    {
        try {
            DB::beginTransaction();

            // Update transaction status to final
            $transaction->update([
                'status' => 'final',
                'invoice_no' => $transaction->invoice_no ?: $transactionUtil->getInvoiceNumber(
                    $transaction->business_id, 
                    'final', 
                    $transaction->location_id
                ),
                'finalized_at' => now()
            ]);

            // Update stock levels for each line item
            $sellLines = $transaction->sell_lines;
            foreach ($sellLines as $sellLine) {
                if ($sellLine->product && $sellLine->variation) {
                    $transactionUtil->decreaseProductQuantity(
                        $sellLine->product_id,
                        $sellLine->variation_id,
                        $transaction->location_id,
                        $sellLine->quantity
                    );
                }
            }

            DB::commit();

            Log::info('Transaction finalized successfully', [
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->invoice_no
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to finalize transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create or get customer from order data
     */
    private static function createOrGetCustomerFromOrder(array $orderData, int $businessId): Contact
    {
        $email = $orderData['billing']['email'] ?? null;
        $customerId = $orderData['customer_id'] ?? null;

        // Try to find existing customer
        if ($customerId) {
            $customer = Contact::where('business_id', $businessId)
                              ->where('woocommerce_cust_id', $customerId)
                              ->first();
            if ($customer) {
                return $customer;
            }
        }

        if ($email) {
            $customer = Contact::where('business_id', $businessId)
                              ->where('email', $email)
                              ->first();
            if ($customer) {
                // Link to WooCommerce customer ID if not already linked
                if (!$customer->woocommerce_cust_id && $customerId) {
                    $customer->update(['woocommerce_cust_id' => $customerId]);
                }
                return $customer;
            }
        }

        // Create new customer
        $customerData = [
            'business_id' => $businessId,
            'type' => 'customer',
            'name' => trim(($orderData['billing']['first_name'] ?? '') . ' ' . ($orderData['billing']['last_name'] ?? '')),
            'email' => $email,
            'mobile' => $orderData['billing']['phone'] ?? null,
            'woocommerce_cust_id' => $customerId,
            'created_by' => 1
        ];

        return Contact::create($customerData);
    }

    /**
     * Add line items from WooCommerce order
     */
    private static function addLineItemsFromOrder(Transaction $transaction, array $orderData, int $businessId): void
    {
        $lineItems = $orderData['line_items'] ?? [];

        foreach ($lineItems as $item) {
            $productId = null;
            $variationId = null;

            // Find POS product by WooCommerce product ID
            if (isset($item['product_id'])) {
                $product = Product::where('business_id', $businessId)
                                 ->where('woocommerce_product_id', $item['product_id'])
                                 ->first();

                if ($product) {
                    $productId = $product->id;

                    // Find variation if this is a variable product
                    if (isset($item['variation_id']) && $item['variation_id'] > 0) {
                        $variation = $product->variations()
                                           ->where('woocommerce_variation_id', $item['variation_id'])
                                           ->first();
                        $variationId = $variation ? $variation->id : $product->variations->first()->id;
                    } else {
                        $variationId = $product->variations->first()->id ?? null;
                    }
                }
            }

            // Create sell line
            TransactionSellLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'quantity' => $item['quantity'] ?? 1,
                'unit_price_before_discount' => $item['price'] ?? 0,
                'unit_price' => $item['price'] ?? 0,
                'line_discount_type' => 'fixed',
                'line_discount_amount' => 0,
                'unit_price_inc_tax' => $item['price'] ?? 0,
                'item_tax' => $item['total_tax'] ?? 0,
                'woocommerce_line_item_id' => $item['id'] ?? null
            ]);
        }
    }

    /**
     * Map WooCommerce payment status to POS payment status
     */
    private static function mapWooPaymentStatus(string $orderStatus): string
    {
        $mapping = [
            'pending' => 'due',
            'on-hold' => 'due',
            'processing' => 'paid',
            'completed' => 'paid',
            'cancelled' => 'due',
            'refunded' => 'due',
            'failed' => 'due'
        ];

        return $mapping[$orderStatus] ?? 'due';
    }

    /**
     * Map POS transaction status to WooCommerce order status
     */
    private static function mapPosStatusToWooCommerce(Transaction $transaction, WoocommerceLocationSetting $locationSetting): ?string
    {
        // Get reverse mapping from location settings
        $statusMapping = $locationSetting->order_status_mapping ?? self::getDefaultStatusMapping();
        $reverseMapping = array_flip($statusMapping);

        // Determine POS invoice type
        $posType = match($transaction->status) {
            'draft' => 'draft',
            'proforma' => 'proforma', 
            'final' => 'final',
            'cancelled' => 'cancelled',
            default => 'draft'
        };

        return $reverseMapping[$posType] ?? null;
    }

    /**
     * Find appropriate location setting for an order
     */
    private static function findLocationSettingForOrder(int $businessId, ?int $locationId, array $orderData): ?WoocommerceLocationSetting
    {
        if ($locationId) {
            return WoocommerceLocationSetting::where('business_id', $businessId)
                                           ->where('location_id', $locationId)
                                           ->first();
        }

        // If no specific location, try to find the first active one
        return WoocommerceLocationSetting::where('business_id', $businessId)
                                        ->where('is_active', true)
                                        ->first();
    }

    /**
     * Log webhook event
     */
    private static function logWebhookEvent(int $businessId, ?int $locationId, string $eventType, string $orderId, array $webhookData): int
    {
        return DB::table('woocommerce_webhook_events')->insertGetId([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'event_type' => $eventType,
            'woocommerce_order_id' => $orderId,
            'status' => 'processing',
            'webhook_payload' => json_encode($webhookData),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Update webhook event result
     */
    private static function updateWebhookEventResult(int $eventId, string $status, array $result): void
    {
        DB::table('woocommerce_webhook_events')
          ->where('id', $eventId)
          ->update([
              'status' => $status,
              'processing_result' => json_encode($result),
              'error_message' => $result['error'] ?? null,
              'processed_at' => now(),
              'updated_at' => now()
          ]);
    }

    /**
     * Get default status mapping
     */
    private static function getDefaultStatusMapping(): array
    {
        return [
            'pending' => 'draft',
            'on-hold' => 'draft', 
            'processing' => 'proforma',
            'completed' => 'final',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'failed' => 'draft'
        ];
    }
}