<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WoocommerceSyncProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'location_id', 
        'sync_type',
        'status',
        'progress_percentage',
        'current_step',
        'total_steps',
        'records_processed',
        'records_total',
        'records_success',
        'records_failed',
        'current_operation',
        'estimated_time_remaining',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_message',
        'metadata'
    ];

    protected $casts = [
        'progress_percentage' => 'float',
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'records_processed' => 'integer',
        'records_total' => 'integer',
        'records_success' => 'integer',
        'records_failed' => 'integer',
        'estimated_time_remaining' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Sync type constants
    const SYNC_TYPE_ALL = 'all';
    const SYNC_TYPE_PRODUCTS = 'products';
    const SYNC_TYPE_ORDERS = 'orders';
    const SYNC_TYPE_CUSTOMERS = 'customers';
    const SYNC_TYPE_INVENTORY = 'inventory';

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
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopePaused($query)
    {
        return $query->where('status', self::STATUS_PAUSED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Create a new sync progress record
     */
    public static function createSync($business_id, $location_id, $sync_type, $estimated_total = null)
    {
        return self::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'sync_type' => $sync_type,
            'status' => self::STATUS_PENDING,
            'progress_percentage' => 0,
            'current_step' => 0,
            'total_steps' => self::getEstimatedSteps($sync_type),
            'records_processed' => 0,
            'records_total' => $estimated_total ?? 0,
            'records_success' => 0,
            'records_failed' => 0,
            'current_operation' => 'Initializing sync...',
            'started_at' => now()
        ]);
    }

    /**
     * Update sync progress
     */
    public function updateProgress($data)
    {
        // Calculate progress percentage if not provided
        if (!isset($data['progress_percentage'])) {
            if ($this->records_total > 0) {
                $data['progress_percentage'] = round(($this->records_processed / $this->records_total) * 100, 2);
            } elseif ($this->total_steps > 0) {
                $data['progress_percentage'] = round(($this->current_step / $this->total_steps) * 100, 2);
            }
        }

        // Calculate estimated time remaining
        if (!isset($data['estimated_time_remaining']) && $this->started_at) {
            $elapsed = now()->diffInSeconds($this->started_at);
            $progress = $data['progress_percentage'] ?? $this->progress_percentage;
            
            if ($progress > 0) {
                $totalEstimated = ($elapsed / $progress) * 100;
                $data['estimated_time_remaining'] = max(0, $totalEstimated - $elapsed);
            }
        }

        return $this->update($data);
    }

    /**
     * Mark sync as completed
     */
    public function markCompleted($success_count = null, $failed_count = null)
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'progress_percentage' => 100,
            'current_operation' => 'Sync completed successfully',
            'completed_at' => now()
        ];

        if ($success_count !== null) {
            $data['records_success'] = $success_count;
        }

        if ($failed_count !== null) {
            $data['records_failed'] = $failed_count;
        }

        // Calculate duration
        if ($this->started_at) {
            $data['duration_seconds'] = now()->diffInSeconds($this->started_at);
        }

        return $this->update($data);
    }

    /**
     * Mark sync as failed
     */
    public function markFailed($error_message = null)
    {
        $data = [
            'status' => self::STATUS_FAILED,
            'current_operation' => 'Sync failed',
            'completed_at' => now()
        ];

        if ($error_message) {
            $data['error_message'] = $error_message;
        }

        // Calculate duration
        if ($this->started_at) {
            $data['duration_seconds'] = now()->diffInSeconds($this->started_at);
        }

        return $this->update($data);
    }

    /**
     * Start processing
     */
    public function startProcessing()
    {
        return $this->update([
            'status' => self::STATUS_PROCESSING,
            'current_operation' => 'Starting sync...',
            'started_at' => $this->started_at ?? now()
        ]);
    }

    /**
     * Increment processed records
     */
    public function incrementProcessed($success = true)
    {
        $data = [
            'records_processed' => $this->records_processed + 1
        ];

        if ($success) {
            $data['records_success'] = $this->records_success + 1;
        } else {
            $data['records_failed'] = $this->records_failed + 1;
        }

        return $this->updateProgress($data);
    }

    /**
     * Set current operation step
     */
    public function setCurrentOperation($operation, $step = null)
    {
        $data = ['current_operation' => $operation];
        
        if ($step !== null) {
            $data['current_step'] = $step;
        }

        return $this->updateProgress($data);
    }

    /**
     * Get estimated steps for sync type
     */
    private static function getEstimatedSteps($sync_type)
    {
        $steps = [
            self::SYNC_TYPE_ALL => 8, // Initialize, Products, Orders, Customers, Inventory, Cleanup, Validate, Complete
            self::SYNC_TYPE_PRODUCTS => 3, // Initialize, Sync Products, Complete  
            self::SYNC_TYPE_ORDERS => 3, // Initialize, Sync Orders, Complete
            self::SYNC_TYPE_CUSTOMERS => 3, // Initialize, Sync Customers, Complete
            self::SYNC_TYPE_INVENTORY => 4, // Initialize, Sync Inventory, Validate, Complete
        ];

        return $steps[$sync_type] ?? 3;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $seconds = $this->duration_seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get formatted estimated time remaining
     */
    public function getFormattedEtaAttribute()
    {
        if (!$this->estimated_time_remaining) {
            return null;
        }

        $seconds = $this->estimated_time_remaining;
        
        if ($seconds < 60) {
            return 'about ' . round($seconds) . 's remaining';
        } elseif ($seconds < 3600) {
            return 'about ' . round($seconds / 60) . 'm remaining';
        } else {
            $hours = round($seconds / 3600, 1);
            return 'about ' . $hours . 'h remaining';
        }
    }

    /**
     * Check if sync is active (processing or paused)
     */
    public function isActive()
    {
        return in_array($this->status, [self::STATUS_PROCESSING, self::STATUS_PAUSED]);
    }

    /**
     * Check if sync is completed (successfully or failed)
     */
    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Get progress color for UI
     */
    public function getProgressColorAttribute()
    {
        switch ($this->status) {
            case self::STATUS_COMPLETED:
                return 'success';
            case self::STATUS_FAILED:
            case self::STATUS_CANCELLED:
                return 'danger';
            case self::STATUS_PAUSED:
                return 'warning';
            case self::STATUS_PROCESSING:
                return 'info';
            default:
                return 'secondary';
        }
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            self::STATUS_PENDING => 'fas fa-clock',
            self::STATUS_PROCESSING => 'fas fa-sync fa-spin',
            self::STATUS_PAUSED => 'fas fa-pause',
            self::STATUS_COMPLETED => 'fas fa-check-circle',
            self::STATUS_FAILED => 'fas fa-exclamation-triangle',
            self::STATUS_CANCELLED => 'fas fa-times-circle'
        ];

        return $icons[$this->status] ?? 'fas fa-question';
    }
}