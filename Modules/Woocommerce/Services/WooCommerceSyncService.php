<?php

namespace Modules\Woocommerce\Services;

use App\Business;
use App\Variation;
use App\VariationLocationDetail;
use Illuminate\Support\Facades\Log;
use Modules\Woocommerce\Services\ModernWooCommerceClient;
use Modules\Woocommerce\Entities\WoocommerceSyncQueue;
use Modules\Woocommerce\Entities\WoocommerceSyncConflict;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Events\SyncProgressUpdated;
use Exception;

class WooCommerceSyncService
{
    protected ModernWooCommerceClient $client;
    protected WoocommerceUtil $woocommerceUtil;
    protected int $business_id;
    
    public function __construct(WoocommerceUtil $woocommerceUtil)
    {
        $this->woocommerceUtil = $woocommerceUtil;
    }
    
    /**
     * Sync with real-time progress tracking
     */
    public function syncWithProgress(
        int $business_id,
        string $sync_type,
        array $entity_ids = [],
        array $options = []
    ): array {
        $this->business_id = $business_id;
        $this->client = new ModernWooCommerceClient($business_id);
        
        Log::info('Starting WooCommerce sync with progress tracking', [
            'business_id' => $business_id,
            'sync_type' => $sync_type,
            'entity_count' => count($entity_ids),
            'options' => $options
        ]);
        
        $startTime = microtime(true);
        $result = [
            'sync_type' => $sync_type,
            'total_items' => 0,
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'conflicts_created' => 0,
            'conflicts_resolved' => 0,
            'processing_time' => 0,
            'errors' => []
        ];
        
        try {
            // Dispatch to appropriate sync method
            switch ($sync_type) {
                case 'categories':
                    $result = array_merge($result, $this->syncCategories($entity_ids, $options));
                    break;
                    
                case 'products':
                    $result = array_merge($result, $this->syncProducts($entity_ids, $options));
                    break;
                    
                case 'orders':
                    $result = array_merge($result, $this->syncOrders($entity_ids, $options));
                    break;
                    
                case 'stock':
                    $result = array_merge($result, $this->syncStock($entity_ids, $options));
                    break;
                    
                case 'attributes':
                    $result = array_merge($result, $this->syncAttributes($entity_ids, $options));
                    break;
                    
                default:
                    throw new Exception("Unsupported sync type: {$sync_type}");
            }
            
            $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('WooCommerce sync completed', [
                'business_id' => $business_id,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            $result['processing_time'] = round((microtime(true) - $startTime) * 1000, 2);
            $result['errors'][] = $e->getMessage();
            
            Log::error('WooCommerce sync failed', [
                'business_id' => $business_id,
                'sync_type' => $sync_type,
                'error' => $e->getMessage(),
                'result' => $result
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Sync categories with progress tracking
     */
    protected function syncCategories(array $entity_ids, array $options): array
    {
        $this->broadcastProgress('syncing_categories', 10);
        
        // Use existing category sync logic but with modern client
        $user_id = $options['user_id'] ?? 1; // Default to system user
        $this->woocommerceUtil->syncCategories($this->business_id, $user_id);
        
        $this->broadcastProgress('categories_completed', 100);
        
        return [
            'total_items' => count($entity_ids),
            'processed_items' => count($entity_ids),
            'successful_items' => count($entity_ids),
            'failed_items' => 0
        ];
    }
    
    /**
     * Sync products with progress tracking
     */
    protected function syncProducts(array $entity_ids, array $options): array
    {
        $this->broadcastProgress('syncing_products', 10);
        
        $user_id = $options['user_id'] ?? 1;
        $sync_type = $options['sync_type'] ?? 'all';
        $limit = $options['limit'] ?? 0;
        $page = $options['page'] ?? 0;
        
        // Use existing product sync logic
        $synced_products = $this->woocommerceUtil->syncProducts(
            $this->business_id, 
            $user_id, 
            $sync_type, 
            $limit, 
            $page
        );
        
        $this->broadcastProgress('products_completed', 100);
        
        return [
            'total_items' => count($synced_products),
            'processed_items' => count($synced_products),
            'successful_items' => count($synced_products),
            'failed_items' => 0
        ];
    }
    
    /**
     * Sync orders with progress tracking
     */
    protected function syncOrders(array $entity_ids, array $options): array
    {
        $this->broadcastProgress('syncing_orders', 10);
        
        $user_id = $options['user_id'] ?? 1;
        
        // Use existing order sync logic
        $this->woocommerceUtil->syncOrders($this->business_id, $user_id);
        
        $this->broadcastProgress('orders_completed', 100);
        
        return [
            'total_items' => count($entity_ids),
            'processed_items' => count($entity_ids),
            'successful_items' => count($entity_ids),
            'failed_items' => 0
        ];
    }
    
    /**
     * Sync stock levels with progress tracking
     */
    protected function syncStock(array $entity_ids, array $options): array
    {
        $this->broadcastProgress('syncing_stock', 10);
        
        $processed = 0;
        $successful = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($entity_ids as $index => $variation_id) {
            try {
                // Update stock for this variation
                $this->syncSingleVariationStock($variation_id, $options);
                $successful++;
                
                // Update progress
                $progress = round((($index + 1) / count($entity_ids)) * 90) + 10;
                $this->broadcastProgress('syncing_stock', $progress);
                
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Variation {$variation_id}: " . $e->getMessage();
                
                Log::warning('Stock sync failed for variation', [
                    'variation_id' => $variation_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $processed++;
        }
        
        $this->broadcastProgress('stock_completed', 100);
        
        return [
            'total_items' => count($entity_ids),
            'processed_items' => $processed,
            'successful_items' => $successful,
            'failed_items' => $failed,
            'errors' => $errors
        ];
    }
    
    /**
     * Sync attributes with progress tracking
     */
    protected function syncAttributes(array $entity_ids, array $options): array
    {
        $this->broadcastProgress('syncing_attributes', 10);
        
        // Use existing attribute sync logic
        $this->woocommerceUtil->syncVariationAttributes($this->business_id);
        
        $this->broadcastProgress('attributes_completed', 100);
        
        return [
            'total_items' => count($entity_ids),
            'processed_items' => count($entity_ids),
            'successful_items' => count($entity_ids),
            'failed_items' => 0
        ];
    }
    
    /**
     * Sync stock for a single variation
     */
    protected function syncSingleVariationStock(int $variation_id, array $options): void
    {
        try {
            // Get location setting for this sync
            $locationSetting = WoocommerceLocationSetting::where('business_id', $this->business_id)
                                                         ->where('location_id', $options['location_id'])
                                                         ->where('is_active', true)
                                                         ->first();

            if (!$locationSetting || !$locationSetting->sync_inventory) {
                Log::info('Stock sync skipped - location not configured or inventory sync disabled', [
                    'variation_id' => $variation_id,
                    'location_id' => $options['location_id']
                ]);
                return;
            }

            // Get variation details with stock information
            $variation = Variation::with(['product', 'product.business'])
                                 ->where('id', $variation_id)
                                 ->where('deleted_at', null)
                                 ->first();

            if (!$variation || $variation->product->business_id != $this->business_id) {
                Log::warning('Stock sync failed - variation not found or access denied', [
                    'variation_id' => $variation_id,
                    'business_id' => $this->business_id
                ]);
                return;
            }

            // Get current stock for this location
            $stockDetails = VariationLocationDetail::where('variation_id', $variation_id)
                                                  ->where('location_id', $options['location_id'])
                                                  ->first();

            $currentStock = $stockDetails ? $stockDetails->qty_available : 0;
            
            // Check if product has WooCommerce mapping
            if (empty($variation->woocommerce_variation_id) || empty($variation->product->woocommerce_product_id)) {
                Log::info('Stock sync skipped - no WooCommerce mapping found', [
                    'variation_id' => $variation_id,
                    'product_id' => $variation->product_id,
                    'wc_product_id' => $variation->product->woocommerce_product_id,
                    'wc_variation_id' => $variation->woocommerce_variation_id
                ]);
                return;
            }

            // Get WooCommerce client
            $wooClient = new ModernWooCommerceClient($this->business_id, $options['location_id']);
            
            // Prepare stock update data
            $stockData = $this->prepareStockUpdateData($variation, $currentStock, $options);
            
            // Update stock in WooCommerce
            if ($variation->product->type === 'variable') {
                // Update variation stock
                $response = $wooClient->updateProductVariation(
                    $variation->product->woocommerce_product_id,
                    $variation->woocommerce_variation_id,
                    $stockData
                );
            } else {
                // Update simple product stock
                $response = $wooClient->updateProduct(
                    $variation->product->woocommerce_product_id,
                    $stockData
                );
            }

            if ($response) {
                // Log successful sync
                Log::info('Stock sync completed successfully', [
                    'variation_id' => $variation_id,
                    'location_id' => $options['location_id'],
                    'wc_product_id' => $variation->product->woocommerce_product_id,
                    'wc_variation_id' => $variation->woocommerce_variation_id,
                    'stock_quantity' => $currentStock,
                    'manage_stock' => $stockData['manage_stock']
                ]);

                // Update last sync timestamp
                if ($stockDetails) {
                    $stockDetails->update(['updated_at' => now()]);
                }

                // Update location setting sync stats
                $locationSetting->increment('total_inventory_synced');
                $locationSetting->update(['last_inventory_sync_at' => now()]);
            }

        } catch (\Exception $e) {
            Log::error('Stock sync failed with exception', [
                'variation_id' => $variation_id,
                'business_id' => $this->business_id,
                'location_id' => $options['location_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare stock update data for WooCommerce
     */
    protected function prepareStockUpdateData(Variation $variation, float $currentStock, array $options): array
    {
        $stockData = [];

        // Determine if we should manage stock
        $manageStock = $currentStock > 0 || ($options['sync_zero_stock'] ?? true);
        
        if ($manageStock) {
            $stockData['manage_stock'] = true;
            $stockData['stock_quantity'] = (int) $currentStock;
            
            // Set stock status based on quantity
            if ($currentStock > 0) {
                $stockData['stock_status'] = 'instock';
            } else {
                $stockData['stock_status'] = ($options['allow_backorders'] ?? false) ? 'onbackorder' : 'outofstock';
            }
            
            // Set low stock threshold if configured
            if (!empty($options['low_stock_threshold'])) {
                $stockData['low_stock_amount'] = (int) $options['low_stock_threshold'];
            }
            
        } else {
            // Disable stock management for this product
            $stockData['manage_stock'] = false;
            $stockData['stock_status'] = 'instock'; // Let WooCommerce handle availability
        }

        // Add backorder settings if applicable
        if ($manageStock && ($options['allow_backorders'] ?? false)) {
            $stockData['backorders'] = 'yes';
        } else {
            $stockData['backorders'] = 'no';
        }

        Log::debug('Prepared stock data for WooCommerce', [
            'variation_id' => $variation->id,
            'current_stock' => $currentStock,
            'stock_data' => $stockData
        ]);

        return $stockData;
    }

    /**
     * Sync stock for all variations in a location
     */
    public function syncAllLocationStock(int $location_id, array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Get location setting
            $locationSetting = WoocommerceLocationSetting::where('business_id', $this->business_id)
                                                         ->where('location_id', $location_id)
                                                         ->where('is_active', true)
                                                         ->first();

            if (!$locationSetting || !$locationSetting->sync_inventory) {
                return [
                    'success' => false,
                    'message' => 'Stock sync disabled for this location',
                    'synced_items' => 0
                ];
            }

            // Get all variations that have WooCommerce mappings for this business
            $variations = Variation::whereHas('product', function($query) {
                                        $query->where('business_id', $this->business_id)
                                              ->whereNotNull('woocommerce_product_id');
                                    })
                                    ->whereNotNull('woocommerce_variation_id')
                                    ->whereNull('deleted_at')
                                    ->with(['product', 'variation_location_details' => function($query) use ($location_id) {
                                        $query->where('location_id', $location_id);
                                    }])
                                    ->get();

            $syncedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($variations as $variation) {
                try {
                    $this->broadcastProgress('stock_sync', 
                        intval(($syncedCount + $failedCount) / $variations->count() * 100),
                        ['current_variation' => $variation->id, 'total' => $variations->count()]
                    );

                    $this->syncSingleVariationStock($variation->id, array_merge($options, [
                        'location_id' => $location_id
                    ]));

                    $syncedCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'variation_id' => $variation->id,
                        'product_name' => $variation->product->name ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];

                    Log::warning('Stock sync failed for variation', [
                        'variation_id' => $variation->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000); // milliseconds

            // Update location setting stats
            $locationSetting->update([
                'last_inventory_sync_at' => now(),
                'total_inventory_synced' => $locationSetting->total_inventory_synced + $syncedCount
            ]);

            Log::info('Bulk stock sync completed', [
                'business_id' => $this->business_id,
                'location_id' => $location_id,
                'total_variations' => $variations->count(),
                'synced' => $syncedCount,
                'failed' => $failedCount,
                'duration_ms' => $duration
            ]);

            return [
                'success' => true,
                'message' => "Stock sync completed successfully",
                'synced_items' => $syncedCount,
                'failed_items' => $failedCount,
                'total_items' => $variations->count(),
                'duration_ms' => $duration,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Bulk stock sync failed', [
                'business_id' => $this->business_id,
                'location_id' => $location_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Stock sync failed: ' . $e->getMessage(),
                'synced_items' => 0,
                'failed_items' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Broadcast progress update
     */
    protected function broadcastProgress(string $stage, int $percentage, array $data = []): void
    {
        try {
            event(new SyncProgressUpdated([
                'business_id' => $this->business_id,
                'stage' => $stage,
                'percentage' => $percentage,
                'data' => $data,
                'timestamp' => now()->toISOString()
            ]));
        } catch (Exception $e) {
            // Don't fail sync if broadcasting fails
            Log::warning('Failed to broadcast sync progress', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Process sync queue items
     */
    public function processQueueItems(int $limit = 50): array
    {
        $processed = 0;
        $successful = 0;
        $failed = 0;
        
        $queueItems = WoocommerceSyncQueue::readyToProcess()
            ->orderBy('priority', 'desc')
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->get();
            
        foreach ($queueItems as $item) {
            try {
                $item->markAsProcessing();
                
                $this->processQueueItem($item);
                
                $item->markAsCompleted();
                $successful++;
                
            } catch (Exception $e) {
                $item->markAsFailed($e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'failed_at' => now()->toISOString()
                ]);
                $failed++;
                
                Log::error('Queue item processing failed', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $processed++;
        }
        
        return [
            'processed' => $processed,
            'successful' => $successful,
            'failed' => $failed
        ];
    }
    
    /**
     * Process individual queue item
     */
    protected function processQueueItem(WoocommerceSyncQueue $item): void
    {
        switch ($item->sync_type) {
            case 'products':
                $this->processProductQueueItem($item);
                break;
                
            case 'categories':
                $this->processCategoryQueueItem($item);
                break;
                
            case 'stock':
                $this->processStockQueueItem($item);
                break;
                
            default:
                throw new Exception("Unsupported queue item type: {$item->sync_type}");
        }
    }
    
    /**
     * Process product queue item
     */
    protected function processProductQueueItem(WoocommerceSyncQueue $item): void
    {
        // Implementation for processing individual product sync
        Log::info('Processing product queue item', [
            'item_id' => $item->id,
            'entity_id' => $item->entity_id,
            'operation' => $item->operation
        ]);
        
        // This would call the appropriate product sync method
        // based on the operation (create, update, delete)
    }
    
    /**
     * Process category queue item
     */
    protected function processCategoryQueueItem(WoocommerceSyncQueue $item): void
    {
        Log::info('Processing category queue item', [
            'item_id' => $item->id,
            'entity_id' => $item->entity_id,
            'operation' => $item->operation
        ]);
    }
    
    /**
     * Process stock queue item
     */
    protected function processStockQueueItem(WoocommerceSyncQueue $item): void
    {
        Log::info('Processing stock queue item', [
            'item_id' => $item->id,
            'entity_id' => $item->entity_id,
            'operation' => $item->operation
        ]);
    }
}