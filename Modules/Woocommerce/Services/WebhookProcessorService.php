<?php

namespace Modules\Woocommerce\Services;

use App\Business;
use App\Transaction;
use Illuminate\Support\Facades\Log;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Services\ConflictResolutionService;
use Modules\Woocommerce\Services\SyncMonitoringService;
use Exception;

class WebhookProcessorService
{
    protected WoocommerceUtil $woocommerceUtil;
    protected ConflictResolutionService $conflictService;
    protected SyncMonitoringService $monitoringService;

    public function __construct(
        WoocommerceUtil $woocommerceUtil,
        ConflictResolutionService $conflictService,
        SyncMonitoringService $monitoringService
    ) {
        $this->woocommerceUtil = $woocommerceUtil;
        $this->conflictService = $conflictService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Process webhook with monitoring integration
     */
    public function processWebhook(
        string $business_id,
        string $event,
        array $payload,
        array $metadata = []
    ): array {
        $startTime = microtime(true);
        
        Log::info('Processing webhook with monitoring', [
            'business_id' => $business_id,
            'event' => $event,
            'payload_id' => $payload['id'] ?? 'unknown',
            'webhook_id' => $metadata['webhook_id'] ?? 'unknown'
        ]);

        try {
            // Log webhook received
            $this->monitoringService->logWebhookReceived($business_id, $event, $payload);

            $result = match($event) {
                'order.created' => $this->processOrderCreated($business_id, $payload, $metadata),
                'order.updated' => $this->processOrderUpdated($business_id, $payload, $metadata),
                'order.deleted' => $this->processOrderDeleted($business_id, $payload, $metadata),
                'order.restored' => $this->processOrderRestored($business_id, $payload, $metadata),
                'product.created' => $this->processProductCreated($business_id, $payload, $metadata),
                'product.updated' => $this->processProductUpdated($business_id, $payload, $metadata),
                'product.deleted' => $this->processProductDeleted($business_id, $payload, $metadata),
                'customer.created' => $this->processCustomerCreated($business_id, $payload, $metadata),
                'customer.updated' => $this->processCustomerUpdated($business_id, $payload, $metadata),
                'customer.deleted' => $this->processCustomerDeleted($business_id, $payload, $metadata),
                default => throw new Exception("Unsupported webhook event: {$event}")
            };

            $processingTime = (microtime(true) - $startTime) * 1000;

            // Log successful webhook processing
            $this->monitoringService->logWebhookProcessed(
                $business_id, 
                $event, 
                $payload, 
                $result, 
                $processingTime
            );

            Log::info('Webhook processed successfully with monitoring', [
                'business_id' => $business_id,
                'event' => $event,
                'result' => $result,
                'processing_time_ms' => $processingTime
            ]);

            return $result;

        } catch (Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            // Log webhook error
            $this->monitoringService->logWebhookError(
                $business_id, 
                $event, 
                $payload, 
                $e->getMessage(), 
                $processingTime
            );

            Log::error('Webhook processing failed', [
                'business_id' => $business_id,
                'event' => $event,
                'payload_id' => $payload['id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'processing_time_ms' => $processingTime
            ]);
            
            throw $e;
        }
    }

    /**
     * Process order created webhook with conflict detection
     */
    protected function processOrderCreated(string $business_id, array $payload, array $metadata): array
    {
        try {
            $business = Business::findOrFail($business_id);
            $woocommerce_api_settings = $this->woocommerceUtil->get_api_settings($business_id);
            
            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $woocommerce_api_settings->location_id,
                'business' => $business,
            ];

            $user_id = $business->owner->id;
            
            // Check if order already exists
            $existing_order = Transaction::where('business_id', $business_id)
                ->where('woocommerce_order_id', $payload['id'])
                ->first();
                
            if ($existing_order) {
                // Log potential conflict
                $this->conflictService->logWebhookConflict(
                    $business_id,
                    'order',
                    $payload['id'],
                    'duplicate_creation_attempt',
                    [
                        'existing_transaction_id' => $existing_order->id,
                        'webhook_payload' => $payload
                    ]
                );

                Log::info('Order already exists, skipping creation', [
                    'woocommerce_order_id' => $payload['id'],
                    'existing_transaction_id' => $existing_order->id
                ]);
                
                return ['status' => 'skipped', 'reason' => 'order_already_exists'];
            }

            $created = $this->woocommerceUtil->createNewSaleFromOrder(
                $business_id,
                $user_id,
                (object) $payload,
                $business_data
            );

            if ($created === true) {
                // Log successful creation
                $this->woocommerceUtil->createSyncLog(
                    $business_id,
                    $user_id,
                    'orders',
                    'created',
                    [$payload['number'] ?? $payload['id']]
                );
                
                return ['status' => 'created', 'order_id' => $payload['id']];
            } else {
                // Log creation with errors and create conflict record
                $errors = is_array($created) ? $created : [$created];
                
                $this->conflictService->logWebhookConflict(
                    $business_id,
                    'order',
                    $payload['id'],
                    'creation_errors',
                    [
                        'errors' => $errors,
                        'webhook_payload' => $payload
                    ]
                );

                $this->woocommerceUtil->createSyncLog(
                    $business_id,
                    $user_id,
                    'orders',
                    'created',
                    [$payload['number'] ?? $payload['id']],
                    $errors
                );
                
                return ['status' => 'created_with_errors', 'errors' => $errors];
            }

        } catch (Exception $e) {
            // Log critical error
            $this->conflictService->logWebhookConflict(
                $business_id,
                'order',
                $payload['id'],
                'creation_failed',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            Log::error('Order creation from webhook failed', [
                'business_id' => $business_id,
                'order_id' => $payload['id'],
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process order updated webhook with conflict detection
     */
    protected function processOrderUpdated(string $business_id, array $payload, array $metadata): array
    {
        try {
            $business = Business::findOrFail($business_id);
            $woocommerce_api_settings = $this->woocommerceUtil->get_api_settings($business_id);
            
            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $woocommerce_api_settings->location_id,
            ];

            $user_id = $business->owner->id;
            
            // Find existing order
            $sell = Transaction::where('business_id', $business_id)
                ->where('woocommerce_order_id', $payload['id'])
                ->with('sell_lines', 'sell_lines.product', 'payment_lines')
                ->first();

            if (!$sell) {
                Log::warning('Order not found for update, attempting to create', [
                    'woocommerce_order_id' => $payload['id']
                ]);
                return $this->processOrderCreated($business_id, $payload, $metadata);
            }

            // Check for potential conflicts before updating
            $conflictData = $this->conflictService->detectOrderConflicts(
                $sell,
                (object) $payload
            );

            if (!empty($conflictData)) {
                $this->conflictService->logWebhookConflict(
                    $business_id,
                    'order',
                    $payload['id'],
                    'update_conflicts',
                    [
                        'conflicts' => $conflictData,
                        'webhook_payload' => $payload
                    ]
                );
            }

            $updated = $this->woocommerceUtil->updateSaleFromOrder(
                $business_id,
                $user_id,
                (object) $payload,
                $sell,
                $business_data
            );

            if ($updated === true) {
                // Log successful update
                $this->woocommerceUtil->createSyncLog(
                    $business_id,
                    $user_id,
                    'orders',
                    'updated',
                    [$payload['number'] ?? $payload['id']]
                );
                
                return ['status' => 'updated', 'order_id' => $payload['id']];
            } else {
                // Log update with errors
                $errors = is_array($updated) ? $updated : [$updated];
                
                $this->conflictService->logWebhookConflict(
                    $business_id,
                    'order',
                    $payload['id'],
                    'update_errors',
                    [
                        'errors' => $errors,
                        'webhook_payload' => $payload
                    ]
                );

                $this->woocommerceUtil->createSyncLog(
                    $business_id,
                    $user_id,
                    'orders',
                    'updated',
                    [$payload['number'] ?? $payload['id']],
                    $errors
                );
                
                return ['status' => 'updated_with_errors', 'errors' => $errors];
            }

        } catch (Exception $e) {
            Log::error('Order update from webhook failed', [
                'business_id' => $business_id,
                'order_id' => $payload['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process order deleted webhook
     */
    protected function processOrderDeleted(string $business_id, array $payload, array $metadata): array
    {
        try {
            $transaction = Transaction::where('business_id', $business_id)
                ->where('woocommerce_order_id', $payload['id'])
                ->with('sell_lines')
                ->first();

            if (!$transaction) {
                Log::info('Order not found for deletion', [
                    'woocommerce_order_id' => $payload['id']
                ]);
                return ['status' => 'not_found', 'order_id' => $payload['id']];
            }

            $business = Business::findOrFail($business_id);
            $user_id = $business->owner->id;
            
            // Set order to draft status (soft delete)
            $status_before = $transaction->status;
            $transaction->status = 'draft';
            $transaction->save();

            // Adjust stock
            $input['location_id'] = $transaction->location_id;
            foreach ($transaction->sell_lines as $sell_line) {
                $input['products']['transaction_sell_lines_id'] = $sell_line->id;
                $input['products']['product_id'] = $sell_line->product_id;
                $input['products']['variation_id'] = $sell_line->variation_id;
                $input['products']['quantity'] = $sell_line->quantity;
            }

            // Update product stock
            $this->woocommerceUtil->productUtil->adjustProductStockForInvoice(
                $status_before,
                $transaction,
                $input
            );

            // Adjust mapping purchase sell
            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $transaction->location_id,
            ];
            
            $this->woocommerceUtil->transactionUtil->adjustMappingPurchaseSell(
                $status_before,
                $transaction,
                $business_data
            );

            // Log deletion
            $this->woocommerceUtil->createSyncLog(
                $business_id,
                $user_id,
                'orders',
                'deleted',
                [$transaction->invoice_no]
            );

            return ['status' => 'deleted', 'order_id' => $payload['id']];

        } catch (Exception $e) {
            Log::error('Order deletion from webhook failed', [
                'business_id' => $business_id,
                'order_id' => $payload['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process order restored webhook
     */
    protected function processOrderRestored(string $business_id, array $payload, array $metadata): array
    {
        try {
            $sell = Transaction::where('business_id', $business_id)
                ->where('woocommerce_order_id', $payload['id'])
                ->with('sell_lines', 'sell_lines.product', 'payment_lines')
                ->first();

            if ($sell) {
                // Order exists, update it
                return $this->processOrderUpdated($business_id, $payload, $metadata);
            } else {
                // Order doesn't exist, create it
                return $this->processOrderCreated($business_id, $payload, $metadata);
            }

        } catch (Exception $e) {
            Log::error('Order restoration from webhook failed', [
                'business_id' => $business_id,
                'order_id' => $payload['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process product created webhook with enhanced logging
     */
    protected function processProductCreated(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Product created webhook received with monitoring', [
            'business_id' => $business_id,
            'product_id' => $payload['id'],
            'product_name' => $payload['name'] ?? 'Unknown'
        ]);

        // Log product webhook activity
        $this->monitoringService->logProductWebhookActivity(
            $business_id,
            'created',
            $payload['id'],
            $payload
        );

        return ['status' => 'logged', 'product_id' => $payload['id']];
    }

    /**
     * Process product updated webhook with conflict detection
     */
    protected function processProductUpdated(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Product updated webhook received with monitoring', [
            'business_id' => $business_id,
            'product_id' => $payload['id'],
            'product_name' => $payload['name'] ?? 'Unknown'
        ]);

        // Check for conflicts with existing POS product
        $this->detectAndLogProductConflicts($business_id, $payload);

        // Log product webhook activity
        $this->monitoringService->logProductWebhookActivity(
            $business_id,
            'updated',
            $payload['id'],
            $payload
        );

        return ['status' => 'logged', 'product_id' => $payload['id']];
    }

    /**
     * Process product deleted webhook
     */
    protected function processProductDeleted(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Product deleted webhook received with monitoring', [
            'business_id' => $business_id,
            'product_id' => $payload['id']
        ]);

        // Log product webhook activity
        $this->monitoringService->logProductWebhookActivity(
            $business_id,
            'deleted',
            $payload['id'],
            $payload
        );

        return ['status' => 'logged', 'product_id' => $payload['id']];
    }

    /**
     * Process customer created webhook
     */
    protected function processCustomerCreated(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Customer created webhook received with monitoring', [
            'business_id' => $business_id,
            'customer_id' => $payload['id'],
            'customer_email' => $payload['email'] ?? 'Unknown'
        ]);

        return ['status' => 'logged', 'customer_id' => $payload['id']];
    }

    /**
     * Process customer updated webhook
     */
    protected function processCustomerUpdated(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Customer updated webhook received with monitoring', [
            'business_id' => $business_id,
            'customer_id' => $payload['id'],
            'customer_email' => $payload['email'] ?? 'Unknown'
        ]);

        return ['status' => 'logged', 'customer_id' => $payload['id']];
    }

    /**
     * Process customer deleted webhook
     */
    protected function processCustomerDeleted(string $business_id, array $payload, array $metadata): array
    {
        Log::info('Customer deleted webhook received with monitoring', [
            'business_id' => $business_id,
            'customer_id' => $payload['id']
        ]);

        return ['status' => 'logged', 'customer_id' => $payload['id']];
    }

    /**
     * Enhanced product conflict detection with monitoring integration
     */
    protected function detectAndLogProductConflicts(string $business_id, array $payload): void
    {
        try {
            // Trigger comprehensive conflict detection
            $conflicts = $this->conflictService->detectProductWebhookConflicts($business_id, $payload);
            
            if (!empty($conflicts)) {
                Log::info('Product conflicts detected from webhook', [
                    'business_id' => $business_id,
                    'product_id' => $payload['id'],
                    'conflicts_count' => count($conflicts)
                ]);

                // Log conflicts for monitoring dashboard
                foreach ($conflicts as $conflict) {
                    $this->monitoringService->logConflictDetected(
                        $business_id,
                        'product',
                        $payload['id'],
                        $conflict
                    );
                }
            }

        } catch (Exception $e) {
            Log::error('Product conflict detection failed', [
                'business_id' => $business_id,
                'product_id' => $payload['id'],
                'error' => $e->getMessage()
            ]);
        }
    }
}