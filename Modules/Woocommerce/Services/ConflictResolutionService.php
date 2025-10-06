<?php

namespace Modules\Woocommerce\Services;

use App\Product;
use App\Category;
use App\Transaction;
use App\Business;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Modules\Woocommerce\Entities\WoocommerceSyncConflict;
use Modules\Woocommerce\Services\ModernWooCommerceClient;
use Modules\Woocommerce\Events\ConflictResolved;
use Carbon\Carbon;
use Exception;

class ConflictResolutionService
{
    protected ModernWooCommerceClient $client;
    protected int $business_id;
    
    public function __construct()
    {
        // Client will be initialized per business when needed
    }
    
    /**
     * Initialize client for specific business
     */
    protected function initializeClient(int $business_id): void
    {
        $this->business_id = $business_id;
        $this->client = new ModernWooCommerceClient($business_id);
    }
    
    /**
     * Detect conflicts between POS and WooCommerce data
     */
    public function detectConflicts(int $business_id, array $options = []): array
    {
        $this->initializeClient($business_id);
        
        Log::info('Starting conflict detection', [
            'business_id' => $business_id,
            'options' => $options
        ]);
        
        $conflicts = [
            'products' => [],
            'categories' => [],
            'orders' => [],
            'summary' => [
                'total_conflicts' => 0,
                'by_type' => [],
                'by_severity' => []
            ]
        ];
        
        try {
            // Detect product conflicts
            if (!isset($options['skip_products'])) {
                $conflicts['products'] = $this->detectProductConflicts($options);
            }
            
            // Detect category conflicts  
            if (!isset($options['skip_categories'])) {
                $conflicts['categories'] = $this->detectCategoryConflicts($options);
            }
            
            // Detect order conflicts
            if (!isset($options['skip_orders'])) {
                $conflicts['orders'] = $this->detectOrderConflicts($options);
            }
            
            // Generate summary
            $conflicts['summary'] = $this->generateConflictSummary($conflicts);
            
            Log::info('Conflict detection completed', [
                'business_id' => $business_id,
                'total_conflicts' => $conflicts['summary']['total_conflicts']
            ]);
            
            return $conflicts;
            
        } catch (Exception $e) {
            Log::error('Conflict detection failed', [
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Detect product-related conflicts
     */
    protected function detectProductConflicts(array $options = []): array
    {
        $conflicts = [];
        $limit = $options['limit'] ?? 100;
        
        // Get POS products that are synced to WooCommerce
        $posProducts = Product::where('business_id', $this->business_id)
            ->whereNotNull('woocommerce_product_id')
            ->where('woocommerce_disable_sync', 0)
            ->limit($limit)
            ->get();
            
        foreach ($posProducts as $posProduct) {
            try {
                $wooProduct = $this->client->withRetry(
                    fn($client) => $client->get("products/{$posProduct->woocommerce_product_id}"),
                    ['context' => 'conflict_detection', 'product_id' => $posProduct->id]
                );
                
                $productConflicts = $this->compareProductData($posProduct, $wooProduct);
                
                if (!empty($productConflicts)) {
                    $conflicts[] = [
                        'entity_type' => 'product',
                        'entity_id' => $posProduct->id,
                        'woocommerce_id' => $posProduct->woocommerce_product_id,
                        'conflicts' => $productConflicts
                    ];
                }
                
            } catch (Exception $e) {
                Log::warning('Failed to fetch WooCommerce product for conflict detection', [
                    'product_id' => $posProduct->id,
                    'woocommerce_product_id' => $posProduct->woocommerce_product_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Detect category-related conflicts
     */
    protected function detectCategoryConflicts(array $options = []): array
    {
        $conflicts = [];
        $limit = $options['limit'] ?? 50;
        
        // Get POS categories that are synced to WooCommerce
        $posCategories = Category::where('business_id', $this->business_id)
            ->whereNotNull('woocommerce_cat_id')
            ->where('category_type', 'product')
            ->limit($limit)
            ->get();
            
        foreach ($posCategories as $posCategory) {
            try {
                $wooCategory = $this->client->withRetry(
                    fn($client) => $client->get("products/categories/{$posCategory->woocommerce_cat_id}"),
                    ['context' => 'conflict_detection', 'category_id' => $posCategory->id]
                );
                
                $categoryConflicts = $this->compareCategoryData($posCategory, $wooCategory);
                
                if (!empty($categoryConflicts)) {
                    $conflicts[] = [
                        'entity_type' => 'category',
                        'entity_id' => $posCategory->id,
                        'woocommerce_id' => $posCategory->woocommerce_cat_id,
                        'conflicts' => $categoryConflicts
                    ];
                }
                
            } catch (Exception $e) {
                Log::warning('Failed to fetch WooCommerce category for conflict detection', [
                    'category_id' => $posCategory->id,
                    'woocommerce_cat_id' => $posCategory->woocommerce_cat_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Detect order-related conflicts
     */
    protected function detectOrderConflicts(array $options = []): array
    {
        $conflicts = [];
        $limit = $options['limit'] ?? 50;
        
        // Get POS orders that are synced to WooCommerce
        $posOrders = Transaction::where('business_id', $this->business_id)
            ->whereNotNull('woocommerce_order_id')
            ->where('type', 'sell')
            ->limit($limit)
            ->get();
            
        foreach ($posOrders as $posOrder) {
            try {
                $wooOrder = $this->client->withRetry(
                    fn($client) => $client->get("orders/{$posOrder->woocommerce_order_id}"),
                    ['context' => 'conflict_detection', 'order_id' => $posOrder->id]
                );
                
                $orderConflicts = $this->compareOrderData($posOrder, $wooOrder);
                
                if (!empty($orderConflicts)) {
                    $conflicts[] = [
                        'entity_type' => 'order',
                        'entity_id' => $posOrder->id,
                        'woocommerce_id' => $posOrder->woocommerce_order_id,
                        'conflicts' => $orderConflicts
                    ];
                }
                
            } catch (Exception $e) {
                Log::warning('Failed to fetch WooCommerce order for conflict detection', [
                    'order_id' => $posOrder->id,
                    'woocommerce_order_id' => $posOrder->woocommerce_order_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Compare product data between POS and WooCommerce
     */
    protected function compareProductData(Product $posProduct, $wooProduct): array
    {
        $conflicts = [];
        
        // Compare name
        if (trim($posProduct->name) !== trim($wooProduct->name)) {
            $conflicts[] = [
                'field' => 'name',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_MEDIUM,
                'pos_value' => $posProduct->name,
                'wc_value' => $wooProduct->name,
                'pos_updated' => $posProduct->updated_at->toISOString(),
                'wc_updated' => $wooProduct->date_modified ?? null,
                'auto_resolvable' => true,
                'recommended_resolution' => $this->getNewestValueResolution(
                    $posProduct->updated_at,
                    $wooProduct->date_modified ?? null
                )
            ];
        }
        
        // Compare price
        $posPrice = (float) $posProduct->sell_price_inc_tax;
        $wooPrice = (float) ($wooProduct->regular_price ?? 0);
        
        if (abs($posPrice - $wooPrice) > 0.01) {
            $conflicts[] = [
                'field' => 'price',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_HIGH,
                'pos_value' => $posPrice,
                'wc_value' => $wooPrice,
                'pos_updated' => $posProduct->updated_at->toISOString(),
                'wc_updated' => $wooProduct->date_modified ?? null,
                'auto_resolvable' => true,
                'recommended_resolution' => $this->getNewestValueResolution(
                    $posProduct->updated_at,
                    $wooProduct->date_modified ?? null
                )
            ];
        }
        
        // Compare stock status
        $posStatus = $posProduct->enable_stock == 1 ? 'instock' : 'outofstock';
        $wooStatus = $wooProduct->stock_status ?? 'instock';
        
        if ($posStatus !== $wooStatus) {
            $conflicts[] = [
                'field' => 'stock_status',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_HIGH,
                'pos_value' => $posStatus,
                'wc_value' => $wooStatus,
                'pos_updated' => $posProduct->updated_at->toISOString(),
                'wc_updated' => $wooProduct->date_modified ?? null,
                'auto_resolvable' => true,
                'recommended_resolution' => WoocommerceSyncConflict::RESOLUTION_POS_WINS
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Compare category data between POS and WooCommerce
     */
    protected function compareCategoryData(Category $posCategory, $wooCategory): array
    {
        $conflicts = [];
        
        // Compare name
        if (trim($posCategory->name) !== trim($wooCategory->name)) {
            $conflicts[] = [
                'field' => 'name',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_MEDIUM,
                'pos_value' => $posCategory->name,
                'wc_value' => $wooCategory->name,
                'pos_updated' => $posCategory->updated_at->toISOString(),
                'wc_updated' => $wooCategory->date_modified ?? null,
                'auto_resolvable' => true,
                'recommended_resolution' => $this->getNewestValueResolution(
                    $posCategory->updated_at,
                    $wooCategory->date_modified ?? null
                )
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Compare order data between POS and WooCommerce
     */
    protected function compareOrderData(Transaction $posOrder, $wooOrder): array
    {
        $conflicts = [];
        
        // Compare total
        $posTotal = (float) $posOrder->final_total;
        $wooTotal = (float) ($wooOrder->total ?? 0);
        
        if (abs($posTotal - $wooTotal) > 0.01) {
            $conflicts[] = [
                'field' => 'total',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_CRITICAL,
                'pos_value' => $posTotal,
                'wc_value' => $wooTotal,
                'pos_updated' => $posOrder->updated_at->toISOString(),
                'wc_updated' => $wooOrder->date_modified ?? null,
                'auto_resolvable' => false, // Financial data requires manual review
                'recommended_resolution' => WoocommerceSyncConflict::RESOLUTION_MANUAL
            ];
        }
        
        // Compare status
        $posStatus = $this->mapPosStatusToWoo($posOrder->status);
        $wooStatus = $wooOrder->status ?? 'pending';
        
        if ($posStatus !== $wooStatus) {
            $conflicts[] = [
                'field' => 'status',
                'type' => WoocommerceSyncConflict::CONFLICT_DATA_MISMATCH,
                'severity' => WoocommerceSyncConflict::SEVERITY_MEDIUM,
                'pos_value' => $posStatus,
                'wc_value' => $wooStatus,
                'pos_updated' => $posOrder->updated_at->toISOString(),
                'wc_updated' => $wooOrder->date_modified ?? null,
                'auto_resolvable' => true,
                'recommended_resolution' => WoocommerceSyncConflict::RESOLUTION_POS_WINS
            ];
        }
        
        return $conflicts;
    }
    
    /**
     * Automatically resolve conflicts based on configured strategies
     */
    public function autoResolveConflicts(int $business_id, array $options = []): array
    {
        $this->initializeClient($business_id);
        
        $maxConflicts = $options['max_conflicts'] ?? 50;
        $conflictTypes = $options['conflict_types'] ?? null;
        
        $conflicts = WoocommerceSyncConflict::forBusiness($business_id)
            ->autoResolvable()
            ->when($conflictTypes, function($query) use ($conflictTypes) {
                return $query->whereIn('conflict_type', $conflictTypes);
            })
            ->limit($maxConflicts)
            ->get();
            
        $results = [
            'processed' => 0,
            'resolved' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($conflicts as $conflict) {
            try {
                $resolved = $this->resolveConflict($conflict);
                
                if ($resolved) {
                    $results['resolved']++;
                    
                    // Broadcast conflict resolution event
                    event(new ConflictResolved($conflict));
                } else {
                    $results['failed']++;
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'conflict_id' => $conflict->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Auto-resolution failed for conflict', [
                    'conflict_id' => $conflict->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $results['processed']++;
        }
        
        Log::info('Auto-resolution completed', [
            'business_id' => $business_id,
            'results' => $results
        ]);
        
        return $results;
    }
    
    /**
     * Resolve individual conflict
     */
    public function resolveConflict(WoocommerceSyncConflict $conflict): bool
    {
        $strategy = $conflict->resolution_strategy ?: $conflict->getRecommendedResolution();
        
        Log::info('Resolving conflict', [
            'conflict_id' => $conflict->id,
            'entity_type' => $conflict->entity_type,
            'entity_id' => $conflict->entity_id,
            'strategy' => $strategy
        ]);
        
        switch ($strategy) {
            case WoocommerceSyncConflict::RESOLUTION_POS_WINS:
                return $this->applyPosWinsResolution($conflict);
                
            case WoocommerceSyncConflict::RESOLUTION_WC_WINS:
                return $this->applyWcWinsResolution($conflict);
                
            case WoocommerceSyncConflict::RESOLUTION_NEWEST_WINS:
                return $this->applyNewestWinsResolution($conflict);
                
            case WoocommerceSyncConflict::RESOLUTION_MERGE:
                return $this->applyMergeResolution($conflict);
                
            case WoocommerceSyncConflict::RESOLUTION_SKIP:
                $conflict->ignore('Skipped per resolution strategy');
                return true;
                
            default:
                Log::warning('Unknown resolution strategy', [
                    'conflict_id' => $conflict->id,
                    'strategy' => $strategy
                ]);
                return false;
        }
    }
    
    /**
     * Apply POS wins resolution
     */
    protected function applyPosWinsResolution(WoocommerceSyncConflict $conflict): bool
    {
        try {
            // Update WooCommerce with POS data
            $posData = $conflict->pos_data;
            $updateData = $this->prepareUpdateData($conflict->field_name, $posData);
            
            $this->client->withRetry(
                fn($client) => $client->put(
                    $this->getWooCommerceEndpoint($conflict->entity_type, $conflict->woocommerce_id),
                    $updateData
                ),
                ['context' => 'conflict_resolution', 'conflict_id' => $conflict->id]
            );
            
            $conflict->resolve(
                WoocommerceSyncConflict::RESOLUTION_POS_WINS,
                'POS data applied to WooCommerce'
            );
            
            return true;
            
        } catch (Exception $e) {
            Log::error('POS wins resolution failed', [
                'conflict_id' => $conflict->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Apply WooCommerce wins resolution
     */
    protected function applyWcWinsResolution(WoocommerceSyncConflict $conflict): bool
    {
        try {
            // Update POS with WooCommerce data
            $wcData = $conflict->woocommerce_data;
            $this->updatePosEntity($conflict, $wcData);
            
            $conflict->resolve(
                WoocommerceSyncConflict::RESOLUTION_WC_WINS,
                'WooCommerce data applied to POS'
            );
            
            return true;
            
        } catch (Exception $e) {
            Log::error('WC wins resolution failed', [
                'conflict_id' => $conflict->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Apply newest wins resolution
     */
    protected function applyNewestWinsResolution(WoocommerceSyncConflict $conflict): bool
    {
        $posUpdated = Carbon::parse($conflict->pos_data['updated_at'] ?? now());
        $wcUpdated = Carbon::parse($conflict->woocommerce_data['date_modified'] ?? now());
        
        if ($posUpdated->gt($wcUpdated)) {
            return $this->applyPosWinsResolution($conflict);
        } else {
            return $this->applyWcWinsResolution($conflict);
        }
    }
    
    /**
     * Apply merge resolution (combine data from both sources)
     */
    protected function applyMergeResolution(WoocommerceSyncConflict $conflict): bool
    {
        // Merge logic depends on entity type and field
        // For now, default to newest wins
        return $this->applyNewestWinsResolution($conflict);
    }
    
    /**
     * Helper methods
     */
    protected function getNewestValueResolution($posUpdated, $wcUpdated): string
    {
        if (!$wcUpdated) return WoocommerceSyncConflict::RESOLUTION_POS_WINS;
        if (!$posUpdated) return WoocommerceSyncConflict::RESOLUTION_WC_WINS;
        
        $posTime = Carbon::parse($posUpdated);
        $wcTime = Carbon::parse($wcUpdated);
        
        return $posTime->gt($wcTime) 
            ? WoocommerceSyncConflict::RESOLUTION_POS_WINS 
            : WoocommerceSyncConflict::RESOLUTION_WC_WINS;
    }
    
    protected function mapPosStatusToWoo(string $posStatus): string
    {
        $statusMap = [
            'final' => 'completed',
            'received' => 'processing', 
            'pending' => 'pending',
            'draft' => 'draft'
        ];
        
        return $statusMap[$posStatus] ?? 'pending';
    }
    
    protected function getWooCommerceEndpoint(string $entityType, int $entityId): string
    {
        $endpoints = [
            'product' => "products/{$entityId}",
            'category' => "products/categories/{$entityId}",
            'order' => "orders/{$entityId}"
        ];
        
        return $endpoints[$entityType] ?? "products/{$entityId}";
    }
    
    protected function prepareUpdateData(string $field, array $data): array
    {
        // Prepare update data based on field
        switch ($field) {
            case 'name':
                return ['name' => $data['name'] ?? $data['value']];
            case 'price':
                return ['regular_price' => (string)($data['price'] ?? $data['value'])];
            case 'stock_status':
                return ['stock_status' => $data['stock_status'] ?? $data['value']];
            default:
                return [$field => $data['value'] ?? $data];
        }
    }
    
    protected function updatePosEntity(WoocommerceSyncConflict $conflict, array $wcData): void
    {
        switch ($conflict->entity_type) {
            case 'product':
                $this->updatePosProduct($conflict->entity_id, $conflict->field_name, $wcData);
                break;
            case 'category':
                $this->updatePosCategory($conflict->entity_id, $conflict->field_name, $wcData);
                break;
            case 'order':
                $this->updatePosOrder($conflict->entity_id, $conflict->field_name, $wcData);
                break;
        }
    }
    
    protected function updatePosProduct(int $productId, string $field, array $wcData): void
    {
        $product = Product::find($productId);
        if (!$product) return;
        
        switch ($field) {
            case 'name':
                $product->name = $wcData['name'];
                break;
            case 'price':
                $product->sell_price_inc_tax = $wcData['regular_price'] ?? $wcData['price'];
                break;
        }
        
        $product->save();
    }
    
    protected function updatePosCategory(int $categoryId, string $field, array $wcData): void
    {
        $category = Category::find($categoryId);
        if (!$category) return;
        
        switch ($field) {
            case 'name':
                $category->name = $wcData['name'];
                break;
        }
        
        $category->save();
    }
    
    protected function updatePosOrder(int $orderId, string $field, array $wcData): void
    {
        $order = Transaction::find($orderId);
        if (!$order) return;
        
        // Order updates require careful handling due to accounting implications
        Log::info('Order field update requested but skipped for safety', [
            'order_id' => $orderId,
            'field' => $field
        ]);
    }
    
    protected function generateConflictSummary(array $conflicts): array
    {
        $summary = [
            'total_conflicts' => 0,
            'by_type' => [],
            'by_severity' => [],
            'by_entity' => [
                'products' => count($conflicts['products']),
                'categories' => count($conflicts['categories']),
                'orders' => count($conflicts['orders'])
            ]
        ];
        
        // Count all conflicts
        foreach (['products', 'categories', 'orders'] as $entityType) {
            foreach ($conflicts[$entityType] as $entityConflicts) {
                foreach ($entityConflicts['conflicts'] as $conflict) {
                    $summary['total_conflicts']++;
                    
                    $type = $conflict['type'];
                    $severity = $conflict['severity'];
                    
                    $summary['by_type'][$type] = ($summary['by_type'][$type] ?? 0) + 1;
                    $summary['by_severity'][$severity] = ($summary['by_severity'][$severity] ?? 0) + 1;
                }
            }
        }
        
        return $summary;
    }
}