<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Exception;

class SyncQueryOptimizer
{
    /**
     * Optimize product queries for sync operations
     */
    public static function getProductsForSync(int $businessId, int $locationId = null, array $options = []): Builder
    {
        $forceAll = $options['force_all'] ?? false;
        $limit = $options['limit'] ?? null;
        $syncType = $options['sync_type'] ?? 'all';
        $updatedSince = $options['updated_since'] ?? null;

        $query = \App\Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.type',
                'products.business_id',
                'products.woocommerce_product_id',
                'products.updated_at',
                'products.category_id',
                'products.brand_id',
                'products.enable_stock'
            ])
            ->where('products.business_id', $businessId);

        // Add location-specific filtering if needed
        if ($locationId) {
            $query->whereHas('variations.variation_location_details', function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        // Optimize based on sync type
        switch ($syncType) {
            case 'products':
                // Only products that need syncing
                if (!$forceAll) {
                    $query->where(function($q) {
                        $q->whereNull('woocommerce_product_id')
                          ->orWhere('updated_at', '>', DB::raw('woocommerce_synced_at'));
                    });
                }
                break;

            case 'inventory':
                // Only products with WooCommerce IDs and stock enabled
                $query->whereNotNull('woocommerce_product_id')
                      ->where('enable_stock', 1);
                break;

            default:
                // For 'all' sync type, include all products
                break;
        }

        // Add time-based filtering
        if ($updatedSince) {
            $query->where('products.updated_at', '>=', $updatedSince);
        }

        // Optimize with eager loading for related data
        $query->with([
            'variations:id,product_id,name,woocommerce_variation_id,updated_at',
            'variations.variation_location_details' => function($q) use ($locationId) {
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
                $q->select(['id', 'variation_id', 'product_id', 'location_id', 'qty_available']);
            },
            'category:id,name',
            'brand:id,name'
        ]);

        // Add ordering for consistent pagination
        $query->orderBy('products.updated_at', 'asc')
              ->orderBy('products.id', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Optimize inventory queries for sync operations
     */
    public static function getInventoryForSync(int $businessId, int $locationId, array $options = []): Builder
    {
        $limit = $options['limit'] ?? null;
        $onlyChanged = $options['only_changed'] ?? false;
        $updatedSince = $options['updated_since'] ?? null;

        $query = DB::table('variation_location_details')
            ->join('products', 'variation_location_details.product_id', '=', 'products.id')
            ->join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
            ->where('variation_location_details.location_id', $locationId)
            ->where('products.business_id', $businessId)
            ->whereNotNull('products.woocommerce_product_id')
            ->select([
                'variation_location_details.id',
                'variation_location_details.variation_id',
                'variation_location_details.product_id',
                'variation_location_details.location_id',
                'variation_location_details.qty_available',
                'variation_location_details.updated_at',
                'products.woocommerce_product_id',
                'variations.woocommerce_variation_id',
                'products.name as product_name',
                'variations.name as variation_name',
                'products.enable_stock'
            ]);

        // Only include stock-enabled products
        $query->where('products.enable_stock', 1);

        // Filter for changed inventory only
        if ($onlyChanged && $updatedSince) {
            $query->where('variation_location_details.updated_at', '>=', $updatedSince);
        }

        // Optimize ordering for batch processing
        $query->orderBy('variation_location_details.updated_at', 'asc')
              ->orderBy('variation_location_details.id', 'asc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Get WooCommerce orders that need syncing to POS
     */
    public static function getOrdersNeedingSync(int $businessId, int $locationId = null, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $status = $options['status'] ?? ['processing', 'completed', 'cancelled', 'refunded'];
        $updatedSince = $options['updated_since'] ?? Carbon::now()->subDays(30);

        // Get orders that don't exist in POS transactions table
        $existingOrderIds = DB::table('transactions')
            ->where('business_id', $businessId)
            ->when($locationId, function($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })
            ->whereNotNull('woocommerce_order_id')
            ->pluck('woocommerce_order_id')
            ->toArray();

        return [
            'exclude_order_ids' => $existingOrderIds,
            'query_params' => [
                'status' => implode(',', $status),
                'modified_after' => $updatedSince->toISOString(),
                'per_page' => $limit,
                'orderby' => 'modified',
                'order' => 'asc'
            ]
        ];
    }

    /**
     * Get customers that need syncing from WooCommerce
     */
    public static function getCustomersNeedingSync(int $businessId, array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $updatedSince = $options['updated_since'] ?? Carbon::now()->subDays(30);

        // Get customers that don't exist in POS contacts table
        $existingCustomerIds = DB::table('contacts')
            ->where('business_id', $businessId)
            ->whereNotNull('woocommerce_cust_id')
            ->pluck('woocommerce_cust_id')
            ->toArray();

        return [
            'exclude_customer_ids' => $existingCustomerIds,
            'query_params' => [
                'modified_after' => $updatedSince->toISOString(),
                'per_page' => $limit,
                'orderby' => 'modified',
                'order' => 'asc'
            ]
        ];
    }

    /**
     * Get sync execution history with optimized queries
     */
    public static function getSyncExecutionHistory(int $businessId, int $locationId = null, array $options = []): Builder
    {
        $limit = $options['limit'] ?? 50;
        $status = $options['status'] ?? null;
        $syncType = $options['sync_type'] ?? null;
        $dateFrom = $options['date_from'] ?? Carbon::now()->subDays(7);
        $dateTo = $options['date_to'] ?? Carbon::now();

        $query = DB::table('woocommerce_sync_executions')
            ->select([
                'id',
                'business_id',
                'location_id',
                'sync_type',
                'status',
                'started_at',
                'completed_at',
                'duration_seconds',
                'records_processed',
                'records_success',
                'records_failed',
                'error_message'
            ])
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($syncType) {
            $query->where('sync_type', $syncType);
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit($limit);
    }

    /**
     * Get sync performance metrics with optimized aggregations
     */
    public static function getSyncPerformanceMetrics(int $businessId, int $locationId = null, array $options = []): array
    {
        $dateFrom = $options['date_from'] ?? Carbon::now()->subDays(30);
        $dateTo = $options['date_to'] ?? Carbon::now();

        try {
            // Base query with optimized indexes
            $baseQuery = DB::table('woocommerce_sync_executions')
                ->where('business_id', $businessId)
                ->whereBetween('created_at', [$dateFrom, $dateTo]);

            if ($locationId) {
                $baseQuery->where('location_id', $locationId);
            }

            // Get aggregate metrics in a single query
            $metrics = $baseQuery
                ->select([
                    DB::raw('COUNT(*) as total_executions'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_executions'),
                    DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_executions'),
                    DB::raw('AVG(duration_seconds) as avg_duration_seconds'),
                    DB::raw('MAX(duration_seconds) as max_duration_seconds'),
                    DB::raw('MIN(duration_seconds) as min_duration_seconds'),
                    DB::raw('SUM(records_processed) as total_records_processed'),
                    DB::raw('SUM(records_success) as total_records_success'),
                    DB::raw('SUM(records_failed) as total_records_failed')
                ])
                ->first();

            // Get sync type breakdown
            $syncTypeMetrics = $baseQuery
                ->select([
                    'sync_type',
                    DB::raw('COUNT(*) as executions'),
                    DB::raw('AVG(duration_seconds) as avg_duration'),
                    DB::raw('SUM(records_processed) as total_processed')
                ])
                ->groupBy('sync_type')
                ->get();

            // Calculate derived metrics
            $successRate = $metrics->total_executions > 0 
                ? round(($metrics->successful_executions / $metrics->total_executions) * 100, 2)
                : 0;

            $dataSuccessRate = $metrics->total_records_processed > 0
                ? round(($metrics->total_records_success / $metrics->total_records_processed) * 100, 2)
                : 0;

            return [
                'period' => [
                    'from' => $dateFrom->toISOString(),
                    'to' => $dateTo->toISOString()
                ],
                'execution_metrics' => [
                    'total_executions' => (int) $metrics->total_executions,
                    'successful_executions' => (int) $metrics->successful_executions,
                    'failed_executions' => (int) $metrics->failed_executions,
                    'success_rate' => $successRate . '%'
                ],
                'performance_metrics' => [
                    'avg_duration_seconds' => round($metrics->avg_duration_seconds ?? 0, 2),
                    'max_duration_seconds' => (int) ($metrics->max_duration_seconds ?? 0),
                    'min_duration_seconds' => (int) ($metrics->min_duration_seconds ?? 0)
                ],
                'data_metrics' => [
                    'total_records_processed' => (int) $metrics->total_records_processed,
                    'total_records_success' => (int) $metrics->total_records_success,
                    'total_records_failed' => (int) $metrics->total_records_failed,
                    'data_success_rate' => $dataSuccessRate . '%'
                ],
                'sync_type_breakdown' => $syncTypeMetrics->map(function($item) {
                    return [
                        'sync_type' => $item->sync_type,
                        'executions' => (int) $item->executions,
                        'avg_duration' => round($item->avg_duration, 2),
                        'total_processed' => (int) $item->total_processed
                    ];
                })->toArray()
            ];

        } catch (Exception $e) {
            Log::error('Failed to get sync performance metrics', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Failed to retrieve performance metrics',
                'period' => [
                    'from' => $dateFrom->toISOString(),
                    'to' => $dateTo->toISOString()
                ]
            ];
        }
    }

    /**
     * Cleanup old sync records with optimized batch deletion
     */
    public static function cleanupOldSyncRecords(array $options = []): array
    {
        $retentionDays = $options['retention_days'] ?? 90;
        $batchSize = $options['batch_size'] ?? 1000;
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $results = [
            'executions_cleaned' => 0,
            'progress_cleaned' => 0,
            'errors' => []
        ];

        try {
            // Clean up old completed executions in batches
            do {
                $deletedExecutions = DB::table('woocommerce_sync_executions')
                    ->where('created_at', '<', $cutoffDate)
                    ->where('status', 'completed')
                    ->limit($batchSize)
                    ->delete();
                
                $results['executions_cleaned'] += $deletedExecutions;
                
                // Small delay to prevent overwhelming the database
                if ($deletedExecutions > 0) {
                    usleep(100000); // 100ms
                }
            } while ($deletedExecutions > 0);

            // Clean up old completed progress records in batches
            do {
                $deletedProgress = DB::table('woocommerce_sync_progress')
                    ->where('completed_at', '<', $cutoffDate)
                    ->where('status', 'completed')
                    ->limit($batchSize)
                    ->delete();
                
                $results['progress_cleaned'] += $deletedProgress;
                
                if ($deletedProgress > 0) {
                    usleep(100000); // 100ms
                }
            } while ($deletedProgress > 0);

        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error('Failed to cleanup old sync records', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toISOString()
            ]);
        }

        Log::info('Sync records cleanup completed', $results);
        return $results;
    }

    /**
     * Analyze query performance for sync operations
     */
    public static function analyzeQueryPerformance(string $query, array $bindings = []): array
    {
        try {
            // Enable query logging temporarily
            DB::enableQueryLog();
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Execute query
            $results = DB::select($query, $bindings);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $queryLog = DB::getQueryLog();
            DB::disableQueryLog();
            
            return [
                'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
                'memory_used' => self::formatBytes($endMemory - $startMemory),
                'result_count' => count($results),
                'query' => $query,
                'bindings' => $bindings,
                'query_log' => $queryLog
            ];
            
        } catch (Exception $e) {
            DB::disableQueryLog();
            
            return [
                'error' => $e->getMessage(),
                'query' => $query,
                'bindings' => $bindings
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}