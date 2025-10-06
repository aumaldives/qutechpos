<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'response_time',
        'status',
        'attempt_number',
        'scheduled_at',
        'delivered_at',
        'error_message'
    ];
    
    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Delivery status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Delivery belongs to a webhook
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
    
    /**
     * Mark delivery as successful
     */
    public function markAsSuccess(int $responseStatus, string $responseBody, float $responseTime): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_status' => $responseStatus,
            'response_body' => substr($responseBody, 0, 1000), // Limit response body size
            'response_time' => $responseTime,
            'delivered_at' => now(),
            'error_message' => null
        ]);
    }
    
    /**
     * Mark delivery as failed
     */
    public function markAsFailed(string $errorMessage, int $responseStatus = null, string $responseBody = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'response_status' => $responseStatus,
            'response_body' => $responseBody ? substr($responseBody, 0, 1000) : null,
            'delivered_at' => now(),
            'error_message' => $errorMessage
        ]);
    }
    
    /**
     * Check if delivery can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED && 
               $this->attempt_number < $this->webhook->max_retries;
    }
    
    /**
     * Get next retry time
     */
    public function getNextRetryTime(): \DateTime
    {
        $delay = $this->webhook->retry_delay * pow(2, $this->attempt_number - 1); // Exponential backoff
        return now()->addSeconds($delay);
    }
    
    /**
     * Scope for failed deliveries that can be retried
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
                    ->where('scheduled_at', '<=', now())
                    ->whereRaw('attempt_number < (SELECT max_retries FROM webhooks WHERE webhooks.id = webhook_deliveries.webhook_id)');
    }
    
    /**
     * Scope for pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('scheduled_at', '<=', now());
    }
}