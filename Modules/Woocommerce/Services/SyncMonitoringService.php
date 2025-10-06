<?php

namespace Modules\Woocommerce\Services;

use App\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Woocommerce\Entities\WoocommerceSyncQueue;
use Modules\Woocommerce\Entities\WoocommerceSyncConflict;
use Modules\Woocommerce\Entities\WoocommerceSyncLog;
use Modules\Woocommerce\Services\ModernWooCommerceClient;
use Modules\Woocommerce\Services\AdaptiveBatchProcessor;
use Carbon\Carbon;
use Exception;

class SyncMonitoringService
{
    protected int $business_id;
    protected ModernWooCommerceClient $client;
    
    public function __construct()
    {
        // Business ID will be set when needed
    }
    
    /**
     * Get comprehensive health status for a business
     */
    public function getHealthStatus(int $business_id): array
    {
        $this->business_id = $business_id;
        $this->client = new ModernWooCommerceClient($business_id);
        
        Log::info('Starting health check', ['business_id' => $business_id]);
        
        $health = [
            'business_id' => $business_id,
            'overall_status' => 'unknown',
            'overall_score' => 0,
            'last_updated' => now()->toISOString(),
            'checks' => [
                'api_connectivity' => $this->checkApiConnectivity(),
                'sync_queue_health' => $this->checkSyncQueueHealth(),
                'conflict_status' => $this->checkConflictStatus(),
                'performance_metrics' => $this->checkPerformanceMetrics(),
                'webhook_health' => $this->checkWebhookHealth(),
                'data_consistency' => $this->checkDataConsistency(),
                'system_resources' => $this->checkSystemResources()
            ],
            'alerts' => [],
            'recommendations' => []
        ];
        
        // Calculate overall status and score
        $health = $this->calculateOverallHealth($health);
        
        // Generate alerts and recommendations
        $health = $this->generateAlerts($health);
        $health = $this->generateRecommendations($health);
        
        // Cache health status for 5 minutes
        Cache::put("wc_health_status:{$business_id}", $health, 300);
        
        Log::info('Health check completed', [
            'business_id' => $business_id,
            'overall_status' => $health['overall_status'],
            'overall_score' => $health['overall_score']
        ]);
        
        return $health;
    }
    
    /**
     * Check WooCommerce API connectivity
     */
    protected function checkApiConnectivity(): array
    {
        $check = [
            'name' => 'API Connectivity',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            $startTime = microtime(true);
            
            // Test connection
            $connectionTest = $this->client->testConnection();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($connectionTest['connected']) {
                $check['status'] = 'healthy';
                $check['score'] = $this->calculateConnectivityScore($responseTime, $connectionTest);
                $check['message'] = "API connected successfully ({$responseTime}ms)";
                $check['details'] = [
                    'response_time_ms' => $responseTime,
                    'api_version' => $connectionTest['api_version'] ?? 'Unknown',
                    'wp_version' => $connectionTest['wp_version'] ?? 'Unknown',
                    'php_version' => $connectionTest['php_version'] ?? 'Unknown'
                ];
            } else {
                $check['status'] = 'critical';
                $check['score'] = 0;
                $check['message'] = 'API connection failed: ' . ($connectionTest['error'] ?? 'Unknown error');
                $check['details'] = $connectionTest;
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'API connectivity check failed: ' . $e->getMessage();
            $check['details'] = ['error' => $e->getMessage()];
        }
        
        return $check;
    }
    
    /**
     * Check sync queue health
     */
    protected function checkSyncQueueHealth(): array
    {
        $check = [
            'name' => 'Sync Queue Health',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            $stats = WoocommerceSyncQueue::getBusinessStats($this->business_id);
            
            $check['details'] = $stats;
            
            // Calculate health based on queue metrics
            $totalItems = $stats['total'];
            $failedItems = $stats['failed'];
            $pendingItems = $stats['pending'];
            $processingItems = $stats['processing'];
            
            if ($totalItems == 0) {
                $check['status'] = 'healthy';
                $check['score'] = 100;
                $check['message'] = 'No items in sync queue';
            } else {
                $failureRate = $failedItems / $totalItems;
                $stalledItems = $this->getStalledQueueItems();
                
                if ($failureRate > 0.5 || $stalledItems > 10) {
                    $check['status'] = 'critical';
                    $check['score'] = 0;
                    $check['message'] = "High failure rate (" . ($failureRate*100) . "%) or stalled items ({$stalledItems})";
                } elseif ($failureRate > 0.2 || $stalledItems > 5) {
                    $check['status'] = 'warning';
                    $check['score'] = 40;
                    $check['message'] = "Moderate issues: {$failedItems} failed, {$stalledItems} stalled";
                } elseif ($pendingItems > 100) {
                    $check['status'] = 'warning';
                    $check['score'] = 60;
                    $check['message'] = "Large queue backlog: {$pendingItems} pending items";
                } else {
                    $check['status'] = 'healthy';
                    $check['score'] = 85;
                    $check['message'] = "Queue healthy: {$pendingItems} pending, {$failedItems} failed";
                }
                
                $check['details']['stalled_items'] = $stalledItems;
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'Queue health check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Check conflict resolution status
     */
    protected function checkConflictStatus(): array
    {
        $check = [
            'name' => 'Conflict Resolution',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            $stats = WoocommerceSyncConflict::getBusinessStats($this->business_id);
            
            $check['details'] = $stats;
            
            $totalConflicts = $stats['total'];
            $openConflicts = $stats['open'];
            $criticalConflicts = $stats['by_severity']['critical'] ?? 0;
            $highConflicts = $stats['by_severity']['high'] ?? 0;
            
            if ($totalConflicts == 0) {
                $check['status'] = 'healthy';
                $check['score'] = 100;
                $check['message'] = 'No conflicts detected';
            } elseif ($criticalConflicts > 0) {
                $check['status'] = 'critical';
                $check['score'] = 0;
                $check['message'] = "{$criticalConflicts} critical conflicts require immediate attention";
            } elseif ($highConflicts > 5 || $openConflicts > 20) {
                $check['status'] = 'warning';
                $check['score'] = 30;
                $check['message'] = "{$openConflicts} open conflicts ({$highConflicts} high priority)";
            } else {
                $check['status'] = 'healthy';
                $check['score'] = 80;
                $check['message'] = "{$openConflicts} minor conflicts, {$stats['resolved']} resolved";
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'Conflict status check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Check performance metrics
     */
    protected function checkPerformanceMetrics(): array
    {
        $check = [
            'name' => 'Performance Metrics',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            $batchProcessor = new AdaptiveBatchProcessor($this->business_id);
            $stats = $batchProcessor->getPerformanceStats();
            
            $check['details'] = $stats;
            
            if (empty($stats)) {
                $check['status'] = 'unknown';
                $check['score'] = 50;
                $check['message'] = 'No performance data available';
            } else {
                $overallScore = 0;
                $operationCount = 0;
                $criticalOperations = 0;
                $poorOperations = 0;
                
                foreach ($stats as $operation => $operationStats) {
                    $rating = $operationStats['performance_rating'];
                    $avgErrorRate = $operationStats['average_error_rate'];
                    
                    switch ($rating) {
                        case 'excellent':
                            $overallScore += 100;
                            break;
                        case 'good':
                            $overallScore += 80;
                            break;
                        case 'acceptable':
                            $overallScore += 60;
                            break;
                        case 'poor':
                            $overallScore += 30;
                            $poorOperations++;
                            break;
                        case 'critical':
                            $overallScore += 0;
                            $criticalOperations++;
                            break;
                    }
                    
                    $operationCount++;
                }
                
                $averageScore = $operationCount > 0 ? $overallScore / $operationCount : 50;
                
                if ($criticalOperations > 0) {
                    $check['status'] = 'critical';
                    $check['score'] = min(20, $averageScore);
                    $check['message'] = "{$criticalOperations} operations performing critically";
                } elseif ($poorOperations > 1) {
                    $check['status'] = 'warning';
                    $check['score'] = min(50, $averageScore);
                    $check['message'] = "{$poorOperations} operations performing poorly";
                } else {
                    $check['status'] = $averageScore >= 70 ? 'healthy' : 'warning';
                    $check['score'] = $averageScore;
                    $check['message'] = "Average performance score: {$averageScore}";
                }
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'Performance metrics check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Check webhook health
     */
    protected function checkWebhookHealth(): array
    {
        $check = [
            'name' => 'Webhook Health',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            // Check webhook configuration
            $business = Business::find($this->business_id);
            $hasWebhookSecrets = !empty($business->woocommerce_wh_oc_secret) ||
                               !empty($business->woocommerce_wh_ou_secret) ||
                               !empty($business->woocommerce_wh_od_secret) ||
                               !empty($business->woocommerce_wh_or_secret);
            
            // Check recent webhook activity
            $recentWebhooks = $this->getRecentWebhookActivity();
            $failedWebhooks = $this->getFailedWebhookCount();
            
            $check['details'] = [
                'webhook_secrets_configured' => $hasWebhookSecrets,
                'recent_webhooks_24h' => $recentWebhooks,
                'failed_webhooks_24h' => $failedWebhooks,
                'webhook_failure_rate' => $recentWebhooks > 0 ? $failedWebhooks / $recentWebhooks : 0
            ];
            
            if (!$hasWebhookSecrets) {
                $check['status'] = 'warning';
                $check['score'] = 40;
                $check['message'] = 'Webhook secrets not configured';
            } elseif ($failedWebhooks > $recentWebhooks * 0.5) {
                $check['status'] = 'critical';
                $check['score'] = 0;
                $check['message'] = "High webhook failure rate: {$failedWebhooks}/{$recentWebhooks}";
            } elseif ($failedWebhooks > $recentWebhooks * 0.1) {
                $check['status'] = 'warning';
                $check['score'] = 50;
                $check['message'] = "Some webhook failures: {$failedWebhooks}/{$recentWebhooks}";
            } else {
                $check['status'] = 'healthy';
                $check['score'] = 90;
                $check['message'] = "Webhooks working well: {$recentWebhooks} processed, {$failedWebhooks} failed";
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'Webhook health check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Check data consistency between POS and WooCommerce
     */
    protected function checkDataConsistency(): array
    {
        $check = [
            'name' => 'Data Consistency',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            // Sample checks on key data
            $productCount = DB::table('products')
                ->where('business_id', $this->business_id)
                ->whereNotNull('woocommerce_product_id')
                ->count();
                
            $categoryCount = DB::table('categories')
                ->where('business_id', $this->business_id)
                ->whereNotNull('woocommerce_cat_id')
                ->count();
                
            $orderCount = DB::table('transactions')
                ->where('business_id', $this->business_id)
                ->whereNotNull('woocommerce_order_id')
                ->count();
            
            // Check for orphaned records
            $orphanedProducts = $this->checkOrphanedRecords('products', 'woocommerce_product_id');
            $orphanedCategories = $this->checkOrphanedRecords('categories', 'woocommerce_cat_id');
            
            $check['details'] = [
                'synced_products' => $productCount,
                'synced_categories' => $categoryCount,
                'synced_orders' => $orderCount,
                'orphaned_products' => $orphanedProducts,
                'orphaned_categories' => $orphanedCategories
            ];
            
            $totalOrphaned = $orphanedProducts + $orphanedCategories;
            $totalSynced = $productCount + $categoryCount + $orderCount;
            
            if ($totalSynced == 0) {
                $check['status'] = 'unknown';
                $check['score'] = 50;
                $check['message'] = 'No synced data found';
            } elseif ($totalOrphaned > $totalSynced * 0.1) {
                $check['status'] = 'warning';
                $check['score'] = 40;
                $check['message'] = "High orphaned record count: {$totalOrphaned}";
            } elseif ($totalOrphaned > 0) {
                $check['status'] = 'healthy';
                $check['score'] = 75;
                $check['message'] = "Minor inconsistencies: {$totalOrphaned} orphaned records";
            } else {
                $check['status'] = 'healthy';
                $check['score'] = 95;
                $check['message'] = "Data consistency good: {$totalSynced} synced records";
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'Data consistency check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Check system resource usage
     */
    protected function checkSystemResources(): array
    {
        $check = [
            'name' => 'System Resources',
            'status' => 'unknown',
            'score' => 0,
            'message' => '',
            'details' => [],
            'last_checked' => now()->toISOString()
        ];
        
        try {
            // Check queue job counts
            $queueSize = DB::table('jobs')->count();
            $failedJobsCount = DB::table('failed_jobs')->count();
            
            // Check recent sync activity
            $recentSyncCount = WoocommerceSyncLog::where('business_id', $this->business_id)
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
                
            // Check memory usage (approximation)
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            
            $check['details'] = [
                'queue_jobs' => $queueSize,
                'failed_jobs' => $failedJobsCount,
                'recent_syncs_24h' => $recentSyncCount,
                'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2)
            ];
            
            if ($queueSize > 1000 || $failedJobsCount > 100) {
                $check['status'] = 'warning';
                $check['score'] = 40;
                $check['message'] = "High resource usage: {$queueSize} queued, {$failedJobsCount} failed jobs";
            } elseif ($queueSize > 500 || $failedJobsCount > 50) {
                $check['status'] = 'healthy';
                $check['score'] = 70;
                $check['message'] = "Moderate resource usage: {$queueSize} queued, {$failedJobsCount} failed jobs";
            } else {
                $check['status'] = 'healthy';
                $check['score'] = 90;
                $check['message'] = "Resource usage normal: {$queueSize} queued, {$failedJobsCount} failed jobs";
            }
            
        } catch (Exception $e) {
            $check['status'] = 'critical';
            $check['score'] = 0;
            $check['message'] = 'System resources check failed: ' . $e->getMessage();
        }
        
        return $check;
    }
    
    /**
     * Calculate overall health status and score
     */
    protected function calculateOverallHealth(array $health): array
    {
        $totalScore = 0;
        $checkCount = 0;
        $criticalIssues = 0;
        $warningIssues = 0;
        
        foreach ($health['checks'] as $check) {
            $totalScore += $check['score'];
            $checkCount++;
            
            if ($check['status'] === 'critical') {
                $criticalIssues++;
            } elseif ($check['status'] === 'warning') {
                $warningIssues++;
            }
        }
        
        $averageScore = $checkCount > 0 ? $totalScore / $checkCount : 0;
        
        // Determine overall status
        if ($criticalIssues > 0) {
            $health['overall_status'] = 'critical';
            $health['overall_score'] = min(25, $averageScore);
        } elseif ($warningIssues >= 3) {
            $health['overall_status'] = 'warning';
            $health['overall_score'] = min(60, $averageScore);
        } elseif ($warningIssues >= 1) {
            $health['overall_status'] = 'warning';
            $health['overall_score'] = min(80, $averageScore);
        } elseif ($averageScore >= 80) {
            $health['overall_status'] = 'healthy';
            $health['overall_score'] = $averageScore;
        } else {
            $health['overall_status'] = 'warning';
            $health['overall_score'] = $averageScore;
        }
        
        return $health;
    }
    
    /**
     * Generate alerts based on health checks
     */
    protected function generateAlerts(array $health): array
    {
        $alerts = [];
        
        foreach ($health['checks'] as $checkName => $check) {
            if ($check['status'] === 'critical') {
                $alerts[] = [
                    'type' => 'critical',
                    'title' => $check['name'] . ' Critical Issue',
                    'message' => $check['message'],
                    'action_required' => true,
                    'check' => $checkName
                ];
            } elseif ($check['status'] === 'warning' && $check['score'] < 50) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => $check['name'] . ' Warning',
                    'message' => $check['message'],
                    'action_required' => false,
                    'check' => $checkName
                ];
            }
        }
        
        $health['alerts'] = $alerts;
        return $health;
    }
    
    /**
     * Generate recommendations based on health status
     */
    protected function generateRecommendations(array $health): array
    {
        $recommendations = [];
        
        // API connectivity recommendations
        if ($health['checks']['api_connectivity']['status'] === 'critical') {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Fix API Connection',
                'description' => 'Check WooCommerce API credentials and store URL',
                'action' => 'Review API settings and test connection'
            ];
        }
        
        // Queue health recommendations
        if ($health['checks']['sync_queue_health']['score'] < 50) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Optimize Sync Queue',
                'description' => 'High failure rate or stalled items in sync queue',
                'action' => 'Review failed queue items and restart sync processes'
            ];
        }
        
        // Conflict resolution recommendations
        if ($health['checks']['conflict_status']['status'] === 'critical') {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Resolve Critical Conflicts',
                'description' => 'Critical data conflicts require immediate attention',
                'action' => 'Review and manually resolve critical conflicts'
            ];
        }
        
        // Performance recommendations
        if ($health['checks']['performance_metrics']['score'] < 60) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Improve Sync Performance',
                'description' => 'Sync operations are performing below optimal levels',
                'action' => 'Review performance metrics and optimize batch sizes'
            ];
        }
        
        $health['recommendations'] = $recommendations;
        return $health;
    }
    
    /**
     * Helper methods
     */
    protected function calculateConnectivityScore(float $responseTime, array $connectionTest): int
    {
        if ($responseTime < 500) return 100;
        if ($responseTime < 1000) return 90;
        if ($responseTime < 2000) return 75;
        if ($responseTime < 5000) return 50;
        return 25;
    }
    
    protected function getStalledQueueItems(): int
    {
        return WoocommerceSyncQueue::where('business_id', $this->business_id)
            ->where('status', WoocommerceSyncQueue::STATUS_PROCESSING)
            ->where('started_at', '<', now()->subHours(1))
            ->count();
    }
    
    protected function getRecentWebhookActivity(): int
    {
        // This would query webhook logs if they existed
        // For now, return a mock value
        return Cache::get("webhook_activity_24h:{$this->business_id}", 0);
    }
    
    protected function getFailedWebhookCount(): int
    {
        // This would query webhook failure logs
        // For now, return a mock value
        return Cache::get("webhook_failures_24h:{$this->business_id}", 0);
    }
    
    protected function checkOrphanedRecords(string $table, string $woocommerceIdField): int
    {
        // This would check if WooCommerce records still exist
        // For now, return 0 as we can't easily verify without API calls
        return 0;
    }
    
    /**
     * Get sync statistics for monitoring dashboard
     */
    public function getSyncStatistics(int $business_id, int $days = 7): array
    {
        $stats = [
            'business_id' => $business_id,
            'period_days' => $days,
            'generated_at' => now()->toISOString(),
            'sync_activity' => [],
            'performance_trends' => [],
            'error_analysis' => [],
            'recommendations' => []
        ];
        
        try {
            // Get sync activity over time
            $syncActivity = WoocommerceSyncLog::where('business_id', $business_id)
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(created_at) as date, sync_type, operation_type, COUNT(*) as count')
                ->groupBy(['date', 'sync_type', 'operation_type'])
                ->orderBy('date')
                ->get();
                
            $stats['sync_activity'] = $syncActivity->toArray();
            
            // Get queue statistics
            $queueStats = WoocommerceSyncQueue::where('business_id', $business_id)
                ->selectRaw('status, COUNT(*) as count, AVG(attempts) as avg_attempts')
                ->groupBy('status')
                ->get();
                
            $stats['queue_statistics'] = $queueStats->toArray();
            
            // Get conflict trends
            $conflictTrends = WoocommerceSyncConflict::where('business_id', $business_id)
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('DATE(created_at) as date, status, severity, COUNT(*) as count')
                ->groupBy(['date', 'status', 'severity'])
                ->orderBy('date')
                ->get();
                
            $stats['conflict_trends'] = $conflictTrends->toArray();
            
        } catch (Exception $e) {
            Log::error('Failed to generate sync statistics', [
                'business_id' => $business_id,
                'error' => $e->getMessage()
            ]);
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Get real-time monitoring data
     */
    public function getRealTimeStatus(int $business_id): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'business_id' => $business_id,
            'active_syncs' => $this->getActiveSyncCount($business_id),
            'queue_backlog' => $this->getQueueBacklog($business_id),
            'recent_errors' => $this->getRecentErrors($business_id),
            'api_health' => $this->getApiHealthSummary($business_id),
            'last_successful_sync' => $this->getLastSuccessfulSync($business_id)
        ];
    }
    
    protected function getActiveSyncCount(int $business_id): int
    {
        return WoocommerceSyncQueue::where('business_id', $business_id)
            ->where('status', WoocommerceSyncQueue::STATUS_PROCESSING)
            ->count();
    }
    
    protected function getQueueBacklog(int $business_id): int
    {
        return WoocommerceSyncQueue::where('business_id', $business_id)
            ->where('status', WoocommerceSyncQueue::STATUS_PENDING)
            ->count();
    }
    
    protected function getRecentErrors(int $business_id): array
    {
        return WoocommerceSyncQueue::where('business_id', $business_id)
            ->where('status', WoocommerceSyncQueue::STATUS_FAILED)
            ->where('updated_at', '>=', now()->subHours(1))
            ->limit(5)
            ->get(['id', 'sync_type', 'entity_type', 'error_message', 'updated_at'])
            ->toArray();
    }
    
    protected function getApiHealthSummary(int $business_id): string
    {
        $cached = Cache::get("wc_health_status:{$business_id}");
        return $cached['checks']['api_connectivity']['status'] ?? 'unknown';
    }
    
    protected function getLastSuccessfulSync(int $business_id): ?string
    {
        $lastSync = WoocommerceSyncLog::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->first();
            
        return $lastSync ? $lastSync->created_at->toISOString() : null;
    }
}