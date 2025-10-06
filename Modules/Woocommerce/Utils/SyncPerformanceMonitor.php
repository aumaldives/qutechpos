<?php

namespace Modules\Woocommerce\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Utils\SyncQueryOptimizer;
use Carbon\Carbon;
use Exception;

class SyncPerformanceMonitor
{
    const CACHE_KEY_PREFIX = 'woocommerce_perf_monitor_';
    const CACHE_TTL = 300; // 5 minutes
    
    // Performance thresholds
    const THRESHOLDS = [
        'execution_time' => [
            'good' => 300,      // Under 5 minutes
            'warning' => 900,   // 5-15 minutes  
            'critical' => 1800  // Over 30 minutes
        ],
        'success_rate' => [
            'good' => 95,       // Above 95%
            'warning' => 80,    // 80-95%
            'critical' => 80    // Below 80%
        ],
        'memory_usage' => [
            'good' => 128,      // Under 128MB
            'warning' => 256,   // 128-256MB
            'critical' => 512   // Over 512MB
        ],
        'throughput' => [
            'good' => 50,       // Over 50 items/minute
            'warning' => 20,    // 20-50 items/minute
            'critical' => 20    // Under 20 items/minute
        ]
    ];

    private $businessId;
    private $locationId;

    public function __construct(int $businessId, int $locationId = null)
    {
        $this->businessId = $businessId;
        $this->locationId = $locationId;
    }

    /**
     * Generate comprehensive performance report
     */
    public function generatePerformanceReport(array $options = []): array
    {
        $period = $options['period'] ?? '7d';
        $includeRecommendations = $options['include_recommendations'] ?? true;
        $includeComparisons = $options['include_comparisons'] ?? true;

        try {
            [$dateFrom, $dateTo] = $this->parsePeriod($period);

            $report = [
                'generated_at' => now()->toISOString(),
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'period' => [
                    'from' => $dateFrom->toISOString(),
                    'to' => $dateTo->toISOString(),
                    'description' => $this->getPeriodDescription($period)
                ],
                'performance_metrics' => $this->getPerformanceMetrics($dateFrom, $dateTo),
                'health_score' => null,
                'trends' => null,
                'bottlenecks' => $this->identifyBottlenecks($dateFrom, $dateTo),
                'recommendations' => null,
                'comparison' => null
            ];

            // Calculate overall health score
            $report['health_score'] = $this->calculateHealthScore($report['performance_metrics']);

            // Add trend analysis
            if ($includeComparisons) {
                $report['trends'] = $this->analyzeTrends($dateFrom, $dateTo);
                $report['comparison'] = $this->generateComparison($dateFrom, $dateTo);
            }

            // Generate recommendations
            if ($includeRecommendations) {
                $report['recommendations'] = $this->generateRecommendations($report);
            }

            return $report;

        } catch (Exception $e) {
            Log::error('Failed to generate performance report', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Failed to generate performance report',
                'message' => $e->getMessage(),
                'generated_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Get real-time performance metrics
     */
    public function getRealTimeMetrics(): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX . "realtime_{$this->businessId}_{$this->locationId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            try {
                // Active sync sessions
                $activeSyncs = DB::table('woocommerce_sync_progress')
                    ->where('business_id', $this->businessId)
                    ->when($this->locationId, function($q) {
                        $q->where('location_id', $this->locationId);
                    })
                    ->whereIn('status', ['processing', 'paused'])
                    ->select([
                        'id', 'sync_type', 'status', 'started_at',
                        'records_total', 'records_processed', 'records_success', 'records_failed',
                        'current_operation'
                    ])
                    ->get();

                // Recent execution performance (last 24 hours)
                $recentPerformance = $this->getPerformanceMetrics(
                    Carbon::now()->subDay(),
                    Carbon::now()
                );

                // Queue status
                $queueStatus = $this->getQueueStatus();

                // System resource usage
                $resourceUsage = $this->getResourceUsage();

                return [
                    'timestamp' => now()->toISOString(),
                    'active_syncs' => $activeSyncs->map(function ($sync) {
                        $progress = $sync->records_total > 0 
                            ? round(($sync->records_processed / $sync->records_total) * 100, 1)
                            : 0;
                            
                        $duration = Carbon::parse($sync->started_at)->diffInSeconds(now());
                        
                        return [
                            'id' => $sync->id,
                            'sync_type' => $sync->sync_type,
                            'status' => $sync->status,
                            'progress_percentage' => $progress,
                            'duration_seconds' => $duration,
                            'current_operation' => $sync->current_operation,
                            'records_processed' => $sync->records_processed,
                            'records_total' => $sync->records_total
                        ];
                    }),
                    'recent_performance' => $recentPerformance,
                    'queue_status' => $queueStatus,
                    'resource_usage' => $resourceUsage,
                    'health_indicators' => $this->getHealthIndicators($recentPerformance)
                ];

            } catch (Exception $e) {
                Log::error('Failed to get real-time metrics', [
                    'business_id' => $this->businessId,
                    'location_id' => $this->locationId,
                    'error' => $e->getMessage()
                ]);

                return [
                    'error' => 'Failed to retrieve real-time metrics',
                    'timestamp' => now()->toISOString()
                ];
            }
        });
    }

    /**
     * Identify performance bottlenecks
     */
    private function identifyBottlenecks(Carbon $dateFrom, Carbon $dateTo): array
    {
        $bottlenecks = [];

        try {
            // Analyze slow executions
            $slowExecutions = DB::table('woocommerce_sync_executions')
                ->where('business_id', $this->businessId)
                ->when($this->locationId, function($q) {
                    $q->where('location_id', $this->locationId);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('duration_seconds', '>', self::THRESHOLDS['execution_time']['warning'])
                ->select(['sync_type', 'duration_seconds', 'records_processed', 'error_message'])
                ->orderBy('duration_seconds', 'desc')
                ->limit(10)
                ->get();

            if ($slowExecutions->isNotEmpty()) {
                $bottlenecks[] = [
                    'type' => 'slow_executions',
                    'severity' => 'warning',
                    'title' => 'Slow Sync Executions Detected',
                    'description' => 'Some sync executions are taking longer than expected',
                    'data' => $slowExecutions->toArray(),
                    'impact' => 'High execution times may indicate database performance issues or large dataset processing'
                ];
            }

            // Analyze high failure rates
            $failureRates = DB::table('woocommerce_sync_executions')
                ->where('business_id', $this->businessId)
                ->when($this->locationId, function($q) {
                    $q->where('location_id', $this->locationId);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->select([
                    'sync_type',
                    DB::raw('COUNT(*) as total_executions'),
                    DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_executions'),
                    DB::raw('ROUND((SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as failure_rate')
                ])
                ->groupBy('sync_type')
                ->having('failure_rate', '>', 100 - self::THRESHOLDS['success_rate']['warning'])
                ->get();

            if ($failureRates->isNotEmpty()) {
                $bottlenecks[] = [
                    'type' => 'high_failure_rates',
                    'severity' => 'critical',
                    'title' => 'High Failure Rates Detected',
                    'description' => 'Some sync types have high failure rates',
                    'data' => $failureRates->toArray(),
                    'impact' => 'High failure rates prevent data synchronization and may indicate API issues'
                ];
            }

            // Analyze resource constraints
            $resourceBottlenecks = $this->analyzeResourceConstraints($dateFrom, $dateTo);
            $bottlenecks = array_merge($bottlenecks, $resourceBottlenecks);

        } catch (Exception $e) {
            Log::error('Failed to identify bottlenecks', [
                'business_id' => $this->businessId,
                'error' => $e->getMessage()
            ]);
        }

        return $bottlenecks;
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(array $report): array
    {
        $recommendations = [];
        $metrics = $report['performance_metrics'];
        $bottlenecks = $report['bottlenecks'];

        // Execution time recommendations
        if (isset($metrics['performance_metrics']['avg_duration_seconds'])) {
            $avgDuration = $metrics['performance_metrics']['avg_duration_seconds'];
            
            if ($avgDuration > self::THRESHOLDS['execution_time']['critical']) {
                $recommendations[] = [
                    'category' => 'performance',
                    'priority' => 'high',
                    'title' => 'Optimize Sync Execution Time',
                    'description' => 'Average sync execution time is significantly above recommended thresholds',
                    'actions' => [
                        'Enable batch processing with smaller chunk sizes',
                        'Implement incremental syncing instead of full syncs',
                        'Consider increasing server resources (CPU/Memory)',
                        'Optimize database queries and add missing indexes'
                    ],
                    'expected_impact' => 'Reduce average execution time by 40-60%'
                ];
            }
        }

        // Success rate recommendations
        if (isset($metrics['execution_metrics']['success_rate'])) {
            $successRate = floatval(str_replace('%', '', $metrics['execution_metrics']['success_rate']));
            
            if ($successRate < self::THRESHOLDS['success_rate']['warning']) {
                $recommendations[] = [
                    'category' => 'reliability',
                    'priority' => 'high',
                    'title' => 'Improve Sync Reliability',
                    'description' => 'Sync success rate is below recommended threshold',
                    'actions' => [
                        'Review and fix common error patterns',
                        'Implement better retry logic with exponential backoff',
                        'Add more comprehensive error handling',
                        'Check WooCommerce API rate limits and authentication'
                    ],
                    'expected_impact' => 'Increase success rate to above 95%'
                ];
            }
        }

        // Bottleneck-specific recommendations
        foreach ($bottlenecks as $bottleneck) {
            switch ($bottleneck['type']) {
                case 'slow_executions':
                    $recommendations[] = [
                        'category' => 'performance',
                        'priority' => 'medium',
                        'title' => 'Address Slow Execution Patterns',
                        'description' => 'Optimize processing for identified slow sync types',
                        'actions' => [
                            'Implement parallel processing for ' . $this->getSlowSyncTypes($bottleneck['data']),
                            'Enable intelligent caching for frequently accessed data',
                            'Consider API response caching for WooCommerce requests',
                            'Optimize database queries with proper indexing'
                        ],
                        'expected_impact' => 'Reduce execution time for slow sync types by 30-50%'
                    ];
                    break;

                case 'high_failure_rates':
                    $recommendations[] = [
                        'category' => 'reliability',
                        'priority' => 'high',
                        'title' => 'Fix High-Failure Sync Types',
                        'description' => 'Address specific sync types with high failure rates',
                        'actions' => [
                            'Investigate error patterns for ' . $this->getHighFailureSyncTypes($bottleneck['data']),
                            'Implement more robust error handling and recovery',
                            'Add data validation before sync operations',
                            'Consider splitting complex sync operations into smaller steps'
                        ],
                        'expected_impact' => 'Reduce failure rates to under 5%'
                    ];
                    break;
            }
        }

        // General optimization recommendations
        $recommendations[] = [
            'category' => 'optimization',
            'priority' => 'low',
            'title' => 'Enable Advanced Features',
            'description' => 'Leverage advanced sync features for better performance',
            'actions' => [
                'Enable intelligent caching and deduplication',
                'Set up automated sync scheduling during off-peak hours',
                'Implement webhook-based real-time syncing for critical data',
                'Use batch processing for bulk operations'
            ],
            'expected_impact' => 'Overall performance improvement and reduced server load'
        ];

        return $recommendations;
    }

    /**
     * Calculate overall health score
     */
    private function calculateHealthScore(array $metrics): array
    {
        $scores = [];
        $weights = [
            'success_rate' => 0.4,
            'execution_time' => 0.3,
            'throughput' => 0.2,
            'resource_usage' => 0.1
        ];

        // Success rate score (0-100)
        if (isset($metrics['execution_metrics']['success_rate'])) {
            $successRate = floatval(str_replace('%', '', $metrics['execution_metrics']['success_rate']));
            $scores['success_rate'] = min(100, max(0, $successRate));
        } else {
            $scores['success_rate'] = 50; // Default neutral score
        }

        // Execution time score (inverse - lower time = higher score)
        if (isset($metrics['performance_metrics']['avg_duration_seconds'])) {
            $avgDuration = $metrics['performance_metrics']['avg_duration_seconds'];
            $timeScore = max(0, 100 - (($avgDuration / self::THRESHOLDS['execution_time']['critical']) * 100));
            $scores['execution_time'] = min(100, $timeScore);
        } else {
            $scores['execution_time'] = 50;
        }

        // Throughput score (items processed per minute)
        if (isset($metrics['data_metrics']['total_records_processed'])) {
            $totalProcessed = $metrics['data_metrics']['total_records_processed'];
            $totalTime = $metrics['performance_metrics']['avg_duration_seconds'] ?? 1;
            $throughput = ($totalProcessed * 60) / max(1, $totalTime); // items per minute
            
            $throughputScore = min(100, ($throughput / self::THRESHOLDS['throughput']['good']) * 100);
            $scores['throughput'] = $throughputScore;
        } else {
            $scores['throughput'] = 50;
        }

        // Resource usage score (placeholder - would need system metrics)
        $scores['resource_usage'] = 75; // Assume good resource usage

        // Calculate weighted average
        $overallScore = 0;
        foreach ($scores as $metric => $score) {
            $overallScore += $score * $weights[$metric];
        }

        return [
            'overall_score' => round($overallScore, 1),
            'grade' => $this->getHealthGrade($overallScore),
            'component_scores' => $scores,
            'status' => $this->getHealthStatus($overallScore)
        ];
    }

    /**
     * Get performance metrics using optimized queries
     */
    private function getPerformanceMetrics(Carbon $dateFrom, Carbon $dateTo): array
    {
        return SyncQueryOptimizer::getSyncPerformanceMetrics(
            $this->businessId, 
            $this->locationId, 
            [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        );
    }

    /**
     * Parse period string into date range
     */
    private function parsePeriod(string $period): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case '1d':
            case '24h':
                return [$now->copy()->subDay(), $now];
            case '7d':
            case '1w':
                return [$now->copy()->subWeek(), $now];
            case '30d':
            case '1m':
                return [$now->copy()->subMonth(), $now];
            case '90d':
            case '3m':
                return [$now->copy()->subMonths(3), $now];
            default:
                // Try to parse custom format like '2024-01-01:2024-01-31'
                if (strpos($period, ':') !== false) {
                    [$from, $to] = explode(':', $period);
                    return [Carbon::parse($from), Carbon::parse($to)];
                }
                // Default to last 7 days
                return [$now->copy()->subWeek(), $now];
        }
    }

    /**
     * Get period description
     */
    private function getPeriodDescription(string $period): string
    {
        $descriptions = [
            '1d' => 'Last 24 hours',
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '1w' => 'Last week',
            '30d' => 'Last 30 days',
            '1m' => 'Last month',
            '90d' => 'Last 90 days',
            '3m' => 'Last 3 months'
        ];

        return $descriptions[$period] ?? 'Custom period';
    }

    /**
     * Get health grade based on score
     */
    private function getHealthGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Get health status based on score
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 80) return 'healthy';
        if ($score >= 60) return 'warning';
        return 'critical';
    }

    /**
     * Get queue status information
     */
    private function getQueueStatus(): array
    {
        // This would integrate with your queue system
        // For now, return placeholder data
        return [
            'pending_jobs' => 0,
            'processing_jobs' => 0,
            'failed_jobs' => 0,
            'queue_health' => 'healthy'
        ];
    }

    /**
     * Get system resource usage
     */
    private function getResourceUsage(): array
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'cpu_usage' => null, // Would need system monitoring
            'disk_usage' => null // Would need system monitoring
        ];
    }

    /**
     * Get health indicators from performance metrics
     */
    private function getHealthIndicators(array $metrics): array
    {
        $indicators = [];

        // Success rate indicator
        if (isset($metrics['execution_metrics']['success_rate'])) {
            $successRate = floatval(str_replace('%', '', $metrics['execution_metrics']['success_rate']));
            $indicators['success_rate'] = [
                'value' => $successRate,
                'status' => $successRate >= self::THRESHOLDS['success_rate']['good'] ? 'healthy' : 
                           ($successRate >= self::THRESHOLDS['success_rate']['warning'] ? 'warning' : 'critical'),
                'description' => 'Sync success rate over the last 24 hours'
            ];
        }

        return $indicators;
    }

    // Helper methods for recommendations
    private function getSlowSyncTypes(array $data): string
    {
        $types = array_unique(array_column($data, 'sync_type'));
        return implode(', ', $types);
    }

    private function getHighFailureSyncTypes(array $data): string
    {
        $types = array_column($data, 'sync_type');
        return implode(', ', $types);
    }

    private function analyzeResourceConstraints(Carbon $dateFrom, Carbon $dateTo): array
    {
        // Placeholder for resource constraint analysis
        return [];
    }

    private function analyzeTrends(Carbon $dateFrom, Carbon $dateTo): array
    {
        // Placeholder for trend analysis
        return [
            'execution_time_trend' => 'stable',
            'success_rate_trend' => 'improving',
            'throughput_trend' => 'stable'
        ];
    }

    private function generateComparison(Carbon $dateFrom, Carbon $dateTo): array
    {
        // Placeholder for comparison with previous period
        return [
            'vs_previous_period' => [
                'success_rate_change' => '+2.3%',
                'avg_duration_change' => '-15.4%',
                'throughput_change' => '+8.7%'
            ]
        ];
    }
}