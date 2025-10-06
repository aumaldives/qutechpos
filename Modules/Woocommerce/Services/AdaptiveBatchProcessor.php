<?php

namespace Modules\Woocommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Woocommerce\Services\ModernWooCommerceClient;
use Exception;

class AdaptiveBatchProcessor
{
    protected ModernWooCommerceClient $client;
    protected int $business_id;
    protected array $performanceMetrics = [];
    protected array $batchSizes = [];
    
    // Default batch sizes for different operations
    protected array $defaultBatchSizes = [
        'products' => 25,
        'categories' => 50,
        'orders' => 20,
        'stock' => 100,
        'attributes' => 30
    ];
    
    // Performance thresholds
    protected array $performanceThresholds = [
        'response_time_ms' => [
            'excellent' => 500,   // < 500ms
            'good' => 1000,       // 500ms - 1s
            'acceptable' => 2000, // 1s - 2s
            'poor' => 5000,       // 2s - 5s
            'critical' => 10000   // > 5s
        ],
        'error_rate' => [
            'excellent' => 0.01,  // < 1%
            'good' => 0.05,       // 1% - 5%
            'acceptable' => 0.10, // 5% - 10%
            'poor' => 0.25,       // 10% - 25%
            'critical' => 1.0     // > 25%
        ]
    ];
    
    public function __construct(int $business_id)
    {
        $this->business_id = $business_id;
        $this->client = new ModernWooCommerceClient($business_id);
        $this->loadPerformanceHistory();
    }
    
    /**
     * Process items with adaptive batch sizing
     */
    public function processBatch(
        Collection $items,
        string $operation,
        callable $processor,
        array $options = []
    ): array {
        Log::info('Starting adaptive batch processing', [
            'business_id' => $this->business_id,
            'operation' => $operation,
            'total_items' => $items->count(),
            'initial_batch_size' => $this->getCurrentBatchSize($operation)
        ]);
        
        $results = [
            'total_items' => $items->count(),
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'batches_processed' => 0,
            'total_processing_time' => 0,
            'average_batch_time' => 0,
            'final_batch_size' => null,
            'performance_rating' => null,
            'errors' => []
        ];
        
        $batchNumber = 1;
        $totalStartTime = microtime(true);
        
        while ($items->isNotEmpty()) {
            $currentBatchSize = $this->getCurrentBatchSize($operation);
            $batch = $items->splice(0, $currentBatchSize);
            
            Log::info("Processing batch {$batchNumber}", [
                'business_id' => $this->business_id,
                'operation' => $operation,
                'batch_size' => $batch->count(),
                'remaining_items' => $items->count()
            ]);
            
            $batchResult = $this->processSingleBatch(
                $batch,
                $operation,
                $processor,
                $batchNumber,
                $options
            );
            
            // Update results
            $results['processed_items'] += $batchResult['processed'];
            $results['successful_items'] += $batchResult['successful'];
            $results['failed_items'] += $batchResult['failed'];
            $results['batches_processed']++;
            $results['errors'] = array_merge($results['errors'], $batchResult['errors']);
            
            // Adjust batch size based on performance
            $this->adjustBatchSize($operation, $batchResult);
            
            $batchNumber++;
            
            // Add small delay between batches to be API-friendly
            if ($items->isNotEmpty()) {
                usleep($this->getInterBatchDelay($batchResult['performance_rating']));
            }
        }
        
        $results['total_processing_time'] = round((microtime(true) - $totalStartTime) * 1000, 2);
        $results['average_batch_time'] = $results['batches_processed'] > 0 
            ? round($results['total_processing_time'] / $results['batches_processed'], 2) 
            : 0;
        $results['final_batch_size'] = $this->getCurrentBatchSize($operation);
        $results['performance_rating'] = $this->getOverallPerformanceRating($operation);
        
        // Save performance data for future optimizations
        $this->savePerformanceHistory($operation, $results);
        
        Log::info('Adaptive batch processing completed', [
            'business_id' => $this->business_id,
            'operation' => $operation,
            'results' => $results
        ]);
        
        return $results;
    }
    
    /**
     * Process a single batch with performance monitoring
     */
    protected function processSingleBatch(
        Collection $batch,
        string $operation,
        callable $processor,
        int $batchNumber,
        array $options
    ): array {
        $startTime = microtime(true);
        $result = [
            'batch_number' => $batchNumber,
            'batch_size' => $batch->count(),
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'processing_time_ms' => 0,
            'performance_rating' => 'unknown'
        ];
        
        try {
            // Execute the batch processing function
            $batchResults = $processor($batch, $options);
            
            // Extract results from processor response
            $result['processed'] = $batchResults['processed'] ?? $batch->count();
            $result['successful'] = $batchResults['successful'] ?? $batch->count();
            $result['failed'] = $batchResults['failed'] ?? 0;
            $result['errors'] = $batchResults['errors'] ?? [];
            
        } catch (Exception $e) {
            Log::error('Batch processing failed', [
                'business_id' => $this->business_id,
                'operation' => $operation,
                'batch_number' => $batchNumber,
                'batch_size' => $batch->count(),
                'error' => $e->getMessage()
            ]);
            
            $result['processed'] = $batch->count();
            $result['failed'] = $batch->count();
            $result['errors'][] = [
                'batch' => $batchNumber,
                'error' => $e->getMessage()
            ];
        }
        
        $result['processing_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        $result['performance_rating'] = $this->calculatePerformanceRating(
            $result['processing_time_ms'],
            $result['failed'],
            $result['processed']
        );
        
        // Record metrics for this batch
        $this->recordBatchMetrics($operation, $result);
        
        return $result;
    }
    
    /**
     * Adjust batch size based on performance feedback
     */
    protected function adjustBatchSize(string $operation, array $batchResult): void
    {
        $currentSize = $this->getCurrentBatchSize($operation);
        $performance = $batchResult['performance_rating'];
        $processingTime = $batchResult['processing_time_ms'];
        $errorRate = $batchResult['failed'] / max($batchResult['processed'], 1);
        
        $newSize = $currentSize;
        $adjustment = 0;
        
        // Adjust based on performance rating
        switch ($performance) {
            case 'excellent':
                // Increase batch size aggressively
                $adjustment = max(5, intval($currentSize * 0.3));
                break;
                
            case 'good':
                // Increase batch size moderately
                $adjustment = max(2, intval($currentSize * 0.2));
                break;
                
            case 'acceptable':
                // Small increase or maintain
                $adjustment = mt_rand(0, 2);
                break;
                
            case 'poor':
                // Decrease batch size
                $adjustment = -max(2, intval($currentSize * 0.3));
                break;
                
            case 'critical':
                // Decrease batch size significantly
                $adjustment = -max(5, intval($currentSize * 0.5));
                break;
        }
        
        // Apply bounds
        $newSize = max(1, min(200, $currentSize + $adjustment));
        
        // Don't make dramatic changes too frequently
        $maxChange = max(5, intval($currentSize * 0.5));
        $actualAdjustment = max(-$maxChange, min($maxChange, $adjustment));
        $newSize = $currentSize + $actualAdjustment;
        
        $this->setBatchSize($operation, $newSize);
        
        Log::info('Batch size adjusted', [
            'business_id' => $this->business_id,
            'operation' => $operation,
            'old_size' => $currentSize,
            'new_size' => $newSize,
            'adjustment' => $actualAdjustment,
            'performance' => $performance,
            'processing_time_ms' => $processingTime,
            'error_rate' => $errorRate
        ]);
    }
    
    /**
     * Calculate performance rating for a batch
     */
    protected function calculatePerformanceRating(float $processingTimeMs, int $failed, int $processed): string
    {
        $errorRate = $failed / max($processed, 1);
        
        // Check error rate thresholds first (more critical)
        if ($errorRate >= $this->performanceThresholds['error_rate']['critical']) {
            return 'critical';
        } elseif ($errorRate >= $this->performanceThresholds['error_rate']['poor']) {
            return 'poor';
        } elseif ($errorRate >= $this->performanceThresholds['error_rate']['acceptable']) {
            return 'acceptable';
        }
        
        // Check response time thresholds
        if ($processingTimeMs >= $this->performanceThresholds['response_time_ms']['critical']) {
            return 'critical';
        } elseif ($processingTimeMs >= $this->performanceThresholds['response_time_ms']['poor']) {
            return 'poor';
        } elseif ($processingTimeMs >= $this->performanceThresholds['response_time_ms']['acceptable']) {
            return 'acceptable';
        } elseif ($processingTimeMs >= $this->performanceThresholds['response_time_ms']['good']) {
            return 'good';
        } else {
            return 'excellent';
        }
    }
    
    /**
     * Get current batch size for operation
     */
    public function getCurrentBatchSize(string $operation): int
    {
        return $this->batchSizes[$operation] ?? $this->defaultBatchSizes[$operation] ?? 25;
    }
    
    /**
     * Set batch size for operation
     */
    protected function setBatchSize(string $operation, int $size): void
    {
        $this->batchSizes[$operation] = max(1, min(200, $size));
    }
    
    /**
     * Get inter-batch delay based on performance
     */
    protected function getInterBatchDelay(string $performanceRating): int
    {
        $delays = [
            'excellent' => 100000, // 0.1 seconds
            'good' => 250000,      // 0.25 seconds
            'acceptable' => 500000, // 0.5 seconds
            'poor' => 1000000,     // 1 second
            'critical' => 2000000  // 2 seconds
        ];
        
        return $delays[$performanceRating] ?? 500000;
    }
    
    /**
     * Record batch metrics for analysis
     */
    protected function recordBatchMetrics(string $operation, array $result): void
    {
        $key = "batch_metrics:{$this->business_id}:{$operation}";
        
        if (!isset($this->performanceMetrics[$operation])) {
            $this->performanceMetrics[$operation] = [];
        }
        
        $this->performanceMetrics[$operation][] = [
            'timestamp' => now()->timestamp,
            'batch_size' => $result['batch_size'],
            'processing_time_ms' => $result['processing_time_ms'],
            'success_rate' => $result['successful'] / max($result['processed'], 1),
            'error_rate' => $result['failed'] / max($result['processed'], 1),
            'performance_rating' => $result['performance_rating']
        ];
        
        // Keep only last 100 metrics per operation
        if (count($this->performanceMetrics[$operation]) > 100) {
            array_shift($this->performanceMetrics[$operation]);
        }
    }
    
    /**
     * Get overall performance rating for operation
     */
    protected function getOverallPerformanceRating(string $operation): string
    {
        if (!isset($this->performanceMetrics[$operation]) || empty($this->performanceMetrics[$operation])) {
            return 'unknown';
        }
        
        $recentMetrics = array_slice($this->performanceMetrics[$operation], -10);
        $ratings = array_column($recentMetrics, 'performance_rating');
        $ratingCounts = array_count_values($ratings);
        
        // Return the most common rating from recent batches
        arsort($ratingCounts);
        return array_key_first($ratingCounts);
    }
    
    /**
     * Load performance history from cache
     */
    protected function loadPerformanceHistory(): void
    {
        $cacheKey = "wc_batch_performance:{$this->business_id}";
        $cached = Cache::get($cacheKey, []);
        
        $this->batchSizes = $cached['batch_sizes'] ?? [];
        $this->performanceMetrics = $cached['metrics'] ?? [];
        
        Log::debug('Loaded performance history', [
            'business_id' => $this->business_id,
            'batch_sizes' => $this->batchSizes,
            'metrics_count' => array_map('count', $this->performanceMetrics)
        ]);
    }
    
    /**
     * Save performance history to cache
     */
    protected function savePerformanceHistory(string $operation, array $results): void
    {
        $cacheKey = "wc_batch_performance:{$this->business_id}";
        
        $data = [
            'batch_sizes' => $this->batchSizes,
            'metrics' => $this->performanceMetrics,
            'last_updated' => now()->timestamp,
            'last_operation' => $operation
        ];
        
        // Cache for 7 days
        Cache::put($cacheKey, $data, 7 * 24 * 60 * 60);
        
        Log::debug('Saved performance history', [
            'business_id' => $this->business_id,
            'operation' => $operation,
            'final_batch_size' => $results['final_batch_size']
        ]);
    }
    
    /**
     * Get performance statistics for monitoring
     */
    public function getPerformanceStats(string $operation = null): array
    {
        if ($operation) {
            return $this->getOperationStats($operation);
        }
        
        $stats = [];
        foreach ($this->performanceMetrics as $op => $metrics) {
            $stats[$op] = $this->getOperationStats($op);
        }
        
        return $stats;
    }
    
    /**
     * Get performance statistics for specific operation
     */
    protected function getOperationStats(string $operation): array
    {
        if (!isset($this->performanceMetrics[$operation])) {
            return [
                'operation' => $operation,
                'current_batch_size' => $this->getCurrentBatchSize($operation),
                'total_batches' => 0,
                'average_processing_time_ms' => 0,
                'average_success_rate' => 0,
                'average_error_rate' => 0,
                'performance_rating' => 'unknown'
            ];
        }
        
        $metrics = $this->performanceMetrics[$operation];
        $recentMetrics = array_slice($metrics, -20); // Last 20 batches
        
        return [
            'operation' => $operation,
            'current_batch_size' => $this->getCurrentBatchSize($operation),
            'total_batches' => count($metrics),
            'recent_batches' => count($recentMetrics),
            'average_processing_time_ms' => round(array_sum(array_column($recentMetrics, 'processing_time_ms')) / count($recentMetrics), 2),
            'average_success_rate' => round(array_sum(array_column($recentMetrics, 'success_rate')) / count($recentMetrics), 3),
            'average_error_rate' => round(array_sum(array_column($recentMetrics, 'error_rate')) / count($recentMetrics), 3),
            'performance_rating' => $this->getOverallPerformanceRating($operation),
            'batch_size_history' => array_column(array_slice($metrics, -10), 'batch_size'),
            'performance_trend' => array_column(array_slice($metrics, -10), 'performance_rating')
        ];
    }
    
    /**
     * Reset performance data for testing
     */
    public function resetPerformanceData(string $operation = null): void
    {
        if ($operation) {
            unset($this->performanceMetrics[$operation]);
            unset($this->batchSizes[$operation]);
        } else {
            $this->performanceMetrics = [];
            $this->batchSizes = [];
        }
        
        Cache::forget("wc_batch_performance:{$this->business_id}");
        
        Log::info('Performance data reset', [
            'business_id' => $this->business_id,
            'operation' => $operation ?? 'all'
        ]);
    }
}