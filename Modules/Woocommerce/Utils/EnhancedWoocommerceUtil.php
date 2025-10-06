<?php

namespace Modules\Woocommerce\Utils;

use App\Business;
use App\Category;
use App\Contact;
use App\Product;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Woocommerce\Entities\WoocommerceSyncLog;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use Exception;

class EnhancedWoocommerceUtil extends WoocommerceUtil
{
    /**
     * Enhanced category synchronization with better error handling and progress tracking
     */
    public function syncCategoriesEnhanced($business_id, $user_id, $progress_callback = null)
    {
        $sync_id = $this->createEnhancedSyncLog($business_id, $user_id, 'categories', 'started');
        
        try {
            $this->updateSyncProgress($sync_id, 0, 'Loading categories from database...');
            if ($progress_callback) call_user_func($progress_callback, 0, 'Loading categories...');

            $last_synced = $this->getLastSync($business_id, 'categories', false);
            
            $query = Category::where('business_id', $business_id)
                           ->where('category_type', 'product')
                           ->where('parent_id', 0);

            if (!empty($last_synced)) {
                $query->where('updated_at', '>', $last_synced);
            }

            $categories = $query->get();
            $total_categories = $categories->count();
            
            if ($total_categories == 0) {
                $this->updateSyncProgress($sync_id, 100, 'No categories to sync');
                return ['success' => true, 'message' => 'No categories to sync', 'stats' => ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0]];
            }

            $this->updateSyncProgress($sync_id, 10, "Found {$total_categories} categories to sync");
            if ($progress_callback) call_user_func($progress_callback, 10, "Processing {$total_categories} categories...");

            $woocommerce_api_settings = $this->get_api_settings($business_id);
            if (empty($woocommerce_api_settings->woocommerce_api_url)) {
                throw new Exception('WooCommerce API settings not configured');
            }

            $client = $this->getClient($woocommerce_api_settings);
            $batch_size = 10; // Process in smaller batches for better performance
            $results = ['created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

            // Process categories in batches
            foreach ($categories->chunk($batch_size) as $chunk_index => $category_chunk) {
                $progress = 10 + (($chunk_index + 1) / ceil($total_categories / $batch_size)) * 80;
                $this->updateSyncProgress($sync_id, $progress, "Processing batch " . ($chunk_index + 1));
                
                if ($progress_callback) {
                    call_user_func($progress_callback, $progress, "Processing batch " . ($chunk_index + 1) . " of " . ceil($total_categories / $batch_size));
                }

                $batch_results = $this->processCategoryBatch($category_chunk, $client, $business_id);
                $results['created'] += $batch_results['created'];
                $results['updated'] += $batch_results['updated'];
                $results['errors'] += $batch_results['errors'];
                $results['error_details'] = array_merge($results['error_details'], $batch_results['error_details']);

                // Add small delay to prevent API rate limiting
                usleep(250000); // 250ms delay
            }

            $this->updateSyncProgress($sync_id, 100, "Completed: {$results['created']} created, {$results['updated']} updated, {$results['errors']} errors");
            $this->completeSyncLog($sync_id, 'completed', $results);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, "Sync completed: {$results['created']} created, {$results['updated']} updated");
            }

            return [
                'success' => true, 
                'message' => "Category sync completed successfully",
                'stats' => [
                    'total' => $total_categories,
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                    'errors' => $results['errors']
                ]
            ];

        } catch (Exception $e) {
            Log::error('WooCommerce category sync failed', [
                'business_id' => $business_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateSyncProgress($sync_id, 100, 'Failed: ' . $e->getMessage());
            $this->completeSyncLog($sync_id, 'failed', ['error' => $e->getMessage()]);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, 'Sync failed: ' . $e->getMessage());
            }

            return ['success' => false, 'message' => 'Category sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Enhanced product synchronization with batch processing and progress tracking
     */
    public function syncProductsEnhanced($business_id, $user_id, $sync_type = 'all', $batch_size = 50, $progress_callback = null)
    {
        $sync_id = $this->createEnhancedSyncLog($business_id, $user_id, 'products', 'started');
        
        try {
            $this->updateSyncProgress($sync_id, 0, 'Initializing product sync...');
            if ($progress_callback) call_user_func($progress_callback, 0, 'Initializing...');

            // First sync categories and attributes
            $this->updateSyncProgress($sync_id, 5, 'Syncing categories...');
            $this->syncCategories($business_id, $user_id);
            
            $this->updateSyncProgress($sync_id, 10, 'Syncing variation attributes...');
            $this->syncVariationAttributes($business_id);

            $woocommerce_api_settings = $this->get_api_settings($business_id);
            if (empty($woocommerce_api_settings->woocommerce_api_url)) {
                throw new Exception('WooCommerce API settings not configured');
            }

            $this->updateSyncProgress($sync_id, 15, 'Loading products from database...');
            
            $last_synced = $this->getLastSync($business_id, 'all_products', false);
            $query = Product::where('business_id', $business_id)
                          ->whereIn('type', ['single', 'variable'])
                          ->where('woocommerce_disable_sync', 0);

            if (!empty($last_synced)) {
                $query->where('updated_at', '>', $last_synced);
            }

            $total_products = $query->count();
            
            if ($total_products == 0) {
                $this->updateSyncProgress($sync_id, 100, 'No products to sync');
                return ['success' => true, 'message' => 'No products to sync', 'stats' => ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0]];
            }

            $this->updateSyncProgress($sync_id, 20, "Found {$total_products} products to sync");
            if ($progress_callback) call_user_func($progress_callback, 20, "Processing {$total_products} products...");

            $client = $this->getClient($woocommerce_api_settings);
            $results = ['created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];

            // Process products in batches
            $processed = 0;
            $products = $query->with(['variations', 'product_variations', 'category', 'brand'])->get();
            
            foreach ($products->chunk($batch_size) as $chunk_index => $product_chunk) {
                $progress = 20 + (($chunk_index + 1) / ceil($total_products / $batch_size)) * 70;
                $this->updateSyncProgress($sync_id, $progress, "Processing batch " . ($chunk_index + 1) . " ({$processed}/{$total_products})");
                
                if ($progress_callback) {
                    call_user_func($progress_callback, $progress, "Processing products {$processed}/{$total_products}");
                }

                $batch_results = $this->processProductBatch($product_chunk, $client, $business_id, $woocommerce_api_settings);
                $results['created'] += $batch_results['created'];
                $results['updated'] += $batch_results['updated'];
                $results['errors'] += $batch_results['errors'];
                $results['error_details'] = array_merge($results['error_details'], $batch_results['error_details']);

                $processed += $product_chunk->count();

                // Add delay to prevent API rate limiting
                usleep(500000); // 500ms delay for products (more complex)
            }

            $this->updateSyncProgress($sync_id, 100, "Completed: {$results['created']} created, {$results['updated']} updated, {$results['errors']} errors");
            $this->completeSyncLog($sync_id, 'completed', $results);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, "Product sync completed: {$results['created']} created, {$results['updated']} updated");
            }

            return [
                'success' => true, 
                'message' => "Product sync completed successfully",
                'stats' => [
                    'total' => $total_products,
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                    'errors' => $results['errors']
                ]
            ];

        } catch (Exception $e) {
            Log::error('WooCommerce product sync failed', [
                'business_id' => $business_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateSyncProgress($sync_id, 100, 'Failed: ' . $e->getMessage());
            $this->completeSyncLog($sync_id, 'failed', ['error' => $e->getMessage()]);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, 'Product sync failed: ' . $e->getMessage());
            }

            return ['success' => false, 'message' => 'Product sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Enhanced order synchronization with conflict resolution
     */
    public function syncOrdersEnhanced($business_id, $user_id, $progress_callback = null)
    {
        $sync_id = $this->createEnhancedSyncLog($business_id, $user_id, 'orders', 'started');
        
        try {
            $this->updateSyncProgress($sync_id, 0, 'Loading orders from WooCommerce...');
            if ($progress_callback) call_user_func($progress_callback, 0, 'Loading orders...');

            $woocommerce_api_settings = $this->get_api_settings($business_id);
            if (empty($woocommerce_api_settings->woocommerce_api_url)) {
                throw new Exception('WooCommerce API settings not configured');
            }

            $orders = $this->getAllResponse($business_id, 'orders');
            $total_orders = count($orders);
            
            if ($total_orders == 0) {
                $this->updateSyncProgress($sync_id, 100, 'No orders to sync');
                return ['success' => true, 'message' => 'No orders to sync', 'stats' => ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0]];
            }

            $this->updateSyncProgress($sync_id, 10, "Found {$total_orders} orders to process");
            if ($progress_callback) call_user_func($progress_callback, 10, "Processing {$total_orders} orders...");

            $results = ['created' => 0, 'updated' => 0, 'errors' => 0, 'skipped' => 0, 'error_details' => []];
            $business = Business::find($business_id);
            $skipped_orders = !empty($business->woocommerce_skipped_orders) ? json_decode($business->woocommerce_skipped_orders, true) : [];

            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $woocommerce_api_settings->location_id,
                'pos_settings' => json_decode($business->pos_settings, true),
                'business' => $business,
            ];

            foreach ($orders as $index => $order) {
                $progress = 10 + (($index + 1) / $total_orders) * 85;
                $this->updateSyncProgress($sync_id, $progress, "Processing order " . ($index + 1) . "/{$total_orders}");
                
                if ($progress_callback) {
                    call_user_func($progress_callback, $progress, "Processing order " . ($index + 1) . "/{$total_orders}");
                }

                // Check if order should be skipped
                if (in_array($order['id'], $skipped_orders)) {
                    $results['skipped']++;
                    continue;
                }

                try {
                    $order_result = $this->processOrderWithConflictResolution($order, $business_data, $user_id);
                    
                    if ($order_result['success']) {
                        if ($order_result['action'] == 'created') {
                            $results['created']++;
                        } else {
                            $results['updated']++;
                        }
                    } else {
                        $results['errors']++;
                        $results['error_details'][] = [
                            'order_id' => $order['id'],
                            'error' => $order_result['message']
                        ];
                    }

                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = [
                        'order_id' => $order['id'],
                        'error' => $e->getMessage()
                    ];
                    Log::error('Order sync error', ['order_id' => $order['id'], 'error' => $e->getMessage()]);
                }

                // Add delay to prevent overwhelming the system
                usleep(100000); // 100ms delay
            }

            $this->updateSyncProgress($sync_id, 100, "Completed: {$results['created']} created, {$results['updated']} updated, {$results['errors']} errors, {$results['skipped']} skipped");
            $this->completeSyncLog($sync_id, 'completed', $results);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, "Order sync completed: {$results['created']} created, {$results['updated']} updated");
            }

            return [
                'success' => true, 
                'message' => "Order sync completed successfully",
                'stats' => [
                    'total' => $total_orders,
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                    'errors' => $results['errors'],
                    'skipped' => $results['skipped']
                ]
            ];

        } catch (Exception $e) {
            Log::error('WooCommerce order sync failed', [
                'business_id' => $business_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->updateSyncProgress($sync_id, 100, 'Failed: ' . $e->getMessage());
            $this->completeSyncLog($sync_id, 'failed', ['error' => $e->getMessage()]);

            if ($progress_callback) {
                call_user_func($progress_callback, 100, 'Order sync failed: ' . $e->getMessage());
            }

            return ['success' => false, 'message' => 'Order sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Process category batch with error handling
     */
    private function processCategoryBatch($categories, $client, $business_id)
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];
        
        foreach ($categories as $category) {
            try {
                if (empty($category->woocommerce_cat_id)) {
                    // Create new category
                    $category_data = ['name' => $category->name];
                    $response = $client->post('products/categories', $category_data);
                    
                    if ($response && isset($response['id'])) {
                        $category->woocommerce_cat_id = $response['id'];
                        $category->save();
                        $results['created']++;
                    }
                } else {
                    // Update existing category
                    $category_data = ['name' => $category->name];
                    $response = $client->put('products/categories/' . $category->woocommerce_cat_id, $category_data);
                    
                    if ($response) {
                        $results['updated']++;
                    }
                }
            } catch (Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Process product batch with error handling
     */
    private function processProductBatch($products, $client, $business_id, $woocommerce_api_settings)
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => 0, 'error_details' => []];
        
        foreach ($products as $product) {
            try {
                $product_data = $this->prepareProductData($product, $woocommerce_api_settings);
                
                if (empty($product->woocommerce_product_id)) {
                    // Create new product
                    $response = $client->post('products', $product_data);
                    
                    if ($response && isset($response['id'])) {
                        $product->woocommerce_product_id = $response['id'];
                        $product->save();
                        $results['created']++;
                    }
                } else {
                    // Update existing product
                    $response = $client->put('products/' . $product->woocommerce_product_id, $product_data);
                    
                    if ($response) {
                        $results['updated']++;
                    }
                }
            } catch (Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Process order with conflict resolution
     */
    private function processOrderWithConflictResolution($order, $business_data, $user_id)
    {
        try {
            // Check if order already exists
            $existing_transaction = Transaction::where('business_id', $business_data['id'])
                                             ->where('woocommerce_order_id', $order['id'])
                                             ->first();

            if ($existing_transaction) {
                // Check for conflicts (order updated in both systems)
                $woo_updated = strtotime($order['date_modified']);
                $pos_updated = $existing_transaction->updated_at->timestamp;
                
                if ($woo_updated > $pos_updated) {
                    // WooCommerce version is newer, update POS
                    $this->updateTransactionFromOrder($existing_transaction, $order, $business_data, $user_id);
                    return ['success' => true, 'action' => 'updated', 'message' => 'Order updated from WooCommerce'];
                } else {
                    // POS version is current, no update needed
                    return ['success' => true, 'action' => 'skipped', 'message' => 'Order is up to date'];
                }
            } else {
                // Create new order
                $transaction = $this->createTransactionFromOrder($order, $business_data, $user_id);
                return ['success' => true, 'action' => 'created', 'message' => 'New order created'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create sync log entry (overrides parent method)
     */
    public function createSyncLog($business_id, $user_id, $type, $operation = null, $data = [], $errors = null)
    {
        // If called with enhanced parameters (status as 4th param), handle accordingly
        if (is_string($operation) && empty($data) && empty($errors)) {
            $status = $operation;
            $log = WoocommerceSyncLog::create([
                'business_id' => $business_id,
                'sync_type' => $type,
                'operation_type' => 'created',
                'details' => json_encode(['status' => $status, 'started_at' => now()]),
                'created_by' => $user_id
            ]);
            return $log->id;
        }
        
        // Otherwise, use parent method signature
        return parent::createSyncLog($business_id, $user_id, $type, $operation, $data, $errors);
    }

    /**
     * Create enhanced sync log entry
     */
    public function createEnhancedSyncLog($business_id, $user_id, $sync_type, $status)
    {
        $log = WoocommerceSyncLog::create([
            'business_id' => $business_id,
            'sync_type' => $sync_type,
            'operation_type' => 'created',
            'details' => json_encode(['status' => $status, 'started_at' => now()]),
            'created_by' => $user_id
        ]);
        
        return $log->id;
    }

    /**
     * Update sync progress
     */
    public function updateSyncProgress($sync_id, $progress, $message)
    {
        $log = WoocommerceSyncLog::find($sync_id);
        if ($log) {
            $details = json_decode($log->details, true);
            $details['progress'] = $progress;
            $details['message'] = $message;
            $details['updated_at'] = now();
            
            $log->details = json_encode($details);
            $log->save();
        }
    }

    /**
     * Complete sync log
     */
    public function completeSyncLog($sync_id, $status, $results)
    {
        $log = WoocommerceSyncLog::find($sync_id);
        if ($log) {
            $details = json_decode($log->details, true);
            $details['status'] = $status;
            $details['completed_at'] = now();
            $details['results'] = $results;
            
            $log->details = json_encode($details);
            $log->operation_type = $status == 'completed' ? 'updated' : 'created';
            $log->save();
        }
    }

    /**
     * Prepare product data for WooCommerce API
     */
    private function prepareProductData($product, $woocommerce_api_settings)
    {
        $product_data = [
            'name' => $product->name,
            'type' => $product->type == 'variable' ? 'variable' : 'simple',
            'status' => 'publish',
            'description' => $product->product_description ?? '',
            'short_description' => $product->product_description ?? '',
            'sku' => $product->sku,
        ];

        // Add category if exists
        if ($product->category && !empty($product->category->woocommerce_cat_id)) {
            $product_data['categories'] = [['id' => $product->category->woocommerce_cat_id]];
        }

        // Add pricing for simple products
        if ($product->type == 'single') {
            $variation = $product->variations->first();
            if ($variation) {
                $product_data['regular_price'] = $variation->sell_price_inc_tax ?? '';
            }
        }

        return $product_data;
    }

    /**
     * Create transaction from WooCommerce order
     */
    private function createTransactionFromOrder($order, $business_data, $user_id)
    {
        // Use the parent class method or implement order creation logic
        return $this->processOrder($order, $business_data, $user_id, 'create');
    }

    /**
     * Update transaction from WooCommerce order
     */
    private function updateTransactionFromOrder($transaction, $order, $business_data, $user_id)
    {
        // Use the parent class method or implement order update logic
        return $this->processOrder($order, $business_data, $user_id, 'update', $transaction);
    }

    /**
     * Process order (create or update)
     */
    private function processOrder($order, $business_data, $user_id, $action, $existing_transaction = null)
    {
        try {
            // Basic order processing - this would typically use the parent class methods
            // For now, we'll implement a simplified version
            
            if ($action == 'create') {
                // Create new transaction
                $transaction_data = [
                    'business_id' => $business_data['id'],
                    'location_id' => $business_data['location_id'],
                    'type' => 'sell',
                    'status' => $this->mapOrderStatus($order['status']),
                    'payment_status' => $this->mapPaymentStatus($order['status']),
                    'contact_id' => $this->getOrCreateCustomer($order, $business_data),
                    'woocommerce_order_id' => $order['id'],
                    'total_before_tax' => $order['total'],
                    'final_total' => $order['total'],
                    'invoice_no' => $order['number'],
                    'created_by' => $user_id,
                    'transaction_date' => $order['date_created'],
                ];

                $transaction = Transaction::create($transaction_data);
                return $transaction;
                
            } else {
                // Update existing transaction
                $existing_transaction->status = $this->mapOrderStatus($order['status']);
                $existing_transaction->payment_status = $this->mapPaymentStatus($order['status']);
                $existing_transaction->save();
                return $existing_transaction;
            }
            
        } catch (Exception $e) {
            Log::error('Failed to process order', [
                'order_id' => $order['id'],
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Map WooCommerce order status to POS status
     */
    private function mapOrderStatus($woo_status)
    {
        $status_map = [
            'pending' => 'draft',
            'processing' => 'final',
            'on-hold' => 'draft',
            'completed' => 'final',
            'cancelled' => 'draft',
            'refunded' => 'draft',
            'failed' => 'draft'
        ];

        return $status_map[$woo_status] ?? 'draft';
    }

    /**
     * Map WooCommerce order status to payment status
     */
    private function mapPaymentStatus($woo_status)
    {
        $payment_map = [
            'pending' => 'due',
            'processing' => 'paid',
            'on-hold' => 'due',
            'completed' => 'paid',
            'cancelled' => 'due',
            'refunded' => 'paid',
            'failed' => 'due'
        ];

        return $payment_map[$woo_status] ?? 'due';
    }

    /**
     * Get or create customer from order
     */
    private function getOrCreateCustomer($order, $business_data)
    {
        // Simplified customer creation - in production this would be more robust
        if (!empty($order['customer_id'])) {
            // Try to find existing customer by WooCommerce ID
            $contact = \App\Contact::where('business_id', $business_data['id'])
                                 ->where('woocommerce_customer_id', $order['customer_id'])
                                 ->first();
                                 
            if ($contact) {
                return $contact->id;
            }
        }

        // Create new customer if needed
        if (!empty($order['billing']['email']) || !empty($order['billing']['first_name'])) {
            $contact_data = [
                'business_id' => $business_data['id'],
                'type' => 'customer',
                'name' => trim(($order['billing']['first_name'] ?? '') . ' ' . ($order['billing']['last_name'] ?? '')),
                'email' => $order['billing']['email'] ?? null,
                'mobile' => $order['billing']['phone'] ?? null,
                'woocommerce_customer_id' => $order['customer_id'] ?? null,
                'created_by' => 1
            ];

            $contact = \App\Contact::create($contact_data);
            return $contact->id;
        }

        // Return default customer if no customer info
        return null;
    }
}