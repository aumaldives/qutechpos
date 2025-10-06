<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Woocommerce\Entities\WoocommerceSyncSchedule;
use Modules\Woocommerce\Entities\WoocommerceSyncExecution;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Jobs\SyncLocationData;
use Carbon\Carbon;

class SyncQueueManager
{
    // Queue names by priority
    const QUEUE_CRITICAL = 'woocommerce-critical';
    const QUEUE_HIGH = 'woocommerce-high';
    const QUEUE_NORMAL = 'woocommerce-normal';
    const QUEUE_LOW = 'woocommerce-low';

    // Priority levels
    const PRIORITY_CRITICAL = 10;
    const PRIORITY_HIGH = 8;
    const PRIORITY_NORMAL = 5;
    const PRIORITY_LOW = 1;

    // Cache keys
    const CACHE_QUEUE_STATS = 'woocommerce_queue_stats';
    const CACHE_ACTIVE_SYNCS = 'woocommerce_active_syncs';
    const CACHE_LOCATION_LIMITS = 'woocommerce_location_limits';

    /**
     * Dispatch a sync job with intelligent queue management
     */
    public static function dispatch(
        WoocommerceLocationSetting $locationSetting, 
        string $syncType = 'all',
        int $priority = self::PRIORITY_NORMAL,
        array $options = []
    ) {
        $businessId = $locationSetting->business_id;
        $locationId = $locationSetting->location_id;

        // Check if sync is allowed
        if (!self::canDispatchSync($businessId, $locationId, $syncType)) {
            throw new \Exception('Sync dispatch not allowed due to limits or conflicts');
        }

        // Determine optimal queue and priority
        $queueInfo = self::determineOptimalQueue($businessId, $locationId, $priority, $syncType);
        
        // Create execution tracking
        $execution = WoocommerceSyncExecution::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'sync_type' => $syncType,
            'status' => 'queued',
            'priority' => $queueInfo['priority'],
            'metadata' => array_merge([
                'queue_name' => $queueInfo['queue'],
                'auto_priority' => $queueInfo['auto_priority'],
                'dispatch_reason' => $options['reason'] ?? 'manual',
                'optimal_queue_selected' => true
            ], $options['metadata'] ?? [])
        ]);

        // Dispatch to appropriate queue
        $job = SyncLocationData::dispatch($locationSetting, $syncType)
                              ->onQueue($queueInfo['queue']);

        // Apply delay if suggested
        if ($queueInfo['delay_seconds'] > 0) {
            $job->delay(Carbon::now()->addSeconds($queueInfo['delay_seconds']));
        }

        // Update execution with job info
        $execution->update([
            'status' => 'dispatched',
            'metadata' => array_merge($execution->metadata ?? [], [
                'delayed_seconds' => $queueInfo['delay_seconds']
            ])
        ]);

        // Track active sync
        self::trackActiveSyncStart($businessId, $locationId, $syncType, $execution->id);

        return $execution;
    }

    /**
     * Check if sync can be dispatched based on limits and conflicts
     */
    public static function canDispatchSync(int $businessId, int $locationId, string $syncType): bool
    {
        // Check location concurrent sync limit
        $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                    ->where('location_id', $locationId)
                                                    ->first();

        if (!$locationSetting || !$locationSetting->is_active) {
            return false;
        }

        $maxConcurrent = $locationSetting->max_concurrent_syncs ?? 2;
        $activeSyncs = self::getActiveSync($businessId, $locationId);

        if (count($activeSyncs) >= $maxConcurrent) {
            return false;
        }

        // Check for conflicting sync types
        if (self::hasConflictingSync($activeSyncs, $syncType)) {
            return false;
        }

        // Check business-wide limits
        $businessActiveSyncs = self::getActiveSync($businessId);
        $businessLimit = self::getBusinessSyncLimit($businessId);

        if (count($businessActiveSyncs) >= $businessLimit) {
            return false;
        }

        return true;
    }

    /**
     * Determine optimal queue and priority for a sync
     */
    private static function determineOptimalQueue(
        int $businessId, 
        int $locationId, 
        int $basePriority, 
        string $syncType
    ): array {
        $queueStats = self::getQueueStatistics();
        $locationSetting = WoocommerceLocationSetting::where('business_id', $businessId)
                                                    ->where('location_id', $locationId)
                                                    ->first();

        // Start with base priority
        $adjustedPriority = $basePriority;
        $delaySeconds = 0;
        $autoPriority = false;

        // Adjust priority based on sync type urgency
        $typeUrgency = [
            'orders' => 2,      // Orders are high priority
            'inventory' => 1,   // Inventory is medium-high priority
            'customers' => 0,   // Customers are normal priority
            'products' => 0,    // Products are normal priority
            'all' => 1          // Full sync is medium-high priority
        ];

        $adjustedPriority += $typeUrgency[$syncType] ?? 0;

        // Adjust based on recent error rate
        $errorRate = self::getLocationErrorRate($businessId, $locationId);
        if ($errorRate > 0.1) { // >10% error rate
            $adjustedPriority = max(1, $adjustedPriority - 2); // Lower priority for problematic locations
        }

        // Adjust based on queue congestion
        $queueCongestion = self::calculateQueueCongestion();
        if ($queueCongestion['critical'] < 5 && $adjustedPriority >= self::PRIORITY_HIGH) {
            // Promote to critical queue if not congested
            $adjustedPriority = self::PRIORITY_CRITICAL;
            $autoPriority = true;
        } elseif ($queueCongestion['high'] > 20) {
            // Demote if high queue is congested
            $adjustedPriority = min($adjustedPriority, self::PRIORITY_NORMAL);
        }

        // Apply delay for congested queues
        if ($queueCongestion['total'] > 50) {
            $delaySeconds = min(300, $queueCongestion['total'] * 2); // Max 5 minutes
        }

        // Use location preferred queue if specified
        $preferredQueue = $locationSetting->preferred_queue ?? null;
        if ($preferredQueue && self::isQueueAvailable($preferredQueue)) {
            $queueName = $preferredQueue;
        } else {
            $queueName = self::priorityToQueue($adjustedPriority);
        }

        return [
            'queue' => $queueName,
            'priority' => $adjustedPriority,
            'delay_seconds' => $delaySeconds,
            'auto_priority' => $autoPriority,
            'congestion_factor' => $queueCongestion['total']
        ];
    }

    /**
     * Get active syncs for business/location
     */
    private static function getActiveSync(int $businessId, int $locationId = null): array
    {
        $cacheKey = self::CACHE_ACTIVE_SYNCS . "_{$businessId}" . ($locationId ? "_{$locationId}" : '');
        
        return Cache::remember($cacheKey, 60, function () use ($businessId, $locationId) {
            $query = WoocommerceSyncExecution::forBusiness($businessId)
                                            ->where('status', 'processing');
            
            if ($locationId) {
                $query->forLocation($locationId);
            }

            return $query->with('schedule')
                         ->get()
                         ->map(function ($execution) {
                             return [
                                 'id' => $execution->id,
                                 'location_id' => $execution->location_id,
                                 'sync_type' => $execution->sync_type,
                                 'started_at' => $execution->started_at,
                                 'priority' => $execution->priority
                             ];
                         })
                         ->toArray();
        });
    }

    /**
     * Check for conflicting sync types
     */
    private static function hasConflictingSync(array $activeSyncs, string $newSyncType): bool
    {
        // Define conflicting sync types
        $conflicts = [
            'all' => ['all', 'products', 'orders', 'customers', 'inventory'], // Full sync conflicts with everything
            'products' => ['all', 'products', 'inventory'], // Products conflicts with inventory
            'inventory' => ['all', 'products', 'inventory'], // Inventory conflicts with products
            'orders' => ['all'], // Orders only conflict with full sync
            'customers' => ['all'] // Customers only conflict with full sync
        ];

        $conflictingTypes = $conflicts[$newSyncType] ?? [];

        foreach ($activeSyncs as $activeSync) {
            if (in_array($activeSync['sync_type'], $conflictingTypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get business-wide sync limit
     */
    private static function getBusinessSyncLimit(int $businessId): int
    {
        // Get from business settings or use default based on business size
        $locationCount = WoocommerceLocationSetting::where('business_id', $businessId)
                                                  ->where('is_active', true)
                                                  ->count();

        // Scale limit based on number of locations
        return max(5, $locationCount * 2);
    }

    /**
     * Get location error rate
     */
    private static function getLocationErrorRate(int $businessId, int $locationId): float
    {
        $recentExecutions = WoocommerceSyncExecution::forBusiness($businessId)
                                                   ->forLocation($locationId)
                                                   ->where('created_at', '>=', Carbon::now()->subHours(24))
                                                   ->count();

        if ($recentExecutions === 0) {
            return 0;
        }

        $failedExecutions = WoocommerceSyncExecution::forBusiness($businessId)
                                                   ->forLocation($locationId)
                                                   ->where('created_at', '>=', Carbon::now()->subHours(24))
                                                   ->where('status', 'failed')
                                                   ->count();

        return $failedExecutions / $recentExecutions;
    }

    /**
     * Calculate queue congestion levels
     */
    private static function calculateQueueCongestion(): array
    {
        $queues = [
            self::QUEUE_CRITICAL,
            self::QUEUE_HIGH,
            self::QUEUE_NORMAL,
            self::QUEUE_LOW
        ];

        $congestion = [
            'critical' => 0,
            'high' => 0,
            'normal' => 0,
            'low' => 0,
            'total' => 0
        ];

        foreach ($queues as $queue) {
            try {
                $size = Queue::size($queue);
                $queueKey = str_replace('woocommerce-', '', $queue);
                $congestion[$queueKey] = $size;
                $congestion['total'] += $size;
            } catch (\Exception $e) {
                // Queue might not exist, continue
            }
        }

        return $congestion;
    }

    /**
     * Convert priority to queue name
     */
    private static function priorityToQueue(int $priority): string
    {
        if ($priority >= self::PRIORITY_CRITICAL) {
            return self::QUEUE_CRITICAL;
        } elseif ($priority >= self::PRIORITY_HIGH) {
            return self::QUEUE_HIGH;
        } elseif ($priority >= self::PRIORITY_NORMAL) {
            return self::QUEUE_NORMAL;
        } else {
            return self::QUEUE_LOW;
        }
    }

    /**
     * Check if queue is available/healthy
     */
    private static function isQueueAvailable(string $queueName): bool
    {
        try {
            $size = Queue::size($queueName);
            return $size < 100; // Consider queue unavailable if too congested
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Track active sync start
     */
    private static function trackActiveSyncStart(int $businessId, int $locationId, string $syncType, int $executionId): void
    {
        $cacheKey = self::CACHE_ACTIVE_SYNCS . "_{$businessId}_{$locationId}";
        $activeSyncs = Cache::get($cacheKey, []);
        
        $activeSyncs[] = [
            'execution_id' => $executionId,
            'sync_type' => $syncType,
            'started_at' => Carbon::now()->toISOString()
        ];

        Cache::put($cacheKey, $activeSyncs, 3600); // 1 hour
    }

    /**
     * Track active sync completion
     */
    public static function trackActiveSyncEnd(int $businessId, int $locationId, int $executionId): void
    {
        $cacheKey = self::CACHE_ACTIVE_SYNCS . "_{$businessId}_{$locationId}";
        $activeSyncs = Cache::get($cacheKey, []);
        
        $activeSyncs = array_filter($activeSyncs, function ($sync) use ($executionId) {
            return $sync['execution_id'] !== $executionId;
        });

        if (empty($activeSyncs)) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, array_values($activeSyncs), 3600);
        }

        // Also clear business-wide cache
        $businessCacheKey = self::CACHE_ACTIVE_SYNCS . "_{$businessId}";
        Cache::forget($businessCacheKey);
    }

    /**
     * Get queue statistics for monitoring
     */
    public static function getQueueStatistics(): array
    {
        return Cache::remember(self::CACHE_QUEUE_STATS, 30, function () {
            $queues = [
                'critical' => self::QUEUE_CRITICAL,
                'high' => self::QUEUE_HIGH,
                'normal' => self::QUEUE_NORMAL,
                'low' => self::QUEUE_LOW
            ];

            $stats = [
                'queues' => [],
                'total_pending' => 0,
                'total_processing' => 0,
                'last_updated' => Carbon::now()->toISOString()
            ];

            foreach ($queues as $level => $queueName) {
                try {
                    $size = Queue::size($queueName);
                    $stats['queues'][$level] = [
                        'name' => $queueName,
                        'pending' => $size,
                        'health' => $size < 20 ? 'healthy' : ($size < 50 ? 'busy' : 'congested')
                    ];
                    $stats['total_pending'] += $size;
                } catch (\Exception $e) {
                    $stats['queues'][$level] = [
                        'name' => $queueName,
                        'pending' => 0,
                        'health' => 'unavailable',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Get processing count from active executions
            $processing = WoocommerceSyncExecution::where('status', 'processing')->count();
            $stats['total_processing'] = $processing;

            return $stats;
        });
    }

    /**
     * Clear all queue-related caches
     */
    public static function clearCaches(): void
    {
        $cacheKeys = [
            self::CACHE_QUEUE_STATS,
            self::CACHE_ACTIVE_SYNCS . '_*',
            self::CACHE_LOCATION_LIMITS . '_*'
        ];

        foreach ($cacheKeys as $key) {
            if (str_contains($key, '*')) {
                // Use Redis pattern matching if available
                try {
                    $redis = Cache::store('redis')->getRedis();
                    $keys = $redis->keys(str_replace('*', '*', $key));
                    foreach ($keys as $redisKey) {
                        Cache::forget($redisKey);
                    }
                } catch (\Exception $e) {
                    // Fallback for non-Redis caches
                }
            } else {
                Cache::forget($key);
            }
        }
    }

    /**
     * Get optimal sync times for a location
     */
    public static function getOptimalSyncTimes(int $businessId, int $locationId): array
    {
        // Analyze historical execution times and success rates
        $executions = WoocommerceSyncExecution::forBusiness($businessId)
                                             ->forLocation($locationId)
                                             ->where('created_at', '>=', Carbon::now()->subDays(30))
                                             ->where('status', 'completed')
                                             ->get()
                                             ->groupBy(function ($execution) {
                                                 return $execution->started_at->hour;
                                             });

        $optimalHours = [];
        
        foreach ($executions as $hour => $hourlyExecutions) {
            $successRate = $hourlyExecutions->where('status', 'completed')->count() / $hourlyExecutions->count();
            $avgDuration = $hourlyExecutions->avg('duration_seconds');
            
            if ($successRate >= 0.9 && $avgDuration <= 3600) { // 90% success, under 1 hour
                $optimalHours[] = [
                    'hour' => $hour,
                    'success_rate' => round($successRate * 100, 1),
                    'avg_duration' => $avgDuration,
                    'execution_count' => $hourlyExecutions->count()
                ];
            }
        }

        // Sort by success rate and duration
        usort($optimalHours, function ($a, $b) {
            if ($a['success_rate'] == $b['success_rate']) {
                return $a['avg_duration'] <=> $b['avg_duration'];
            }
            return $b['success_rate'] <=> $a['success_rate'];
        });

        return array_slice($optimalHours, 0, 6); // Return top 6 optimal hours
    }
}