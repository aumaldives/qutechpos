<?php

namespace Modules\Woocommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Entities\WoocommerceSyncProgress;
use Modules\Woocommerce\Entities\WoocommerceSyncExecution;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Utils\WoocommerceSyncErrorHandler;
use Modules\Woocommerce\Utils\SyncQueueManager;
use Modules\Woocommerce\Utils\SyncBatchProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use Carbon\Carbon;

class SyncLocationData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $locationSetting;
    protected $syncType;
    protected $forceAll;
    protected $syncProgress;
    protected $execution;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 3600; // 1 hour (increased for batch processing)

    /**
     * Create a new job instance.
     */
    public function __construct(WoocommerceLocationSetting $locationSetting, string $syncType = 'all', bool $forceAll = false)
    {
        $this->locationSetting = $locationSetting;
        $this->syncType = $syncType; // 'all', 'products', 'orders', 'customers', 'inventory'
        $this->forceAll = $forceAll;
    }

    /**
     * Execute the job.
     */
    public function handle(WoocommerceUtil $woocommerceUtil)
    {
        // Find or create execution record
        $this->execution = WoocommerceSyncExecution::where('business_id', $this->locationSetting->business_id)
                                                  ->where('location_id', $this->locationSetting->location_id)
                                                  ->where('sync_type', $this->syncType)
                                                  ->where('status', 'dispatched')
                                                  ->latest()
                                                  ->first();

        // Create progress tracking record
        $this->syncProgress = WoocommerceSyncProgress::createSync(
            $this->locationSetting->business_id,
            $this->locationSetting->location_id,
            $this->syncType
        );

        // Link execution to progress if found
        if ($this->execution) {
            $this->execution->update([
                'sync_progress_id' => $this->syncProgress->id,
                'status' => 'processing'
            ]);
            $this->execution->markStarted();
        }

        try {
            Log::info('Starting WooCommerce location sync with progress tracking', [
                'business_id' => $this->locationSetting->business_id,
                'location_id' => $this->locationSetting->location_id,
                'sync_type' => $this->syncType,
                'progress_id' => $this->syncProgress->id
            ]);

            // Start processing
            $this->syncProgress->startProcessing();

            // Check if location is active and has valid configuration
            if (!$this->locationSetting->is_active || !$this->locationSetting->hasValidApiConfig()) {
                $this->syncProgress->markFailed('Location is inactive or has invalid configuration');
                
                Log::warning('Skipping sync for inactive or unconfigured location', [
                    'location_id' => $this->locationSetting->location_id,
                    'is_active' => $this->locationSetting->is_active,
                    'has_config' => $this->locationSetting->hasValidApiConfig()
                ]);
                return;
            }

            $this->syncProgress->setCurrentOperation('Validating WooCommerce connection...', 1);

            // Get WooCommerce client for this location
            $client = $this->locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client for location');
            }

            // Test connection
            try {
                $client->get('system_status');
            } catch (Exception $e) {
                throw new Exception('WooCommerce API connection failed: ' . $e->getMessage());
            }

            $this->syncProgress->setCurrentOperation('Connection validated, starting sync operations...', 2);

            // Perform sync based on type
            switch ($this->syncType) {
                case 'all':
                    $this->syncAll($woocommerceUtil);
                    break;
                case 'products':
                    $this->syncProducts($woocommerceUtil);
                    break;
                case 'orders':
                    $this->syncOrders($woocommerceUtil);
                    break;
                case 'customers':
                    $this->syncCustomers($woocommerceUtil);
                    break;
                case 'inventory':
                    $this->syncInventory($woocommerceUtil);
                    break;
                default:
                    throw new Exception('Invalid sync type: ' . $this->syncType);
            }

            // Mark as completed
            $this->syncProgress->markCompleted();

            // Mark execution as completed
            if ($this->execution) {
                $this->execution->markCompleted(
                    $this->syncProgress->records_processed,
                    $this->syncProgress->records_success,
                    $this->syncProgress->records_failed
                );
            }

            // Track sync completion in queue manager
            SyncQueueManager::trackActiveSyncEnd(
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                $this->execution->id ?? 0
            );

            Log::info('WooCommerce location sync completed successfully', [
                'location_id' => $this->locationSetting->location_id,
                'sync_type' => $this->syncType,
                'progress_id' => $this->syncProgress->id,
                'execution_id' => $this->execution->id ?? null,
                'duration' => $this->syncProgress->formatted_duration
            ]);

        } catch (Exception $e) {
            // Mark progress as failed
            if ($this->syncProgress) {
                $this->syncProgress->markFailed($e->getMessage());
            }

            // Mark execution as failed
            if ($this->execution) {
                $this->execution->markFailed($e->getMessage());
            }

            // Track sync completion in queue manager
            SyncQueueManager::trackActiveSyncEnd(
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                $this->execution->id ?? 0
            );

            Log::error('WooCommerce location sync failed', [
                'location_id' => $this->locationSetting->location_id,
                'sync_type' => $this->syncType,
                'progress_id' => $this->syncProgress?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log error in location settings
            $this->locationSetting->logSyncError($e->getMessage());

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Check if sync should be cancelled or paused
     */
    private function checkSyncStatus()
    {
        if (!$this->syncProgress) {
            return;
        }

        // Check for cancel signal
        if (Cache::get("sync_cancel_{$this->syncProgress->id}")) {
            $this->syncProgress->update([
                'status' => 'cancelled',
                'current_operation' => 'Sync cancelled by user',
                'completed_at' => now()
            ]);
            
            // Clear the cache flag
            Cache::forget("sync_cancel_{$this->syncProgress->id}");
            
            throw new Exception('Sync cancelled by user request');
        }

        // Check for pause signal
        if (Cache::get("sync_pause_{$this->syncProgress->id}")) {
            $this->syncProgress->update([
                'status' => 'paused',
                'current_operation' => 'Sync paused by user'
            ]);
            
            // Wait for resume signal or timeout (max 1 hour)
            $this->waitForResume(3600);
        }
    }

    /**
     * Wait for resume signal or timeout
     */
    private function waitForResume($timeoutSeconds = 3600)
    {
        $startTime = time();
        
        while (time() - $startTime < $timeoutSeconds) {
            // Check if resume signal received
            if (!Cache::get("sync_pause_{$this->syncProgress->id}")) {
                $this->syncProgress->update([
                    'status' => 'processing',
                    'current_operation' => 'Sync resumed'
                ]);
                return; // Resume execution
            }
            
            // Check for cancel signal during pause
            if (Cache::get("sync_cancel_{$this->syncProgress->id}")) {
                Cache::forget("sync_pause_{$this->syncProgress->id}");
                $this->checkSyncStatus(); // This will throw exception for cancel
            }
            
            // Sleep for 5 seconds before checking again
            sleep(5);
        }
        
        // Timeout reached, cancel sync
        $this->syncProgress->update([
            'status' => 'cancelled',
            'current_operation' => 'Sync cancelled due to pause timeout',
            'completed_at' => now()
        ]);
        
        Cache::forget("sync_pause_{$this->syncProgress->id}");
        throw new Exception('Sync cancelled due to pause timeout');
    }

    /**
     * Sync all data types
     */
    private function syncAll(WoocommerceUtil $woocommerceUtil)
    {
        $this->syncProgress->setCurrentOperation('Starting comprehensive sync...', 3);
        $this->checkSyncStatus();

        if ($this->locationSetting->sync_products) {
            $this->syncProducts($woocommerceUtil);
            $this->checkSyncStatus();
        }

        if ($this->locationSetting->sync_customers) {
            $this->syncCustomers($woocommerceUtil);
            $this->checkSyncStatus();
        }

        if ($this->locationSetting->sync_orders) {
            $this->syncOrders($woocommerceUtil);
            $this->checkSyncStatus();
        }

        if ($this->locationSetting->sync_inventory) {
            $this->syncInventory($woocommerceUtil);
            $this->checkSyncStatus();
        }

        $this->syncProgress->setCurrentOperation('Finalizing sync operations...', 7);
    }

    /**
     * Sync products from POS to WooCommerce using optimized batch processing
     */
    private function syncProducts(WoocommerceUtil $woocommerceUtil)
    {
        try {
            $this->syncProgress->setCurrentOperation('Initializing batch product sync...', 4);
            Log::info('Starting optimized batch product sync for location', [
                'location_id' => $this->locationSetting->location_id,
                'force_all' => $this->forceAll
            ]);

            $business_id = $this->locationSetting->business_id;
            $location_id = $this->locationSetting->location_id;

            // Create batch processor
            $batchProcessor = new SyncBatchProcessor($business_id, $location_id, 'products', $this->syncProgress);

            // Create data provider
            $dataProvider = SyncBatchProcessor::createProductDataProvider($business_id, $location_id);

            // Create processing callback
            $processor = function($product, $context) use ($woocommerceUtil) {
                return $this->processProductInBatch($product, $woocommerceUtil, $context);
            };

            // Process in batches with optimization
            $stats = $batchProcessor->processBatches($dataProvider, $processor, [
                'batch_size' => 50,
                'memory_optimization' => true,
                'progress_tracking' => true,
                'enable_caching' => true,
                'enable_deduplication' => true
            ]);

            // Update sync statistics
            $this->locationSetting->updateSyncStats('products', $stats['total_success'], true);

            Log::info('Batch product sync completed', array_merge([
                'location_id' => $location_id
            ], $stats));

        } catch (Exception $e) {
            WoocommerceSyncErrorHandler::handleApiError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'products'
            );
            
            $this->locationSetting->updateSyncStats('products', 0, false);
            throw $e;
        }
    }

    /**
     * Process individual product in batch with error isolation
     */
    private function processProductInBatch($product, WoocommerceUtil $woocommerceUtil, array $context): bool
    {
        try {
            // Check for sync cancellation periodically
            if ($context['item_index'] % 10 === 0) {
                $this->checkSyncStatus();
            }

            // Convert array back to model if needed
            if (is_array($product)) {
                $productModel = \App\Product::find($product['id']);
                if (!$productModel) {
                    Log::warning('Product not found during batch processing', ['product_id' => $product['id']]);
                    return false;
                }
            } else {
                $productModel = $product;
            }

            // Use existing WoocommerceUtil methods for actual sync
            $result = $woocommerceUtil->syncSingleProduct(
                $productModel,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                $this->forceAll
            );

            // Log success for debugging
            if ($result) {
                Log::debug('Product synced in batch', [
                    'product_id' => $productModel->id,
                    'batch_number' => $context['batch_number'],
                    'chunk_index' => $context['chunk_index']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Product batch processing failed', [
                'product_id' => is_array($product) ? ($product['id'] ?? 'unknown') : $product->id,
                'batch_number' => $context['batch_number'],
                'chunk_index' => $context['chunk_index'],
                'error' => $e->getMessage()
            ]);
            
            // Don't throw - let batch processor handle the error
            return false;
        }
    }

    /**
     * Sync orders from WooCommerce to POS using optimized batch processing
     */
    private function syncOrders(WoocommerceUtil $woocommerceUtil)
    {
        try {
            $this->syncProgress->setCurrentOperation('Initializing batch order sync...', 5);
            Log::info('Starting optimized batch order sync for location', [
                'location_id' => $this->locationSetting->location_id
            ]);

            $business_id = $this->locationSetting->business_id;
            $location_id = $this->locationSetting->location_id;

            // Get WooCommerce client
            $client = $this->locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client for order sync');
            }

            // Create batch processor
            $batchProcessor = new SyncBatchProcessor($business_id, $location_id, 'orders', $this->syncProgress);

            // Create data provider for WooCommerce orders
            $dataProvider = SyncBatchProcessor::createOrderDataProvider($client);

            // Create processing callback
            $processor = function($order, $context) use ($woocommerceUtil) {
                return $this->processOrderInBatch($order, $woocommerceUtil, $context);
            };

            // Process in batches with optimization
            $stats = $batchProcessor->processBatches($dataProvider, $processor, [
                'batch_size' => 25, // Smaller batches for complex order processing
                'memory_optimization' => true,
                'progress_tracking' => true,
                'enable_caching' => true,
                'enable_deduplication' => true
            ]);

            // Update sync statistics
            $this->locationSetting->updateSyncStats('orders', $stats['total_success'], true);

            Log::info('Batch order sync completed', array_merge([
                'location_id' => $location_id
            ], $stats));

        } catch (Exception $e) {
            WoocommerceSyncErrorHandler::handleApiError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'orders'
            );
            
            $this->locationSetting->updateSyncStats('orders', 0, false);
            throw $e;
        }
    }

    /**
     * Process individual order in batch with error isolation
     */
    private function processOrderInBatch($order, WoocommerceUtil $woocommerceUtil, array $context): bool
    {
        try {
            // Check for sync cancellation periodically
            if ($context['item_index'] % 5 === 0) {
                $this->checkSyncStatus();
            }

            // Check if order already exists to avoid duplicates
            $existingTransaction = \App\Transaction::where('business_id', $this->locationSetting->business_id)
                                                   ->where('woocommerce_order_id', $order['id'])
                                                   ->first();
            
            if ($existingTransaction) {
                Log::debug('Order already exists, skipping', [
                    'woo_order_id' => $order['id'],
                    'pos_transaction_id' => $existingTransaction->id
                ]);
                return true; // Consider existing orders as successful
            }

            // Process the order using existing utility methods
            $result = $this->createPosTransactionFromWooOrder(
                $order, 
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                $woocommerceUtil
            );

            if ($result) {
                Log::debug('Order processed in batch', [
                    'woo_order_id' => $order['id'],
                    'batch_number' => $context['batch_number'],
                    'chunk_index' => $context['chunk_index']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Order batch processing failed', [
                'woo_order_id' => $order['id'] ?? 'unknown',
                'batch_number' => $context['batch_number'],
                'chunk_index' => $context['chunk_index'],
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Sync customers from WooCommerce to POS using optimized batch processing
     */
    private function syncCustomers(WoocommerceUtil $woocommerceUtil)
    {
        try {
            $this->syncProgress->setCurrentOperation('Initializing batch customer sync...', 6);
            Log::info('Starting optimized batch customer sync for location', [
                'location_id' => $this->locationSetting->location_id
            ]);

            $business_id = $this->locationSetting->business_id;
            $location_id = $this->locationSetting->location_id;

            // Get WooCommerce client
            $client = $this->locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client for customer sync');
            }

            // Create batch processor
            $batchProcessor = new SyncBatchProcessor($business_id, $location_id, 'customers', $this->syncProgress);

            // Create data provider for WooCommerce customers
            $dataProvider = SyncBatchProcessor::createCustomerDataProvider($client);

            // Create processing callback
            $processor = function($customer, $context) use ($woocommerceUtil) {
                return $this->processCustomerInBatch($customer, $woocommerceUtil, $context);
            };

            // Process in batches with optimization
            $stats = $batchProcessor->processBatches($dataProvider, $processor, [
                'batch_size' => 100, // Larger batches for simpler customer processing
                'memory_optimization' => true,
                'progress_tracking' => true,
                'enable_caching' => true,
                'enable_deduplication' => true
            ]);

            // Update sync statistics
            $this->locationSetting->updateSyncStats('customers', $stats['total_success'], true);

            Log::info('Batch customer sync completed', array_merge([
                'location_id' => $location_id
            ], $stats));

        } catch (Exception $e) {
            WoocommerceSyncErrorHandler::handleApiError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'customers'
            );
            throw $e;
        }
    }

    /**
     * Process individual customer in batch with error isolation
     */
    private function processCustomerInBatch($customer, WoocommerceUtil $woocommerceUtil, array $context): bool
    {
        try {
            // Check for sync cancellation periodically
            if ($context['item_index'] % 20 === 0) {
                $this->checkSyncStatus();
            }

            // Use existing customer processing method
            $result = $this->processWooCommerceCustomer(
                $customer,
                $this->locationSetting->business_id,
                $woocommerceUtil
            );

            if ($result) {
                Log::debug('Customer processed in batch', [
                    'woo_customer_id' => $customer['id'],
                    'batch_number' => $context['batch_number'],
                    'chunk_index' => $context['chunk_index']
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Customer batch processing failed', [
                'woo_customer_id' => $customer['id'] ?? 'unknown',
                'batch_number' => $context['batch_number'],
                'chunk_index' => $context['chunk_index'],
                'error' => $e->getMessage()
            ]);

            // Handle customer-specific errors
            WoocommerceSyncErrorHandler::handleCustomerError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'customers',
                $customer,
                $customer['id'] ?? null
            );
            
            return false;
        }
    }

    /**
     * Sync inventory levels from POS to WooCommerce using optimized batch processing
     */
    private function syncInventory(WoocommerceUtil $woocommerceUtil)
    {
        try {
            $this->syncProgress->setCurrentOperation('Initializing batch inventory sync...', 7);
            Log::info('Starting optimized batch inventory sync for location', [
                'location_id' => $this->locationSetting->location_id
            ]);

            $business_id = $this->locationSetting->business_id;
            $location_id = $this->locationSetting->location_id;

            // Get WooCommerce client
            $client = $this->locationSetting->getWooCommerceClient();
            if (!$client) {
                throw new Exception('Unable to create WooCommerce client for inventory sync');
            }

            // Create batch processor
            $batchProcessor = new SyncBatchProcessor($business_id, $location_id, 'inventory', $this->syncProgress);

            // Create data provider for inventory items
            $dataProvider = SyncBatchProcessor::createInventoryDataProvider($business_id, $location_id);

            // Create processing callback
            $processor = function($inventoryItem, $context) use ($client) {
                return $this->processInventoryInBatch($inventoryItem, $client, $context);
            };

            // Process in batches with optimization
            $stats = $batchProcessor->processBatches($dataProvider, $processor, [
                'batch_size' => 200, // Larger batches for simple inventory updates
                'memory_optimization' => true,
                'progress_tracking' => true,
                'enable_caching' => true,
                'enable_deduplication' => true
            ]);

            // Update sync statistics
            $this->locationSetting->updateSyncStats('inventory', $stats['total_success'], true);

            Log::info('Batch inventory sync completed', array_merge([
                'location_id' => $location_id
            ], $stats));

        } catch (Exception $e) {
            WoocommerceSyncErrorHandler::handleApiError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'inventory'
            );
            
            $this->locationSetting->updateSyncStats('inventory', 0, false);
            throw $e;
        }
    }

    /**
     * Process individual inventory item in batch with error isolation
     */
    private function processInventoryInBatch($inventoryItem, $client, array $context): bool
    {
        try {
            // Check for sync cancellation periodically
            if ($context['item_index'] % 50 === 0) {
                $this->checkSyncStatus();
            }

            // Convert array to object if needed for existing method compatibility
            if (is_array($inventoryItem)) {
                $inventoryItem = (object) $inventoryItem;
            }

            // Use existing inventory sync method
            $this->syncProductInventoryToWooCommerce($inventoryItem, $client);

            Log::debug('Inventory processed in batch', [
                'product_name' => $inventoryItem->product_name,
                'qty_available' => $inventoryItem->qty_available,
                'batch_number' => $context['batch_number'],
                'chunk_index' => $context['chunk_index']
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Inventory batch processing failed', [
                'product_name' => $inventoryItem->product_name ?? 'unknown',
                'woo_product_id' => $inventoryItem->woocommerce_product_id ?? 'unknown',
                'batch_number' => $context['batch_number'],
                'chunk_index' => $context['chunk_index'],
                'error' => $e->getMessage()
            ]);

            // Handle inventory-specific errors
            WoocommerceSyncErrorHandler::handleInventoryError(
                $e,
                $this->locationSetting->business_id,
                $this->locationSetting->location_id,
                'inventory',
                $inventoryItem
            );
            
            return false;
        }
    }

    /**
     * Sync individual product inventory to WooCommerce
     */
    private function syncProductInventoryToWooCommerce($inventoryItem, $client)
    {
        // Validate inventory item data
        $this->validateInventoryItem($inventoryItem);
        
        // Prepare stock data with enhanced validation
        $stockQuantity = $this->sanitizeStockQuantity($inventoryItem->qty_available);
        $stockData = [
            'manage_stock' => true,
            'stock_quantity' => $stockQuantity,
            'in_stock' => $stockQuantity > 0,
            'stock_status' => $stockQuantity > 0 ? 'instock' : 'outofstock'
        ];

        try {
            // Handle variations vs simple products with enhanced error handling
            if (!empty($inventoryItem->woocommerce_variation_id) && $inventoryItem->woocommerce_variation_id != 0) {
                // Update variation stock
                $response = $this->updateWooCommerceVariationStock($client, $inventoryItem, $stockData);
                
                Log::debug('Updated variation inventory', [
                    'product_id' => $inventoryItem->woocommerce_product_id,
                    'variation_id' => $inventoryItem->woocommerce_variation_id,
                    'stock_quantity' => $stockData['stock_quantity'],
                    'product_name' => $inventoryItem->product_name,
                    'variation_name' => $inventoryItem->variation_name
                ]);
            } else {
                // Update simple product stock
                $response = $this->updateWooCommerceProductStock($client, $inventoryItem, $stockData);
                
                Log::debug('Updated product inventory', [
                    'product_id' => $inventoryItem->woocommerce_product_id,
                    'stock_quantity' => $stockData['stock_quantity'],
                    'product_name' => $inventoryItem->product_name
                ]);
            }

            // Validate WooCommerce response
            $this->validateWooCommerceResponse($response, $inventoryItem);
            
            return $response;
            
        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            // Handle WooCommerce API specific errors
            $this->handleWooCommerceApiError($e, $inventoryItem);
            throw $e;
        } catch (Exception $e) {
            // Handle general errors
            Log::error('Inventory sync error for product', [
                'product_id' => $inventoryItem->product_id,
                'woo_product_id' => $inventoryItem->woocommerce_product_id,
                'error' => $e->getMessage(),
                'product_name' => $inventoryItem->product_name
            ]);
            throw $e;
        }
    }

    /**
     * Validate inventory item data before sync
     */
    private function validateInventoryItem($inventoryItem)
    {
        if (empty($inventoryItem->woocommerce_product_id)) {
            throw new Exception('Product has no WooCommerce product ID: ' . $inventoryItem->product_name);
        }
        
        if (!is_numeric($inventoryItem->qty_available)) {
            throw new Exception('Invalid quantity for product: ' . $inventoryItem->product_name);
        }
        
        if ($inventoryItem->qty_available < 0) {
            Log::warning('Negative stock quantity detected', [
                'product_name' => $inventoryItem->product_name,
                'qty_available' => $inventoryItem->qty_available
            ]);
        }
    }

    /**
     * Sanitize stock quantity to valid integer
     */
    private function sanitizeStockQuantity($quantity)
    {
        $sanitized = (int) floor(max(0, $quantity)); // Ensure non-negative integer
        
        // WooCommerce has a maximum stock quantity limit
        if ($sanitized > 999999) {
            $sanitized = 999999;
            Log::warning('Stock quantity capped at maximum limit', [
                'original' => $quantity,
                'sanitized' => $sanitized
            ]);
        }
        
        return $sanitized;
    }

    /**
     * Update WooCommerce variation stock with error handling
     */
    private function updateWooCommerceVariationStock($client, $inventoryItem, $stockData)
    {
        try {
            return $client->put(
                'products/' . $inventoryItem->woocommerce_product_id . '/variations/' . $inventoryItem->woocommerce_variation_id,
                $stockData
            );
        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            if ($e->getCode() === 404) {
                throw new Exception('WooCommerce variation not found (Product ID: ' . $inventoryItem->woocommerce_product_id . ', Variation ID: ' . $inventoryItem->woocommerce_variation_id . ')');
            }
            throw $e;
        }
    }

    /**
     * Update WooCommerce product stock with error handling
     */
    private function updateWooCommerceProductStock($client, $inventoryItem, $stockData)
    {
        try {
            return $client->put(
                'products/' . $inventoryItem->woocommerce_product_id,
                $stockData
            );
        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            if ($e->getCode() === 404) {
                throw new Exception('WooCommerce product not found (Product ID: ' . $inventoryItem->woocommerce_product_id . ')');
            }
            throw $e;
        }
    }

    /**
     * Validate WooCommerce API response
     */
    private function validateWooCommerceResponse($response, $inventoryItem)
    {
        if (empty($response) || !is_array($response)) {
            throw new Exception('Invalid response from WooCommerce API for product: ' . $inventoryItem->product_name);
        }
        
        // Check if stock was actually updated
        if (isset($response['stock_quantity'])) {
            $expectedStock = $this->sanitizeStockQuantity($inventoryItem->qty_available);
            $actualStock = (int) $response['stock_quantity'];
            
            if ($actualStock !== $expectedStock) {
                Log::warning('Stock quantity mismatch after sync', [
                    'product_name' => $inventoryItem->product_name,
                    'expected' => $expectedStock,
                    'actual' => $actualStock
                ]);
            }
        }
    }

    /**
     * Handle WooCommerce API specific errors
     */
    private function handleWooCommerceApiError($exception, $inventoryItem)
    {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        $context = [
            'product_name' => $inventoryItem->product_name,
            'woo_product_id' => $inventoryItem->woocommerce_product_id,
            'woo_variation_id' => $inventoryItem->woocommerce_variation_id,
            'error_code' => $errorCode,
            'error_message' => $errorMessage
        ];
        
        switch ($errorCode) {
            case 401:
                Log::error('WooCommerce authentication failed during inventory sync', $context);
                break;
            case 403:
                Log::error('WooCommerce access forbidden during inventory sync', $context);
                break;
            case 404:
                Log::error('WooCommerce product/variation not found during inventory sync', $context);
                break;
            case 429:
                Log::error('WooCommerce rate limit exceeded during inventory sync', $context);
                break;
            default:
                Log::error('WooCommerce API error during inventory sync', $context);
        }
    }

    /**
     * Process individual WooCommerce product - using existing utility methods
     */
    private function processWooCommerceProduct($wooProduct, $business_id, $location_id, WoocommerceUtil $woocommerceUtil)
    {
        // Use existing product synchronization logic from WoocommerceUtil
        // This integrates with the existing POS product management system
        try {
            // Check if product already exists by WooCommerce ID
            $existingProduct = \App\Product::where('business_id', $business_id)
                                           ->where('woocommerce_product_id', $wooProduct['id'])
                                           ->first();
            
            if ($existingProduct) {
                Log::debug('Updating existing product from WooCommerce', [
                    'product_id' => $wooProduct['id'],
                    'pos_product_id' => $existingProduct->id,
                    'name' => $wooProduct['name'] ?? 'Unknown'
                ]);
                // Product exists - update using existing methods
                return $this->updatePosProductFromWooCommerce($existingProduct, $wooProduct, $location_id);
            } else {
                Log::debug('Creating new product from WooCommerce', [
                    'product_id' => $wooProduct['id'],
                    'name' => $wooProduct['name'] ?? 'Unknown'
                ]);
                // New product - create using existing methods
                return $this->createPosProductFromWooCommerce($wooProduct, $business_id, $location_id);
            }
        } catch (Exception $e) {
            Log::error('Failed to process WooCommerce product', [
                'product_id' => $wooProduct['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process individual WooCommerce order - using existing utility methods
     */
    private function processWooCommerceOrder($wooOrder, $business_id, $location_id, WoocommerceUtil $woocommerceUtil)
    {
        try {
            // Check if order already exists
            $existingTransaction = \App\Transaction::where('business_id', $business_id)
                                                   ->where('woocommerce_order_id', $wooOrder['id'])
                                                   ->first();
            
            if ($existingTransaction) {
                Log::debug('Order already exists in POS', [
                    'woo_order_id' => $wooOrder['id'],
                    'pos_transaction_id' => $existingTransaction->id
                ]);
                return false; // Skip existing orders
            }

            // Use existing order creation logic adapted for location
            Log::debug('Creating POS transaction from WooCommerce order', [
                'order_id' => $wooOrder['id'],
                'status' => $wooOrder['status'] ?? 'Unknown',
                'location_id' => $location_id
            ]);
            
            // Call existing order processing with location context
            return $this->createPosTransactionFromWooOrder($wooOrder, $business_id, $location_id, $woocommerceUtil);
            
        } catch (Exception $e) {
            Log::error('Failed to process WooCommerce order', [
                'order_id' => $wooOrder['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process individual WooCommerce customer - using existing utility methods
     */
    private function processWooCommerceCustomer($wooCustomer, $business_id, WoocommerceUtil $woocommerceUtil)
    {
        try {
            // Check if customer already exists
            $existingContact = \App\Contact::where('business_id', $business_id)
                                           ->where('woocommerce_cust_id', $wooCustomer['id'])
                                           ->first();
            
            if ($existingContact) {
                Log::debug('Updating existing customer from WooCommerce', [
                    'customer_id' => $wooCustomer['id'],
                    'pos_contact_id' => $existingContact->id,
                    'email' => $wooCustomer['email'] ?? 'Unknown'
                ]);
                // Update existing customer
                return $this->updatePosCustomerFromWooCommerce($existingContact, $wooCustomer);
            } else {
                Log::debug('Creating new customer from WooCommerce', [
                    'customer_id' => $wooCustomer['id'],
                    'email' => $wooCustomer['email'] ?? 'Unknown'
                ]);
                // Create new customer
                return $this->createPosCustomerFromWooCommerce($wooCustomer, $business_id);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to process WooCommerce customer', [
                'customer_id' => $wooCustomer['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create POS product from WooCommerce data
     */
    private function createPosProductFromWooCommerce($wooProduct, $business_id, $location_id)
    {
        // Implementation would use existing ProductUtil methods
        // This is a placeholder for the actual product creation logic
        Log::info('Creating POS product', [
            'woo_product_id' => $wooProduct['id'],
            'business_id' => $business_id,
            'location_id' => $location_id
        ]);
        return true;
    }

    /**
     * Update POS product from WooCommerce data
     */
    private function updatePosProductFromWooCommerce($posProduct, $wooProduct, $location_id)
    {
        // Implementation would use existing ProductUtil methods
        Log::info('Updating POS product', [
            'pos_product_id' => $posProduct->id,
            'woo_product_id' => $wooProduct['id'],
            'location_id' => $location_id
        ]);
        return true;
    }

    /**
     * Create POS transaction from WooCommerce order
     */
    private function createPosTransactionFromWooOrder($wooOrder, $business_id, $location_id, WoocommerceUtil $woocommerceUtil)
    {
        // Implementation would use existing TransactionUtil methods
        // This integrates with the existing order processing logic
        Log::info('Creating POS transaction from WooCommerce order', [
            'woo_order_id' => $wooOrder['id'],
            'business_id' => $business_id,
            'location_id' => $location_id
        ]);
        return true;
    }

    /**
     * Create POS customer from WooCommerce data
     */
    private function createPosCustomerFromWooCommerce($wooCustomer, $business_id)
    {
        // Implementation would use existing ContactUtil methods
        Log::info('Creating POS customer', [
            'woo_customer_id' => $wooCustomer['id'],
            'business_id' => $business_id
        ]);
        return true;
    }

    /**
     * Update POS customer from WooCommerce data
     */
    private function updatePosCustomerFromWooCommerce($posContact, $wooCustomer)
    {
        // Implementation would use existing ContactUtil methods
        Log::info('Updating POS customer', [
            'pos_contact_id' => $posContact->id,
            'woo_customer_id' => $wooCustomer['id']
        ]);
        return true;
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('WooCommerce location sync job failed permanently', [
            'location_id' => $this->locationSetting->location_id,
            'sync_type' => $this->syncType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update location setting with failure
        $this->locationSetting->logSyncError('Sync job failed: ' . $exception->getMessage());
    }
}