<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'business_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'timeout',
        'max_retries',
        'retry_delay',
        'last_triggered_at',
        'last_response_status',
        'failure_count',
        'metadata'
    ];
    
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    protected $hidden = [
        'secret'
    ];
    
    /**
     * Available webhook events
     */
    const AVAILABLE_EVENTS = [
        // Product Events
        'product.created',
        'product.updated', 
        'product.deleted',
        'product.stock_updated',
        
        // Contact Events
        'contact.created',
        'contact.updated',
        'contact.deleted',
        
        // Transaction Events
        'transaction.created',
        'transaction.updated',
        'transaction.deleted',
        'transaction.payment_added',
        'transaction.payment_updated',
        'transaction.status_changed',
        
        // Sale Events
        'sale.created',
        'sale.completed',
        'sale.cancelled',
        'sale.refunded',
        
        // Purchase Events
        'purchase.created',
        'purchase.received',
        'purchase.cancelled',
        
        // Stock Events
        'stock.low_alert',
        'stock.adjustment',
        'stock.transfer',
        
        // Business Events
        'business.settings_updated',
        'business.location_created',
        'business.location_updated'
    ];
    
    /**
     * Webhook belongs to a business
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    
    /**
     * Webhook deliveries
     */
    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }
    
    /**
     * Check if webhook is subscribed to a specific event
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }
    
    /**
     * Generate webhook secret
     */
    public static function generateSecret(): string
    {
        return 'wh_' . bin2hex(random_bytes(32));
    }
    
    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Get webhook health status
     */
    public function getHealthStatus(): array
    {
        $successRate = 0;
        $avgResponseTime = 0;
        
        if ($this->deliveries()->count() > 0) {
            $totalDeliveries = $this->deliveries()->count();
            $successfulDeliveries = $this->deliveries()->where('status', 'success')->count();
            $successRate = ($successfulDeliveries / $totalDeliveries) * 100;
            
            $avgResponseTime = $this->deliveries()
                ->where('status', 'success')
                ->where('response_time', '>', 0)
                ->avg('response_time') ?? 0;
        }
        
        return [
            'success_rate' => round($successRate, 2),
            'avg_response_time' => round($avgResponseTime, 2),
            'failure_count' => $this->failure_count,
            'last_triggered' => $this->last_triggered_at ? $this->last_triggered_at->toISOString() : null,
            'status' => $this->getOverallStatus()
        ];
    }
    
    /**
     * Get overall webhook status
     */
    public function getOverallStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->failure_count >= $this->max_retries) {
            return 'failed';
        }
        
        if ($this->failure_count > 0) {
            return 'degraded';
        }
        
        return 'healthy';
    }
    
    /**
     * Reset failure count
     */
    public function resetFailureCount(): void
    {
        $this->update([
            'failure_count' => 0,
            'last_response_status' => null
        ]);
    }
    
    /**
     * Increment failure count
     */
    public function incrementFailureCount(int $responseStatus = null): void
    {
        $this->update([
            'failure_count' => $this->failure_count + 1,
            'last_response_status' => $responseStatus
        ]);
    }
    
    /**
     * Scope for active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope for webhooks subscribed to specific event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}