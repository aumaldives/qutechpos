<?php

namespace Modules\Woocommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Woocommerce\Services\WooCommerceSyncService;
use Modules\Woocommerce\Entities\WoocommerceSyncQueue;
use Modules\Woocommerce\Events\SyncProgressUpdated;
use Exception;

class WooCommerceSyncJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    public $maxExceptions = 3;
    
    protected int $business_id;
    protected string $sync_type;
    protected array $entity_ids;
    protected array $options;
    
    public function __construct(
        int $business_id,
        string $sync_type,
        array $entity_ids = [],
        array $options = []
    ) {
        $this->business_id = $business_id;
        $this->sync_type = $sync_type;
        $this->entity_ids = $entity_ids;
        $this->options = $options;
        
        // Set queue based on sync type priority
        $this->onQueue($this->getQueueName($sync_type));
    }
    
    /**
     * Get unique job ID to prevent duplicate jobs
     */
    public function uniqueId(): string
    {
        $entity_hash = empty($this->entity_ids) ? 'all' : md5(implode(',', $this->entity_ids));
        return "woocommerce_sync_{$this->business_id}_{$this->sync_type}_{$entity_hash}";
    }
    
    /**
     * Execute the job
     */
    public function handle(WooCommerceSyncService $syncService): void
    {
        Log::info('WooCommerce sync job started', [
            'business_id' => $this->business_id,
            'sync_type' => $this->sync_type,
            'entity_count' => count($this->entity_ids),
            'job_id' => $this->job->getJobId()
        ]);
        
        DB::beginTransaction();
        
        try {
            // Broadcast sync started
            $this->broadcastProgress('started', 0);
            
            // Execute the sync
            $result = $syncService->syncWithProgress(
                $this->business_id,
                $this->sync_type,
                $this->entity_ids,
                array_merge($this->options, ['job_id' => $this->job->getJobId()])
            );
            
            // Commit transaction
            DB::commit();
            
            // Broadcast completion
            $this->broadcastProgress('completed', 100, $result);
            
            Log::info('WooCommerce sync job completed successfully', [
                'business_id' => $this->business_id,
                'sync_type' => $this->sync_type,
                'result' => $result
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            // Broadcast failure
            $this->broadcastProgress('failed', null, null, $e->getMessage());
            
            Log::error('WooCommerce sync job failed', [
                'business_id' => $this->business_id,
                'sync_type' => $this->sync_type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('WooCommerce sync job permanently failed', [
            'business_id' => $this->business_id,
            'sync_type' => $this->sync_type,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);
        
        // Mark any related queue items as permanently failed
        WoocommerceSyncQueue::where('business_id', $this->business_id)
            ->where('sync_type', $this->sync_type)
            ->whereIn('entity_id', $this->entity_ids)
            ->where('status', WoocommerceSyncQueue::STATUS_PROCESSING)
            ->update([
                'status' => WoocommerceSyncQueue::STATUS_FAILED,
                'processed_at' => now(),
                'error_message' => 'Job permanently failed: ' . $exception->getMessage(),
                'error_context' => [
                    'attempts' => $this->attempts(),
                    'job_class' => static::class,
                    'failed_at' => now()->toISOString()
                ]
            ]);
        
        // Broadcast final failure
        $this->broadcastProgress('permanently_failed', null, null, $exception->getMessage());
    }
    
    /**
     * Get appropriate queue name based on sync type
     */
    private function getQueueName(string $sync_type): string
    {
        $queueMapping = [
            'stock' => 'woocommerce-high',      // High priority - stock updates
            'orders' => 'woocommerce-high',     // High priority - order processing
            'products' => 'woocommerce-medium', // Medium priority - product sync
            'categories' => 'woocommerce-low',  // Low priority - category sync
            'customers' => 'woocommerce-low',   // Low priority - customer sync
            'attributes' => 'woocommerce-low'   // Low priority - attribute sync
        ];
        
        return $queueMapping[$sync_type] ?? 'woocommerce-default';
    }
    
    /**
     * Broadcast sync progress to real-time listeners
     */
    private function broadcastProgress(
        string $status, 
        ?int $percentage = null, 
        ?array $result = null, 
        ?string $error = null
    ): void {
        try {
            event(new SyncProgressUpdated([
                'business_id' => $this->business_id,
                'sync_type' => $this->sync_type,
                'status' => $status,
                'percentage' => $percentage,
                'result' => $result,
                'error' => $error,
                'timestamp' => now()->toISOString(),
                'job_id' => $this->job->getJobId() ?? null
            ]));
        } catch (Exception $e) {
            // Don't fail the job if broadcasting fails
            Log::warning('Failed to broadcast sync progress', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Calculate retry delay based on attempt number
     */
    public function retryAfter(): int
    {
        $attempt = $this->attempts();
        return $this->backoff[$attempt - 1] ?? 900; // Default to 15 minutes
    }
}