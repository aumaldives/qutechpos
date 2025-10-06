<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Woocommerce\Entities\WoocommerceSyncProgress;
use Modules\Woocommerce\Utils\SyncDataCache;
use Modules\Woocommerce\Utils\SyncQueryOptimizer;
use Carbon\Carbon;
use Exception;

class SyncBatchProcessor
{
    // Batch size configurations by sync type
    const BATCH_SIZES = [
        'products' => 50,      // Products have complex relationships
        'orders' => 25,        // Orders require transaction processing
        'customers' => 100,    // Customers are simpler to process
        'inventory' => 200,    // Inventory updates are lightweight
    ];

    // Memory thresholds
    const MEMORY_WARNING_THRESHOLD = 0.8;  // 80% of memory limit
    const MEMORY_CRITICAL_THRESHOLD = 0.9; // 90% of memory limit

    // Processing timeouts
    const BATCH_TIMEOUT_SECONDS = 30;      // Max time per batch
    const CHUNK_PROCESSING_TIMEOUT = 5;    // Max time per chunk within batch

    private $businessId;
    private $locationId;
    private $syncType;
    private $syncProgress;
    private $stats;
    private $dataCache;

    public function __construct(int $businessId, int $locationId, string $syncType, WoocommerceSyncProgress $syncProgress = null)
    {
        $this->businessId = $businessId;
        $this->locationId = $locationId;
        $this->syncType = $syncType;
        $this->syncProgress = $syncProgress;
        $this->dataCache = new SyncDataCache($businessId, $locationId, $syncType);
        $this->stats = [
            'total_processed' => 0,
            'total_success' => 0,
            'total_failed' => 0,
            'batches_processed' => 0,
            'memory_peak' => 0,
            'processing_time' => 0,
            'start_time' => microtime(true),
            'cache_hits' => 0,
            'cache_misses' => 0,
            'duplicates_skipped' => 0
        ];
    }

    /**
     * Process large dataset in optimized batches with chunking
     */
    public function processBatches(callable $dataProvider, callable $processor, array $options = []): array
    {
        $batchSize = $options['batch_size'] ?? self::BATCH_SIZES[$this->syncType] ?? 50;
        $enableMemoryOptimization = $options['memory_optimization'] ?? true;
        $enableProgressTracking = $options['progress_tracking'] ?? true;
        $enableCaching = $options['enable_caching'] ?? true;
        $enableDeduplication = $options['enable_deduplication'] ?? true;
        
        Log::info('Starting batch processing', [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'sync_type' => $this->syncType,
            'batch_size' => $batchSize,
            'memory_limit' => ini_get('memory_limit')
        ]);

        try {
            // Get total count for progress tracking
            $totalCount = $this->getTotalCount($dataProvider);
            $this->updateProgressTotal($totalCount);

            $offset = 0;
            $batchNumber = 1;

            while (true) {
                // Check system resources before processing batch
                if ($enableMemoryOptimization) {
                    $this->checkSystemResources();
                }

                // Get batch data
                $batchData = call_user_func($dataProvider, $offset, $batchSize);
                
                if (empty($batchData)) {
                    Log::info('No more data to process, completing batch processing');
                    break;
                }

                Log::debug('Processing batch', [
                    'batch_number' => $batchNumber,
                    'batch_size' => count($batchData),
                    'offset' => $offset,
                    'memory_usage' => $this->getFormattedMemoryUsage()
                ]);

                // Process batch with chunking
                $batchResult = $this->processBatch($batchData, $processor, $batchNumber);
                
                // Update statistics
                $this->updateBatchStats($batchResult);

                // Update progress tracking
                if ($enableProgressTracking && $this->syncProgress) {
                    $this->updateSyncProgress($batchResult);
                }

                // Memory cleanup after batch
                if ($enableMemoryOptimization) {
                    $this->performMemoryCleanup();
                }

                // Check for pause/cancel signals
                $this->checkSyncSignals();

                $offset += $batchSize;
                $batchNumber++;

                // Prevent infinite loops
                if ($offset > $totalCount * 2) {
                    Log::warning('Breaking batch processing loop to prevent infinite processing');
                    break;
                }
            }

            // Final statistics
            $this->stats['processing_time'] = microtime(true) - $this->stats['start_time'];
            $this->stats['memory_peak'] = memory_get_peak_usage(true);

            Log::info('Batch processing completed', $this->getProcessingStats());

            return $this->stats;

        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'sync_type' => $this->syncType,
                'error' => $e->getMessage(),
                'stats' => $this->stats
            ]);
            throw $e;
        }
    }

    /**
     * Process individual batch with chunking strategy
     */
    private function processBatch(array $batchData, callable $processor, int $batchNumber): array
    {
        $startTime = microtime(true);
        $batchResult = [
            'batch_number' => $batchNumber,
            'total_items' => count($batchData),
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'processing_time' => 0
        ];

        // Determine optimal chunk size based on data complexity
        $chunkSize = $this->getOptimalChunkSize($batchData);
        $chunks = array_chunk($batchData, $chunkSize);

        Log::debug('Processing batch chunks', [
            'batch_number' => $batchNumber,
            'total_chunks' => count($chunks),
            'chunk_size' => $chunkSize
        ]);

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkStartTime = microtime(true);

            try {
                // Set timeout for chunk processing
                set_time_limit(self::CHUNK_PROCESSING_TIMEOUT);

                $chunkResult = $this->processChunk($chunk, $processor, $batchNumber, $chunkIndex, [
                    'enable_caching' => $enableCaching,
                    'enable_deduplication' => $enableDeduplication
                ]);
                
                $batchResult['successful'] += $chunkResult['successful'];
                $batchResult['failed'] += $chunkResult['failed'];
                $batchResult['errors'] = array_merge($batchResult['errors'], $chunkResult['errors']);
                
                // Update cache stats
                $this->stats['cache_hits'] += $chunkResult['cache_hits'] ?? 0;
                $this->stats['cache_misses'] += $chunkResult['cache_misses'] ?? 0;
                $this->stats['duplicates_skipped'] += $chunkResult['duplicates_skipped'] ?? 0;

                // Log chunk completion
                $chunkTime = microtime(true) - $chunkStartTime;
                Log::debug('Chunk processed', [
                    'batch_number' => $batchNumber,
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk),
                    'successful' => $chunkResult['successful'],
                    'failed' => $chunkResult['failed'],
                    'processing_time' => round($chunkTime, 3)
                ]);

                // Check if chunk processing is taking too long
                if ($chunkTime > self::CHUNK_PROCESSING_TIMEOUT * 0.8) {
                    Log::warning('Chunk processing time approaching timeout', [
                        'batch_number' => $batchNumber,
                        'chunk_index' => $chunkIndex,
                        'processing_time' => $chunkTime,
                        'timeout_threshold' => self::CHUNK_PROCESSING_TIMEOUT
                    ]);
                }

            } catch (Exception $e) {
                Log::error('Chunk processing failed', [
                    'batch_number' => $batchNumber,
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => count($chunk),
                    'error' => $e->getMessage()
                ]);

                $batchResult['failed'] += count($chunk);
                $batchResult['errors'][] = [
                    'chunk_index' => $chunkIndex,
                    'error' => $e->getMessage(),
                    'items_affected' => count($chunk)
                ];
            }

            // Reset timeout to batch timeout
            set_time_limit(self::BATCH_TIMEOUT_SECONDS);
        }

        $batchResult['processing_time'] = microtime(true) - $startTime;

        return $batchResult;
    }

    /**
     * Process individual chunk with error isolation and caching
     */
    private function processChunk(array $chunk, callable $processor, int $batchNumber, int $chunkIndex, array $options = []): array
    {
        $result = [
            'successful' => 0, 
            'failed' => 0, 
            'errors' => [],
            'cache_hits' => 0,
            'cache_misses' => 0,
            'duplicates_skipped' => 0
        ];

        $enableCaching = $options['enable_caching'] ?? true;
        $enableDeduplication = $options['enable_deduplication'] ?? true;

        foreach ($chunk as $itemIndex => $item) {
            try {
                // Check for deduplication first
                if ($enableDeduplication) {
                    $operationType = $this->syncType . '_sync';
                    if ($this->dataCache->checkDuplication($operationType, $item)) {
                        $result['duplicates_skipped']++;
                        $result['successful']++; // Count as successful since it was already processed
                        
                        Log::debug('Duplicate item skipped', [
                            'batch_number' => $batchNumber,
                            'chunk_index' => $chunkIndex,
                            'item_index' => $itemIndex,
                            'item_id' => $this->getItemId($item)
                        ]);
                        continue;
                    }
                }

                $processed = call_user_func($processor, $item, [
                    'batch_number' => $batchNumber,
                    'chunk_index' => $chunkIndex,
                    'item_index' => $itemIndex,
                    'enable_caching' => $enableCaching,
                    'enable_deduplication' => $enableDeduplication
                ]);

                if ($processed) {
                    $result['successful']++;
                    $result['cache_misses']++; // Assume cache miss if processed
                } else {
                    $result['failed']++;
                    $result['errors'][] = [
                        'item_index' => $itemIndex,
                        'error' => 'Processor returned false',
                        'item_id' => $this->getItemId($item)
                    ];
                }

            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'item_index' => $itemIndex,
                    'error' => $e->getMessage(),
                    'item_id' => $this->getItemId($item)
                ];

                Log::error('Item processing failed', [
                    'batch_number' => $batchNumber,
                    'chunk_index' => $chunkIndex,
                    'item_index' => $itemIndex,
                    'item_id' => $this->getItemId($item),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Determine optimal chunk size based on data complexity and system resources
     */
    private function getOptimalChunkSize(array $batchData): int
    {
        $baseSizes = [
            'products' => 10,   // Complex data with relations
            'orders' => 5,      // Very complex transaction processing
            'customers' => 20,  // Moderate complexity
            'inventory' => 50,  // Simple data updates
        ];

        $baseSize = $baseSizes[$this->syncType] ?? 10;

        // Adjust based on memory usage
        $memoryUsage = $this->getMemoryUsagePercentage();
        if ($memoryUsage > 0.7) {
            $baseSize = max(1, intval($baseSize * 0.5));
        } elseif ($memoryUsage < 0.3) {
            $baseSize = intval($baseSize * 1.5);
        }

        // Adjust based on item complexity (if we can determine it)
        if (!empty($batchData)) {
            $sampleItem = $batchData[0];
            $itemComplexity = $this->estimateItemComplexity($sampleItem);
            
            if ($itemComplexity > 0.8) {
                $baseSize = max(1, intval($baseSize * 0.6));
            } elseif ($itemComplexity < 0.3) {
                $baseSize = intval($baseSize * 1.3);
            }
        }

        // Ensure reasonable bounds
        return max(1, min($baseSize, 100));
    }

    /**
     * Estimate item complexity for processing optimization
     */
    private function estimateItemComplexity($item): float
    {
        if (!is_array($item)) {
            return 0.1;
        }

        $complexity = 0.0;
        $factors = [
            'variations' => 0.3,
            'categories' => 0.1,
            'images' => 0.2,
            'attributes' => 0.2,
            'meta_data' => 0.15,
            'line_items' => 0.4,  // For orders
            'shipping' => 0.1,
            'tax_lines' => 0.1,
        ];

        foreach ($factors as $key => $weight) {
            if (isset($item[$key])) {
                if (is_array($item[$key])) {
                    $complexity += $weight * min(1.0, count($item[$key]) / 10);
                } else {
                    $complexity += $weight * 0.1;
                }
            }
        }

        return min(1.0, $complexity);
    }

    /**
     * Check system resources and adjust processing if needed
     */
    private function checkSystemResources(): void
    {
        $memoryUsage = $this->getMemoryUsagePercentage();
        
        if ($memoryUsage > self::MEMORY_CRITICAL_THRESHOLD) {
            Log::error('Critical memory usage detected', [
                'memory_usage_percentage' => $memoryUsage * 100,
                'memory_current' => $this->getFormattedMemoryUsage(),
                'memory_limit' => ini_get('memory_limit')
            ]);
            
            // Force garbage collection
            gc_collect_cycles();
            
            // Check again after cleanup
            $memoryUsage = $this->getMemoryUsagePercentage();
            if ($memoryUsage > self::MEMORY_CRITICAL_THRESHOLD) {
                throw new Exception('Memory usage too high: ' . ($memoryUsage * 100) . '%');
            }
        } elseif ($memoryUsage > self::MEMORY_WARNING_THRESHOLD) {
            Log::warning('High memory usage detected', [
                'memory_usage_percentage' => $memoryUsage * 100,
                'memory_current' => $this->getFormattedMemoryUsage()
            ]);
        }
    }

    /**
     * Perform memory cleanup operations
     */
    private function performMemoryCleanup(): void
    {
        // Force garbage collection
        gc_collect_cycles();
        
        // Clear any temporary caches
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Log memory usage after cleanup
        Log::debug('Memory cleanup performed', [
            'memory_after_cleanup' => $this->getFormattedMemoryUsage(),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true))
        ]);
    }

    /**
     * Get memory usage as percentage of limit
     */
    private function getMemoryUsagePercentage(): float
    {
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit == -1) {
            return 0.0; // No limit
        }

        $limitBytes = $this->parseMemoryLimit($memoryLimit);
        $currentBytes = memory_get_usage(true);

        return $limitBytes > 0 ? ($currentBytes / $limitBytes) : 0.0;
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = intval($limit);

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get formatted memory usage string
     */
    private function getFormattedMemoryUsage(): string
    {
        return $this->formatBytes(memory_get_usage(true));
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get total count from data provider
     */
    private function getTotalCount(callable $dataProvider): int
    {
        try {
            // Try to get count without fetching all data
            return call_user_func($dataProvider, 0, 0, true); // Pass true as count flag
        } catch (Exception $e) {
            // Fallback: estimate based on first batch
            $sampleBatch = call_user_func($dataProvider, 0, 100);
            return count($sampleBatch) * 10; // Rough estimate
        }
    }

    /**
     * Update progress tracking
     */
    private function updateProgressTotal(int $total): void
    {
        if ($this->syncProgress) {
            $this->syncProgress->updateProgress([
                'records_total' => $total,
                'current_operation' => "Processing {$total} {$this->syncType} in optimized batches..."
            ]);
        }
    }

    /**
     * Update sync progress with batch results
     */
    private function updateSyncProgress(array $batchResult): void
    {
        if (!$this->syncProgress) {
            return;
        }

        $this->syncProgress->updateProgress([
            'records_processed' => $this->stats['total_processed'],
            'records_success' => $this->stats['total_success'],
            'records_failed' => $this->stats['total_failed'],
            'current_operation' => "Processed batch {$batchResult['batch_number']} ({$this->stats['total_processed']} total)"
        ]);
    }

    /**
     * Update batch statistics
     */
    private function updateBatchStats(array $batchResult): void
    {
        $this->stats['total_processed'] += $batchResult['total_items'];
        $this->stats['total_success'] += $batchResult['successful'];
        $this->stats['total_failed'] += $batchResult['failed'];
        $this->stats['batches_processed']++;
        $this->stats['memory_peak'] = max($this->stats['memory_peak'], memory_get_peak_usage(true));
    }

    /**
     * Check for pause/cancel signals
     */
    private function checkSyncSignals(): void
    {
        if (!$this->syncProgress) {
            return;
        }

        // Check for cancel signal
        if (Cache::get("sync_cancel_{$this->syncProgress->id}")) {
            Log::info('Batch processing cancelled by user signal');
            throw new Exception('Sync cancelled by user request');
        }

        // Check for pause signal
        if (Cache::get("sync_pause_{$this->syncProgress->id}")) {
            Log::info('Batch processing paused by user signal');
            // Note: Actual pause handling would be implemented in the calling job
            throw new Exception('Sync paused by user request');
        }
    }

    /**
     * Get item ID for logging purposes
     */
    private function getItemId($item): string
    {
        if (is_array($item)) {
            return $item['id'] ?? $item['ID'] ?? 'unknown';
        } elseif (is_object($item)) {
            return $item->id ?? $item->ID ?? 'unknown';
        }
        
        return 'unknown';
    }

    /**
     * Get comprehensive processing statistics
     */
    public function getProcessingStats(): array
    {
        $cacheHitRate = ($this->stats['cache_hits'] + $this->stats['cache_misses']) > 0
            ? round(($this->stats['cache_hits'] / ($this->stats['cache_hits'] + $this->stats['cache_misses'])) * 100, 2)
            : 0;

        return [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'sync_type' => $this->syncType,
            'total_processed' => $this->stats['total_processed'],
            'total_success' => $this->stats['total_success'],
            'total_failed' => $this->stats['total_failed'],
            'duplicates_skipped' => $this->stats['duplicates_skipped'],
            'success_rate' => $this->stats['total_processed'] > 0 
                ? round(($this->stats['total_success'] / $this->stats['total_processed']) * 100, 2)
                : 0,
            'batches_processed' => $this->stats['batches_processed'],
            'processing_time' => round($this->stats['processing_time'], 3),
            'avg_time_per_batch' => $this->stats['batches_processed'] > 0
                ? round($this->stats['processing_time'] / $this->stats['batches_processed'], 3)
                : 0,
            'memory_peak' => $this->formatBytes($this->stats['memory_peak']),
            'items_per_second' => $this->stats['processing_time'] > 0
                ? round($this->stats['total_processed'] / $this->stats['processing_time'], 2)
                : 0,
            'cache_stats' => [
                'cache_hits' => $this->stats['cache_hits'],
                'cache_misses' => $this->stats['cache_misses'],
                'cache_hit_rate' => $cacheHitRate . '%',
                'duplicates_skipped' => $this->stats['duplicates_skipped']
            ]
        ];
    }

    /**
     * Create optimized data provider for products
     */
    public static function createProductDataProvider(int $businessId, int $locationId = null, array $options = []): callable
    {
        return function($offset, $limit, $countOnly = false) use ($businessId, $locationId, $options) {
            // Use optimized query builder
            $query = SyncQueryOptimizer::getProductsForSync($businessId, $locationId, array_merge($options, [
                'limit' => $countOnly ? null : $limit
            ]));

            if ($countOnly) {
                // Use optimized count query
                return $query->toBase()->getCountForPagination();
            }

            return $query->offset($offset)
                        ->limit($limit)
                        ->get()
                        ->toArray();
        };
    }

    /**
     * Create data provider for WooCommerce orders
     */
    public static function createOrderDataProvider($wooClient): callable
    {
        return function($offset, $limit, $countOnly = false) use ($wooClient) {
            if ($countOnly) {
                // Get total count from WooCommerce
                try {
                    $response = $wooClient->get('orders', ['per_page' => 1]);
                    return isset($response) && is_array($response) ? 1000 : 0; // Estimate
                } catch (Exception $e) {
                    return 0;
                }
            }

            $page = intval($offset / $limit) + 1;
            
            try {
                $orders = $wooClient->get('orders', [
                    'per_page' => $limit,
                    'page' => $page,
                    'status' => 'any'
                ]);

                return is_array($orders) ? $orders : [];
            } catch (Exception $e) {
                Log::error('Failed to fetch orders from WooCommerce', [
                    'page' => $page,
                    'limit' => $limit,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        };
    }

    /**
     * Create data provider for WooCommerce customers
     */
    public static function createCustomerDataProvider($wooClient): callable
    {
        return function($offset, $limit, $countOnly = false) use ($wooClient) {
            if ($countOnly) {
                try {
                    $response = $wooClient->get('customers', ['per_page' => 1]);
                    return isset($response) && is_array($response) ? 500 : 0; // Estimate
                } catch (Exception $e) {
                    return 0;
                }
            }

            $page = intval($offset / $limit) + 1;
            
            try {
                $customers = $wooClient->get('customers', [
                    'per_page' => $limit,
                    'page' => $page
                ]);

                return is_array($customers) ? $customers : [];
            } catch (Exception $e) {
                Log::error('Failed to fetch customers from WooCommerce', [
                    'page' => $page,
                    'limit' => $limit,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        };
    }

    /**
     * Create optimized data provider for inventory items
     */
    public static function createInventoryDataProvider(int $businessId, int $locationId, array $options = []): callable
    {
        return function($offset, $limit, $countOnly = false) use ($businessId, $locationId, $options) {
            // Use optimized inventory query
            $query = SyncQueryOptimizer::getInventoryForSync($businessId, $locationId, array_merge($options, [
                'limit' => $countOnly ? null : $limit
            ]));

            if ($countOnly) {
                return $query->count();
            }

            return $query->offset($offset)
                        ->limit($limit)
                        ->get()
                        ->toArray();
        };
    }
}