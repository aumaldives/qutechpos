<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WoocommerceSyncError extends Model
{
    use HasFactory;

    protected $table = 'woocommerce_sync_errors';

    protected $fillable = [
        'business_id',
        'location_id',
        'sync_job_id',
        'sync_type',
        'error_category',
        'error_code',
        'error_message',
        'error_context',
        'affected_entity_type',
        'affected_entity_id',
        'recovery_attempts',
        'is_resolved',
        'resolved_at',
        'resolution_method',
        'severity_level',
        'retry_after'
    ];

    protected $casts = [
        'error_context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'retry_after' => 'datetime'
    ];

    // Error categories
    const CATEGORY_API_AUTH = 'api_authentication';
    const CATEGORY_API_RATE_LIMIT = 'api_rate_limit';
    const CATEGORY_API_CONNECTION = 'api_connection';
    const CATEGORY_DATA_VALIDATION = 'data_validation';
    const CATEGORY_ENTITY_NOT_FOUND = 'entity_not_found';
    const CATEGORY_BUSINESS_LOGIC = 'business_logic';
    const CATEGORY_SYSTEM_ERROR = 'system_error';
    const CATEGORY_CONFIGURATION = 'configuration';

    // Severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Entity types
    const ENTITY_PRODUCT = 'product';
    const ENTITY_ORDER = 'order';
    const ENTITY_CUSTOMER = 'customer';
    const ENTITY_INVENTORY = 'inventory';
    const ENTITY_CATEGORY = 'category';

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function locationSetting()
    {
        return $this->belongsTo(WoocommerceLocationSetting::class, 'location_id', 'location_id');
    }

    /**
     * Scopes
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity_level', $severity);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('error_category', $category);
    }

    public function scopeRetryable($query)
    {
        return $query->where(function($q) {
            $q->whereNull('retry_after')->orWhere('retry_after', '<=', now());
        });
    }

    /**
     * Static methods for error creation
     */
    public static function logError(
        $businessId,
        $locationId,
        $syncType,
        $category,
        $message,
        $context = [],
        $entityType = null,
        $entityId = null,
        $errorCode = null
    ) {
        $severity = self::determineSeverity($category, $errorCode);
        $retryAfter = self::calculateRetryDelay($category, $errorCode);

        return self::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'sync_job_id' => self::getCurrentJobId(),
            'sync_type' => $syncType,
            'error_category' => $category,
            'error_code' => $errorCode,
            'error_message' => $message,
            'error_context' => $context,
            'affected_entity_type' => $entityType,
            'affected_entity_id' => $entityId,
            'severity_level' => $severity,
            'retry_after' => $retryAfter,
            'recovery_attempts' => 0,
            'is_resolved' => false
        ]);
    }

    /**
     * Determine error severity based on category and code
     */
    private static function determineSeverity($category, $errorCode)
    {
        switch ($category) {
            case self::CATEGORY_API_AUTH:
            case self::CATEGORY_CONFIGURATION:
                return self::SEVERITY_CRITICAL;

            case self::CATEGORY_API_RATE_LIMIT:
                return self::SEVERITY_MEDIUM;

            case self::CATEGORY_ENTITY_NOT_FOUND:
                return $errorCode === 404 ? self::SEVERITY_MEDIUM : self::SEVERITY_HIGH;

            case self::CATEGORY_DATA_VALIDATION:
                return self::SEVERITY_MEDIUM;

            case self::CATEGORY_SYSTEM_ERROR:
                return self::SEVERITY_HIGH;

            case self::CATEGORY_API_CONNECTION:
                return self::SEVERITY_HIGH;

            default:
                return self::SEVERITY_MEDIUM;
        }
    }

    /**
     * Calculate retry delay based on error type
     */
    private static function calculateRetryDelay($category, $errorCode)
    {
        switch ($category) {
            case self::CATEGORY_API_RATE_LIMIT:
                return now()->addMinutes(60); // Wait 1 hour for rate limits

            case self::CATEGORY_API_CONNECTION:
                return now()->addMinutes(5); // Quick retry for connection issues

            case self::CATEGORY_ENTITY_NOT_FOUND:
                return now()->addHours(4); // Wait longer for missing entities

            case self::CATEGORY_API_AUTH:
            case self::CATEGORY_CONFIGURATION:
                return null; // No automatic retry for auth/config issues

            default:
                return now()->addMinutes(15); // Default 15-minute retry
        }
    }

    /**
     * Get current job ID if running in queue
     */
    private static function getCurrentJobId()
    {
        // Try to get job ID from queue context
        if (app()->bound('queue.job')) {
            $job = app('queue.job');
            return $job ? $job->getJobId() : null;
        }
        return null;
    }

    /**
     * Mark error as resolved
     */
    public function markResolved($method = 'manual')
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolution_method' => $method
        ]);
    }

    /**
     * Increment recovery attempts
     */
    public function incrementRecoveryAttempts()
    {
        $this->increment('recovery_attempts');
        
        // Exponential backoff for retry delay
        $baseDelay = 15; // 15 minutes base
        $newDelay = min($baseDelay * pow(2, $this->recovery_attempts), 1440); // Max 24 hours
        
        $this->update(['retry_after' => now()->addMinutes($newDelay)]);
    }

    /**
     * Get error statistics
     */
    public static function getErrorStats($businessId, $locationId = null, $days = 7)
    {
        $query = self::where('business_id', $businessId)
                     ->where('created_at', '>=', now()->subDays($days));

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return [
            'total_errors' => $query->count(),
            'unresolved_errors' => $query->unresolved()->count(),
            'critical_errors' => $query->bySeverity(self::SEVERITY_CRITICAL)->count(),
            'by_category' => $query->groupBy('error_category')
                                  ->selectRaw('error_category, count(*) as count')
                                  ->pluck('count', 'error_category')
                                  ->toArray(),
            'by_severity' => $query->groupBy('severity_level')
                                  ->selectRaw('severity_level, count(*) as count')
                                  ->pluck('count', 'severity_level')
                                  ->toArray()
        ];
    }
}