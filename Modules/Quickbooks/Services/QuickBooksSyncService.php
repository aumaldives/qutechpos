<?php

namespace Modules\Quickbooks\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Contact;
use App\Product;
use App\Transaction;
use App\TransactionPayment;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;
use Modules\Quickbooks\Services\QuickBooksApiClient;

class QuickBooksSyncService
{
    private QuickBooksApiClient $apiClient;
    private QuickbooksLocationSettings $settings;

    public function __construct(QuickbooksLocationSettings $settings)
    {
        $this->settings = $settings;
        $this->apiClient = new QuickBooksApiClient($settings);
    }

    public function syncAll(): array
    {
        $results = [];

        try {
            DB::beginTransaction();

            if ($this->settings->sync_customers) {
                $results['customers'] = $this->syncCustomers();
            }

            if ($this->settings->sync_suppliers) {
                $results['suppliers'] = $this->syncSuppliers();
            }

            if ($this->settings->sync_products) {
                $results['products'] = $this->syncProducts();
            }

            if ($this->settings->sync_invoices) {
                $results['invoices'] = $this->syncInvoices();
            }

            if ($this->settings->sync_payments) {
                $results['payments'] = $this->syncPayments();
            }

            if ($this->settings->sync_purchases) {
                $results['purchases'] = $this->syncPurchases();
            }

            DB::commit();
            $this->settings->recordSuccessfulSync();

            return [
                'success' => true,
                'results' => $results,
                'message' => 'All sync operations completed successfully',
            ];

        } catch (Exception $e) {
            DB::rollBack();
            $this->settings->recordSyncError($e->getMessage());

            Log::error('QuickBooks full sync failed', [
                'business_id' => $this->settings->business_id,
                'location_id' => $this->settings->location_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    public function syncCustomers(): array
    {
        try {
            $customers = Contact::where('business_id', $this->settings->business_id)
                              ->where('type', 'customer')
                              ->whereNull('quickbooks_customer_id')
                              ->get();

            $synced = 0;
            $errors = [];

            foreach ($customers as $customer) {
                try {
                    $qbCustomerData = $this->mapCustomerToQuickBooks($customer);
                    $response = $this->apiClient->createCustomer($qbCustomerData);

                    if ($response && isset($response['Customer'])) {
                        $customer->update([
                            'quickbooks_customer_id' => $response['Customer']['Id'],
                            'quickbooks_sync_token' => $response['Customer']['SyncToken'],
                        ]);
                        $synced++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Customer {$customer->name}: " . $e->getMessage();
                    Log::error('Customer sync failed', [
                        'customer_id' => $customer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('customers');

            return [
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($customers),
            ];

        } catch (Exception $e) {
            throw new Exception('Customer sync failed: ' . $e->getMessage());
        }
    }

    public function syncSuppliers(): array
    {
        try {
            $suppliers = Contact::where('business_id', $this->settings->business_id)
                               ->where('type', 'supplier')
                               ->whereNull('quickbooks_vendor_id')
                               ->get();

            $synced = 0;
            $errors = [];

            foreach ($suppliers as $supplier) {
                try {
                    $qbVendorData = $this->mapSupplierToQuickBooks($supplier);
                    $response = $this->apiClient->createVendor($qbVendorData);

                    if ($response && isset($response['Vendor'])) {
                        $supplier->update([
                            'quickbooks_vendor_id' => $response['Vendor']['Id'],
                            'quickbooks_sync_token' => $response['Vendor']['SyncToken'],
                        ]);
                        $synced++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Supplier {$supplier->name}: " . $e->getMessage();
                    Log::error('Supplier sync failed', [
                        'supplier_id' => $supplier->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('suppliers');

            return [
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($suppliers),
            ];

        } catch (Exception $e) {
            throw new Exception('Supplier sync failed: ' . $e->getMessage());
        }
    }

    public function syncProducts(): array
    {
        try {
            $products = Product::where('business_id', $this->settings->business_id)
                              ->whereNull('quickbooks_item_id')
                              ->with(['variations', 'product_locations' => function($q) {
                                  $q->where('location_id', $this->settings->location_id);
                              }])
                              ->get();

            $synced = 0;
            $skipped = 0;
            $errors = [];

            foreach ($products as $product) {
                try {
                    // Check for existing product by SKU first
                    if ($product->sku && $this->checkProductExistsBySku($product->sku)) {
                        $skipped++;
                        Log::info('Product skipped - SKU already exists in QuickBooks', [
                            'product_id' => $product->id,
                            'sku' => $product->sku
                        ]);
                        continue;
                    }

                    $qbItemData = $this->mapProductToQuickBooks($product);
                    $response = $this->apiClient->createItem($qbItemData);

                    if ($response && isset($response['Item'])) {
                        $product->update([
                            'quickbooks_item_id' => $response['Item']['Id'],
                            'quickbooks_sync_token' => $response['Item']['SyncToken'],
                            'quickbooks_last_synced_at' => now(),
                        ]);
                        $synced++;

                        // Sync stock levels for this product
                        $this->syncProductStockLevel($product);
                    }

                } catch (Exception $e) {
                    $errors[] = "Product {$product->name} (SKU: {$product->sku}): " . $e->getMessage();
                    Log::error('Product sync failed', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('products');

            return [
                'synced' => $synced,
                'skipped' => $skipped,
                'errors' => $errors,
                'total' => count($products),
            ];

        } catch (Exception $e) {
            throw new Exception('Product sync failed: ' . $e->getMessage());
        }
    }

    private function checkProductExistsBySku(string $sku): bool
    {
        try {
            $response = $this->apiClient->makeAuthenticatedRequest('GET', "/items?where=Sku='$sku'");
            return isset($response['QueryResponse']['Item']) && count($response['QueryResponse']['Item']) > 0;
        } catch (Exception $e) {
            Log::warning('Failed to check SKU existence in QuickBooks', [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function syncProductStockLevel(Product $product): void
    {
        try {
            $locationStock = $product->product_locations->first();
            if ($locationStock && $product->quickbooks_item_id) {
                $stockData = [
                    'QtyOnHand' => $locationStock->qty_available ?? 0
                ];
                
                $this->apiClient->updateItem($product->quickbooks_item_id, $stockData);
                
                Log::info('Product stock synced', [
                    'product_id' => $product->id,
                    'quickbooks_id' => $product->quickbooks_item_id,
                    'stock_quantity' => $locationStock->qty_available ?? 0
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to sync product stock level', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function syncInvoices(): array
    {
        try {
            $invoices = Transaction::where('business_id', $this->settings->business_id)
                                  ->where('location_id', $this->settings->location_id)
                                  ->where('type', 'sell')
                                  ->where('status', 'final')
                                  ->whereNull('quickbooks_invoice_id')
                                  ->with(['contact', 'sell_lines.product', 'sell_lines.variations', 'payment_lines'])
                                  ->get();

            $synced = 0;
            $errors = [];
            $syncResults = [
                'credit_sales' => 0,
                'cash_sales' => 0,
                'bank_sales' => 0,
                'other_paid_sales' => 0,
            ];

            foreach ($invoices as $invoice) {
                try {
                    // Determine payment method category
                    $paymentCategory = $this->categorizeInvoicePayment($invoice);
                    
                    $qbInvoiceData = $this->mapInvoiceToQuickBooks($invoice, $paymentCategory);
                    $response = $this->apiClient->createInvoice($qbInvoiceData);

                    if ($response && isset($response['Invoice'])) {
                        $invoice->update([
                            'quickbooks_invoice_id' => $response['Invoice']['Id'],
                            'quickbooks_sync_token' => $response['Invoice']['SyncToken'],
                            'quickbooks_payment_category' => $paymentCategory,
                            'quickbooks_last_synced_at' => now(),
                        ]);
                        $synced++;
                        $syncResults[$paymentCategory]++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Invoice {$invoice->invoice_no}: " . $e->getMessage();
                    Log::error('Invoice sync failed', [
                        'invoice_id' => $invoice->id,
                        'payment_category' => $paymentCategory ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('invoices');

            return [
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($invoices),
                'breakdown' => $syncResults,
            ];

        } catch (Exception $e) {
            throw new Exception('Invoice sync failed: ' . $e->getMessage());
        }
    }

    private function categorizeInvoicePayment(Transaction $invoice): string
    {
        // Check payment status first
        if ($invoice->payment_status === 'due') {
            return 'credit_sales';
        }

        // Get payment methods for paid invoices
        $paymentMethods = $invoice->payment_lines->pluck('method')->unique();
        
        if ($paymentMethods->contains('cash')) {
            return 'cash_sales';
        } elseif ($paymentMethods->intersect(['card', 'bank_transfer', 'cheque'])->isNotEmpty()) {
            return 'bank_sales';
        } elseif ($paymentMethods->isNotEmpty()) {
            return 'other_paid_sales';
        }

        // Default to credit if no payment info
        return 'credit_sales';
    }

    public function syncPayments(): array
    {
        try {
            $payments = TransactionPayment::whereHas('transaction', function ($query) {
                                             $query->where('business_id', $this->settings->business_id)
                                                   ->where('location_id', $this->settings->location_id)
                                                   ->where('type', 'sell')
                                                   ->whereNotNull('quickbooks_invoice_id');
                                         })
                                         ->whereNull('quickbooks_payment_id')
                                         ->with('transaction')
                                         ->get();

            $synced = 0;
            $errors = [];

            foreach ($payments as $payment) {
                try {
                    $qbPaymentData = $this->mapPaymentToQuickBooks($payment);
                    $response = $this->apiClient->createPayment($qbPaymentData);

                    if ($response && isset($response['Payment'])) {
                        $payment->update([
                            'quickbooks_payment_id' => $response['Payment']['Id'],
                            'quickbooks_sync_token' => $response['Payment']['SyncToken'],
                        ]);
                        $synced++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Payment {$payment->id}: " . $e->getMessage();
                    Log::error('Payment sync failed', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('payments');

            return [
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($payments),
            ];

        } catch (Exception $e) {
            throw new Exception('Payment sync failed: ' . $e->getMessage());
        }
    }

    public function syncPurchases(): array
    {
        try {
            $purchases = Transaction::where('business_id', $this->settings->business_id)
                                   ->where('location_id', $this->settings->location_id)
                                   ->where('type', 'purchase')
                                   ->where('status', 'received')
                                   ->whereNull('quickbooks_bill_id')
                                   ->with(['contact', 'purchase_lines.product', 'purchase_lines.variations'])
                                   ->get();

            $synced = 0;
            $errors = [];

            foreach ($purchases as $purchase) {
                try {
                    $qbBillData = $this->mapPurchaseToQuickBooks($purchase);
                    $response = $this->apiClient->createBill($qbBillData);

                    if ($response && isset($response['Bill'])) {
                        $purchase->update([
                            'quickbooks_bill_id' => $response['Bill']['Id'],
                            'quickbooks_sync_token' => $response['Bill']['SyncToken'],
                        ]);
                        $synced++;
                    }

                } catch (Exception $e) {
                    $errors[] = "Purchase {$purchase->ref_no}: " . $e->getMessage();
                    Log::error('Purchase sync failed', [
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->settings->incrementSyncCount('purchases');

            return [
                'synced' => $synced,
                'errors' => $errors,
                'total' => count($purchases),
            ];

        } catch (Exception $e) {
            throw new Exception('Purchase sync failed: ' . $e->getMessage());
        }
    }

    private function mapCustomerToQuickBooks(Contact $customer): array
    {
        $customerData = [
            'Name' => $customer->name ?: 'Customer-' . $customer->id,
            'Active' => true,
        ];

        // Add company name if available
        if ($customer->business_name) {
            $customerData['CompanyName'] = $customer->business_name;
        }

        // Add billing address if any address info is available
        if ($customer->address_line_1 || $customer->city || $customer->country) {
            $customerData['BillAddr'] = array_filter([
                'Line1' => $customer->address_line_1,
                'Line2' => $customer->address_line_2,
                'City' => $customer->city,
                'Country' => $customer->country,
                'PostalCode' => $customer->zip_code,
            ]);
        }

        // Add phone if available
        if ($customer->mobile) {
            $customerData['PrimaryPhone'] = [
                'FreeFormNumber' => $customer->mobile,
            ];
        }

        // Add alternate phone if available
        if ($customer->alternate_number) {
            $customerData['Mobile'] = [
                'FreeFormNumber' => $customer->alternate_number,
            ];
        }

        // Add email if available
        if ($customer->email) {
            $customerData['PrimaryEmailAddr'] = [
                'Address' => $customer->email,
            ];
        }

        // Add notes and customer details
        $notes = [];
        if ($customer->notes) {
            $notes[] = $customer->notes;
        }
        if ($customer->tax_number) {
            $notes[] = 'Tax Number: ' . $customer->tax_number;
        }
        if ($customer->credit_limit) {
            $notes[] = 'Credit Limit: ' . number_format($customer->credit_limit, 2);
        }
        if (!empty($notes)) {
            $customerData['Notes'] = implode(' | ', $notes);
        }

        // Add customer location context
        $customerData['CompanyName'] = $customerData['CompanyName'] ?? ($customer->business_name ?: $customer->name);
        
        return $customerData;
    }

    private function mapSupplierToQuickBooks(Contact $supplier): array
    {
        $supplierData = [
            'Name' => $supplier->name ?: 'Supplier-' . $supplier->id,
            'Active' => true,
        ];

        // Add company name if available
        if ($supplier->business_name) {
            $supplierData['CompanyName'] = $supplier->business_name;
        }

        // Add billing address if any address info is available
        if ($supplier->address_line_1 || $supplier->city || $supplier->country) {
            $supplierData['BillAddr'] = array_filter([
                'Line1' => $supplier->address_line_1,
                'Line2' => $supplier->address_line_2,
                'City' => $supplier->city,
                'Country' => $supplier->country,
                'PostalCode' => $supplier->zip_code,
            ]);
        }

        // Add phone if available
        if ($supplier->mobile) {
            $supplierData['PrimaryPhone'] = [
                'FreeFormNumber' => $supplier->mobile,
            ];
        }

        // Add alternate phone if available  
        if ($supplier->alternate_number) {
            $supplierData['Mobile'] = [
                'FreeFormNumber' => $supplier->alternate_number,
            ];
        }

        // Add email if available
        if ($supplier->email) {
            $supplierData['PrimaryEmailAddr'] = [
                'Address' => $supplier->email,
            ];
        }

        // Add supplier-specific details
        $notes = [];
        if ($supplier->notes) {
            $notes[] = $supplier->notes;
        }
        if ($supplier->tax_number) {
            $notes[] = 'Tax Number: ' . $supplier->tax_number;
        }
        if ($supplier->pay_term) {
            $notes[] = 'Payment Terms: ' . $supplier->pay_term . ' days';
        }
        if (!empty($notes)) {
            $supplierData['Notes'] = implode(' | ', $notes);
        }

        // Add payment terms if available
        if ($supplier->pay_term && is_numeric($supplier->pay_term)) {
            // Map common payment terms to QuickBooks term IDs
            $termMapping = [
                0 => '1',    // Due on receipt
                15 => '2',   // Net 15
                30 => '3',   // Net 30  
                60 => '4',   // Net 60
            ];
            
            if (isset($termMapping[$supplier->pay_term])) {
                $supplierData['TermRef'] = ['value' => $termMapping[$supplier->pay_term]];
            }
        }

        return $supplierData;
    }

    private function mapProductToQuickBooks(Product $product): array
    {
        $locationStock = $product->product_locations->first();
        
        return [
            'Name' => $product->name,
            'Description' => $product->description ?: $product->name,
            'Type' => 'Inventory',
            'Sku' => $product->sku ?: 'POS-' . $product->id,
            'UnitPrice' => $product->sell_price_inc_tax ?? 0,
            'QtyOnHand' => $locationStock->qty_available ?? 0,
            'InvStartDate' => now()->format('Y-m-d'),
            'TrackQtyOnHand' => true,
        ];
    }

    private function mapInvoiceToQuickBooks(Transaction $invoice, string $paymentCategory = null): array
    {
        $lines = [];
        foreach ($invoice->sell_lines as $line) {
            $lines[] = [
                'Amount' => $line->unit_price_inc_tax * $line->quantity,
                'DetailType' => 'SalesItemLineDetail',
                'SalesItemLineDetail' => [
                    'ItemRef' => [
                        'value' => $line->product->quickbooks_item_id ?? '1',
                    ],
                    'UnitPrice' => $line->unit_price_inc_tax,
                    'Qty' => $line->quantity,
                ],
            ];
        }

        $qbInvoice = [
            'CustomerRef' => [
                'value' => $invoice->contact->quickbooks_customer_id ?? '1',
            ],
            'DocNumber' => $invoice->invoice_no,
            'TxnDate' => $invoice->transaction_date->format('Y-m-d'),
            'Line' => $lines,
        ];

        // Add payment terms based on category
        if ($paymentCategory === 'credit_sales') {
            $qbInvoice['SalesTermRef'] = ['value' => '3']; // Net 30
        }

        // Add memo to indicate payment method
        if ($paymentCategory) {
            $paymentMethodNames = [
                'credit_sales' => 'Credit Sale',
                'cash_sales' => 'Cash Sale',
                'bank_sales' => 'Bank/Card Payment',
                'other_paid_sales' => 'Other Payment Method'
            ];
            $qbInvoice['CustomerMemo'] = [
                'value' => $paymentMethodNames[$paymentCategory] . ' - Location: ' . $invoice->location->name ?? ''
            ];
        }

        return $qbInvoice;
    }

    private function mapPaymentToQuickBooks(TransactionPayment $payment): array
    {
        return [
            'CustomerRef' => [
                'value' => $payment->transaction->contact->quickbooks_customer_id ?? '1',
            ],
            'TotalAmt' => $payment->amount,
            'Line' => [
                [
                    'Amount' => $payment->amount,
                    'LinkedTxn' => [
                        [
                            'TxnId' => $payment->transaction->quickbooks_invoice_id,
                            'TxnType' => 'Invoice',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function mapPurchaseToQuickBooks(Transaction $purchase): array
    {
        $lines = [];
        foreach ($purchase->purchase_lines as $line) {
            $lines[] = [
                'Amount' => $line->purchase_price_inc_tax * $line->quantity,
                'DetailType' => 'ItemBasedExpenseLineDetail',
                'ItemBasedExpenseLineDetail' => [
                    'ItemRef' => [
                        'value' => $line->product->quickbooks_item_id ?? '1',
                    ],
                    'UnitPrice' => $line->purchase_price_inc_tax,
                    'Qty' => $line->quantity,
                ],
            ];
        }

        return [
            'VendorRef' => [
                'value' => $purchase->contact->quickbooks_vendor_id ?? '1',
            ],
            'TxnDate' => $purchase->transaction_date->format('Y-m-d'),
            'Line' => $lines,
        ];
    }
}