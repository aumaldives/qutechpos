<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SyncDataCache
{
    // Cache keys and TTL configurations
    const CACHE_PREFIX = 'woocommerce_sync_cache_';
    const DEDUP_PREFIX = 'woocommerce_dedup_';
    
    // TTL configurations (in minutes)
    const TTL_PRODUCT_DATA = 60;        // 1 hour for product data
    const TTL_CUSTOMER_DATA = 120;      // 2 hours for customer data  
    const TTL_ORDER_DATA = 30;          // 30 minutes for order data
    const TTL_INVENTORY_DATA = 15;      // 15 minutes for inventory data
    const TTL_API_RESPONSES = 10;       // 10 minutes for API responses
    const TTL_DEDUPLICATION = 1440;     // 24 hours for deduplication
    const TTL_SYNC_STATE = 180;         // 3 hours for sync state
    
    // Batch size for cache operations
    const CACHE_BATCH_SIZE = 100;
    
    private $businessId;
    private $locationId;
    private $syncType;
    
    public function __construct(int $businessId, int $locationId = null, string $syncType = null)
    {
        $this->businessId = $businessId;
        $this->locationId = $locationId;
        $this->syncType = $syncType;
    }
    
    /**
     * Cache product data for sync operations
     */
    public function cacheProductData(array $products, array $options = []): bool
    {
        try {
            $ttl = $options['ttl'] ?? self::TTL_PRODUCT_DATA;
            $chunks = array_chunk($products, self::CACHE_BATCH_SIZE);
            $cachedCount = 0;
            
            foreach ($chunks as $chunkIndex => $chunk) {
                $cacheKey = $this->generateCacheKey('products', "chunk_{$chunkIndex}");
                
                // Prepare optimized cache data
                $cacheData = [
                    'timestamp' => now()->toISOString(),
                    'business_id' => $this->businessId,
                    'location_id' => $this->locationId,
                    'chunk_index' => $chunkIndex,
                    'count' => count($chunk),
                    'data' => $this->optimizeProductsForCache($chunk),
                    'hash' => $this->generateDataHash($chunk)
                ];
                
                Cache::put($cacheKey, $cacheData, $ttl);
                $cachedCount += count($chunk);
            }
            
            // Cache metadata
            $this->cacheMetadata('products', [
                'total_count' => count($products),
                'chunks' => count($chunks),
                'cached_at' => now()->toISOString()
            ], $ttl);
            
            Log::debug('Product data cached successfully', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'cached_count' => $cachedCount,
                'chunks' => count($chunks)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to cache product data', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Retrieve cached product data
     */
    public function getCachedProductData(): ?array
    {
        try {
            $metadata = $this->getCacheMetadata('products');
            if (!$metadata) {
                return null;
            }
            
            $allProducts = [];
            
            for ($i = 0; $i < $metadata['chunks']; $i++) {
                $cacheKey = $this->generateCacheKey('products', "chunk_{$i}");
                $chunkData = Cache::get($cacheKey);
                
                if (!$chunkData) {
                    Log::warning('Missing cache chunk during product retrieval', [
                        'chunk_index' => $i,
                        'cache_key' => $cacheKey
                    ]);
                    return null; // Return null if any chunk is missing
                }
                
                $allProducts = array_merge($allProducts, $chunkData['data']);
            }
            
            Log::debug('Product data retrieved from cache', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'count' => count($allProducts)
            ]);
            
            return $allProducts;
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve cached product data', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Cache WooCommerce API response data
     */
    public function cacheApiResponse(string $endpoint, array $params, $response, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? self::TTL_API_RESPONSES;
            $cacheKey = $this->generateApiCacheKey($endpoint, $params);
            
            $cacheData = [
                'endpoint' => $endpoint,
                'params' => $params,
                'response' => $response,
                'cached_at' => now()->toISOString(),
                'hash' => $this->generateDataHash([$endpoint, $params, $response])
            ];
            
            Cache::put($cacheKey, $cacheData, $ttl);
            
            Log::debug('API response cached', [
                'endpoint' => $endpoint,
                'params_hash' => md5(serialize($params)),
                'cache_key' => $cacheKey
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to cache API response', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Retrieve cached API response
     */
    public function getCachedApiResponse(string $endpoint, array $params)
    {
        try {
            $cacheKey = $this->generateApiCacheKey($endpoint, $params);
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                Log::debug('API response retrieved from cache', [
                    'endpoint' => $endpoint,
                    'cache_key' => $cacheKey,
                    'cached_at' => $cached['cached_at']
                ]);
                return $cached['response'];
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve cached API response', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Deduplicate sync operations based on data fingerprints
     */
    public function checkDuplication(string $operation, $data): bool
    {
        try {
            $fingerprint = $this->generateDataFingerprint($operation, $data);
            $cacheKey = $this->generateDedupKey($operation, $fingerprint);
            
            $existing = Cache::get($cacheKey);
            
            if ($existing) {
                Log::debug('Duplicate operation detected', [
                    'operation' => $operation,
                    'fingerprint' => $fingerprint,
                    'last_processed' => $existing['processed_at']
                ]);
                
                // Update last seen timestamp
                $existing['last_seen'] = now()->toISOString();
                $existing['duplicate_count'] = ($existing['duplicate_count'] ?? 1) + 1;
                Cache::put($cacheKey, $existing, self::TTL_DEDUPLICATION);
                
                return true; // Is duplicate
            }
            
            // Mark as processed
            Cache::put($cacheKey, [
                'operation' => $operation,
                'fingerprint' => $fingerprint,
                'processed_at' => now()->toISOString(),
                'last_seen' => now()->toISOString(),
                'duplicate_count' => 0,
                'business_id' => $this->businessId,
                'location_id' => $this->locationId
            ], self::TTL_DEDUPLICATION);
            
            return false; // Not duplicate
            
        } catch (Exception $e) {
            Log::error('Failed to check duplication', [
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
            return false; // Assume not duplicate on error
        }
    }
    
    /**
     * Cache sync state to resume interrupted operations
     */
    public function cacheSyncState(string $syncId, array $state, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? self::TTL_SYNC_STATE;
            $cacheKey = $this->generateCacheKey('sync_state', $syncId);
            
            $stateData = array_merge($state, [
                'sync_id' => $syncId,
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'updated_at' => now()->toISOString(),
                'version' => 1
            ]);
            
            Cache::put($cacheKey, $stateData, $ttl);
            
            Log::debug('Sync state cached', [
                'sync_id' => $syncId,
                'state_keys' => array_keys($state)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to cache sync state', [
                'sync_id' => $syncId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Retrieve cached sync state
     */
    public function getCachedSyncState(string $syncId): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey('sync_state', $syncId);
            $state = Cache::get($cacheKey);
            
            if ($state) {
                Log::debug('Sync state retrieved from cache', [
                    'sync_id' => $syncId,
                    'updated_at' => $state['updated_at']
                ]);
            }
            
            return $state;
            
        } catch (Exception $e) {
            Log::error('Failed to retrieve cached sync state', [
                'sync_id' => $syncId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Optimize product data for caching
     */
    private function optimizeProductsForCache(array $products): array
    {
        return array_map(function($product) {
            // Keep only essential fields for sync operations
            if (is_array($product)) {
                return array_intersect_key($product, [
                    'id' => true,
                    'name' => true,
                    'sku' => true,
                    'type' => true,
                    'status' => true,
                    'woocommerce_product_id' => true,
                    'updated_at' => true,
                    'business_id' => true,
                    'variations' => true,
                    'category_id' => true,
                    'brand_id' => true
                ]);
            }
            
            // Handle Eloquent models
            if (is_object($product) && method_exists($product, 'toArray')) {
                $array = $product->toArray();
                return array_intersect_key($array, [
                    'id' => true,
                    'name' => true,
                    'sku' => true,
                    'type' => true,
                    'status' => true,
                    'woocommerce_product_id' => true,
                    'updated_at' => true,
                    'business_id' => true,
                    'variations' => true,
                    'category_id' => true,
                    'brand_id' => true
                ]);
            }
            
            return $product;
        }, $products);
    }
    
    /**
     * Generate cache key for data
     */
    private function generateCacheKey(string $type, string $identifier = null): string
    {
        $parts = [
            self::CACHE_PREFIX,
            $this->businessId,
            $this->locationId ?? 'all',
            $type
        ];
        
        if ($identifier) {
            $parts[] = $identifier;
        }
        
        return implode('_', $parts);
    }
    
    /**
     * Generate API cache key
     */
    private function generateApiCacheKey(string $endpoint, array $params): string
    {
        $paramsHash = md5(serialize($params));
        return $this->generateCacheKey('api', $endpoint . '_' . $paramsHash);
    }
    
    /**
     * Generate deduplication key
     */
    private function generateDedupKey(string $operation, string $fingerprint): string
    {
        return self::DEDUP_PREFIX . $this->businessId . '_' . 
               ($this->locationId ?? 'all') . '_' . $operation . '_' . $fingerprint;
    }
    
    /**
     * Generate data hash for integrity checking
     */
    private function generateDataHash($data): string
    {
        return hash('sha256', serialize($data));
    }
    
    /**
     * Generate data fingerprint for deduplication
     */
    private function generateDataFingerprint(string $operation, $data): string
    {
        // Create a fingerprint based on operation and relevant data fields
        $fingerprintData = [
            'operation' => $operation,
            'business_id' => $this->businessId,
            'location_id' => $this->locationId
        ];
        
        // Add operation-specific data for fingerprinting
        switch ($operation) {
            case 'product_sync':
                if (is_array($data)) {
                    $fingerprintData['product_id'] = $data['id'] ?? null;
                    $fingerprintData['updated_at'] = $data['updated_at'] ?? null;
                } elseif (is_object($data)) {
                    $fingerprintData['product_id'] = $data->id ?? null;
                    $fingerprintData['updated_at'] = $data->updated_at ?? null;
                }
                break;
                
            case 'order_sync':
                $fingerprintData['order_id'] = is_array($data) ? ($data['id'] ?? null) : ($data->id ?? null);
                $fingerprintData['status'] = is_array($data) ? ($data['status'] ?? null) : ($data->status ?? null);
                break;
                
            case 'customer_sync':
                $fingerprintData['customer_id'] = is_array($data) ? ($data['id'] ?? null) : ($data->id ?? null);
                $fingerprintData['email'] = is_array($data) ? ($data['email'] ?? null) : ($data->email ?? null);
                break;
                
            case 'inventory_sync':
                $fingerprintData['variation_id'] = is_array($data) ? ($data['variation_id'] ?? null) : ($data->variation_id ?? null);
                $fingerprintData['qty_available'] = is_array($data) ? ($data['qty_available'] ?? null) : ($data->qty_available ?? null);
                break;
        }
        
        return hash('md5', serialize($fingerprintData));
    }
    
    /**
     * Cache metadata for cache management
     */
    private function cacheMetadata(string $type, array $metadata, int $ttl): bool
    {
        try {
            $cacheKey = $this->generateCacheKey($type . '_metadata');
            Cache::put($cacheKey, $metadata, $ttl);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to cache metadata', ['type' => $type, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get cached metadata
     */
    private function getCacheMetadata(string $type): ?array
    {
        try {
            $cacheKey = $this->generateCacheKey($type . '_metadata');
            return Cache::get($cacheKey);
        } catch (Exception $e) {
            Log::error('Failed to get cached metadata', ['type' => $type, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Clear cache for specific business/location
     */
    public function clearCache(array $types = null): int
    {
        try {
            $cleared = 0;
            $types = $types ?? ['products', 'orders', 'customers', 'inventory', 'api', 'sync_state'];
            
            foreach ($types as $type) {
                // Clear main cache
                $pattern = self::CACHE_PREFIX . $this->businessId . '_' . 
                          ($this->locationId ?? 'all') . '_' . $type . '*';
                
                $keys = $this->getCacheKeysMatching($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                    $cleared++;
                }
                
                // Clear metadata
                $metadataKey = $this->generateCacheKey($type . '_metadata');
                if (Cache::has($metadataKey)) {
                    Cache::forget($metadataKey);
                    $cleared++;
                }
            }
            
            Log::info('Cache cleared successfully', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'cleared_keys' => $cleared,
                'types' => $types
            ]);
            
            return $cleared;
            
        } catch (Exception $e) {
            Log::error('Failed to clear cache', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Get cache keys matching pattern (Redis-specific)
     */
    private function getCacheKeysMatching(string $pattern): array
    {
        try {
            // Try Redis first
            if (config('cache.default') === 'redis') {
                $redis = Cache::getRedis();
                return $redis->keys($pattern);
            }
            
            // Fallback for other cache drivers - return empty array
            // In production, you might want to implement file-based or database-based cache key tracking
            return [];
            
        } catch (Exception $e) {
            Log::warning('Could not get cache keys matching pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'cache_driver' => config('cache.default'),
                'types' => []
            ];
            
            $types = ['products', 'orders', 'customers', 'inventory', 'api', 'sync_state'];
            
            foreach ($types as $type) {
                $metadata = $this->getCacheMetadata($type);
                $stats['types'][$type] = [
                    'cached' => $metadata !== null,
                    'metadata' => $metadata
                ];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            Log::error('Failed to get cache stats', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Warm cache with fresh data
     */
    public function warmCache(array $types = null): bool
    {
        try {
            $types = $types ?? ['products'];
            $warmed = 0;
            
            foreach ($types as $type) {
                switch ($type) {
                    case 'products':
                        $products = DB::table('products')
                                     ->where('business_id', $this->businessId)
                                     ->when($this->locationId, function($q) {
                                         $q->whereHas('variations.variation_location_details', function($sq) {
                                             $sq->where('location_id', $this->locationId);
                                         });
                                     })
                                     ->select(['id', 'name', 'sku', 'type', 'woocommerce_product_id', 'updated_at'])
                                     ->get()
                                     ->toArray();
                        
                        if ($this->cacheProductData($products)) {
                            $warmed++;
                        }
                        break;
                        
                    // Add other types as needed
                }
            }
            
            Log::info('Cache warmed successfully', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'types_warmed' => $warmed
            ]);
            
            return $warmed > 0;
            
        } catch (Exception $e) {
            Log::error('Failed to warm cache', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}