<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Business;
use App\User;
use Carbon\Carbon;

class WoocommerceSyncQueue extends Model
{
    protected $table = 'woocommerce_sync_queue';
    
    protected $fillable = [
        'business_id',
        'sync_type',
        'entity_type',
        'entity_id',
        'operation',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'priority',
        'scheduled_at',
        'started_at',
        'processed_at',
        'error_message',
        'error_context',
        'batch_id',
        'woocommerce_id'
    ];
    
    protected $casts = [
        'payload' => 'array',
        'error_context' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'priority' => 'integer',
        'entity_id' => 'integer',
        'woocommerce_id' => 'integer'
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    const OPERATION_CREATE = 'create';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    
    const SYNC_TYPE_CATEGORIES = 'categories';
    const SYNC_TYPE_PRODUCTS = 'products';
    const SYNC_TYPE_ORDERS = 'orders';
    const SYNC_TYPE_CUSTOMERS = 'customers';
    const SYNC_TYPE_STOCK = 'stock';
    const SYNC_TYPE_ATTRIBUTES = 'attributes';
    
    /**
     * Business relationship
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
    
    /**
     * Scope for pending items
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    /**
     * Scope for failed items
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
    
    /**
     * Scope for completed items
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
    
    /**
     * Scope for ready to process items
     */
    public function scopeReadyToProcess($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('scheduled_at', '<=', now())
                    ->where('attempts', '<', $query->getModel()->max_attempts);
    }
    
    /**
     * Scope for business
     */
    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }
    
    /**
     * Scope for batch
     */
    public function scopeForBatch($query, string $batch_id)
    {
        return $query->where('batch_id', $batch_id);
    }
    
    /**
     * Scope for entity
     */
    public function scopeForEntity($query, string $entity_type, int $entity_id)
    {
        return $query->where('entity_type', $entity_type)
                    ->where('entity_id', $entity_id);
    }
    
    /**
     * Check if item can be retried
     */
    public function canRetry(): bool
    {
        return $this->attempts < $this->max_attempts && 
               in_array($this->status, [self::STATUS_FAILED, self::STATUS_PENDING]);
    }
    
    /**
     * Check if item is expired
     */
    public function isExpired(): bool
    {
        return $this->scheduled_at->addHours(24)->isPast();
    }
    
    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'attempts' => $this->attempts + 1
        ]);
    }
    
    /**
     * Mark as completed
     */
    public function markAsCompleted(int $woocommerce_id = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'woocommerce_id' => $woocommerce_id,
            'error_message' => null,
            'error_context' => null
        ]);
    }
    
    /**
     * Mark as failed
     */
    public function markAsFailed(string $error_message, array $error_context = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processed_at' => now(),
            'error_message' => $error_message,
            'error_context' => $error_context
        ]);
    }
    
    /**
     * Retry the sync item
     */
    public function retry(int $delay_minutes = 0): void
    {
        if (!$this->canRetry()) {
            return;
        }
        
        $this->update([
            'status' => self::STATUS_PENDING,
            'scheduled_at' => now()->addMinutes($delay_minutes),
            'started_at' => null,
            'processed_at' => null,
            'error_message' => null,
            'error_context' => null
        ]);
    }
    
    /**
     * Get processing duration
     */
    public function getProcessingDuration(): ?int
    {
        if (!$this->started_at || !$this->processed_at) {
            return null;
        }
        
        return $this->started_at->diffInSeconds($this->processed_at);
    }
    
    /**
     * Create a new sync queue item
     */
    public static function createSyncItem(
        int $business_id,
        string $sync_type,
        string $entity_type,
        int $entity_id,
        string $operation,
        array $payload,
        array $options = []
    ): self {
        return self::create([
            'business_id' => $business_id,
            'sync_type' => $sync_type,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'operation' => $operation,
            'payload' => $payload,
            'priority' => $options['priority'] ?? 0,
            'scheduled_at' => $options['scheduled_at'] ?? now(),
            'batch_id' => $options['batch_id'] ?? null,
            'max_attempts' => $options['max_attempts'] ?? 3
        ]);
    }
    
    /**
     * Get queue statistics for a business
     */
    public static function getBusinessStats(int $business_id): array
    {
        $stats = self::where('business_id', $business_id)
            ->selectRaw('
                status,
                COUNT(*) as count,
                AVG(attempts) as avg_attempts,
                MAX(created_at) as last_created
            ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
            
        return [
            'pending' => $stats[self::STATUS_PENDING]->count ?? 0,
            'processing' => $stats[self::STATUS_PROCESSING]->count ?? 0,
            'completed' => $stats[self::STATUS_COMPLETED]->count ?? 0,
            'failed' => $stats[self::STATUS_FAILED]->count ?? 0,
            'cancelled' => $stats[self::STATUS_CANCELLED]->count ?? 0,
            'total' => $stats->sum('count'),
            'avg_attempts' => $stats->avg('avg_attempts') ?? 0,
            'last_activity' => $stats->max('last_created')
        ];
    }
}