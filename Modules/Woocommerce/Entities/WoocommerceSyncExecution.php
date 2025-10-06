<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class WoocommerceSyncExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'business_id',
        'location_id',
        'sync_type',
        'sync_progress_id',
        'status',
        'priority',
        'started_at',
        'completed_at',
        'duration_seconds',
        'records_processed',
        'records_success',
        'records_failed',
        'error_message',
        'retry_count',
        'next_retry_at',
        'metadata'
    ];

    protected $casts = [
        'priority' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'records_processed' => 'integer',
        'records_success' => 'integer',
        'records_failed' => 'integer',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Status constants
    const STATUS_QUEUED = 'queued';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RETRY_PENDING = 'retry_pending';

    /**
     * Relationships
     */
    public function schedule()
    {
        return $this->belongsTo(WoocommerceSyncSchedule::class);
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function syncProgress()
    {
        return $this->belongsTo(WoocommerceSyncProgress::class, 'sync_progress_id');
    }

    /**
     * Scopes
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForSchedule($query, $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePendingRetry($query)
    {
        return $query->where('status', self::STATUS_RETRY_PENDING)
                    ->where('next_retry_at', '<=', Carbon::now());
    }

    /**
     * Mark execution as started
     */
    public function markStarted()
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => Carbon::now()
        ]);
    }

    /**
     * Mark execution as completed successfully
     */
    public function markCompleted($recordsProcessed = 0, $recordsSuccess = 0, $recordsFailed = 0)
    {
        $completedAt = Carbon::now();
        $duration = $this->started_at ? $completedAt->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'records_processed' => $recordsProcessed,
            'records_success' => $recordsSuccess,
            'records_failed' => $recordsFailed
        ]);

        // Update parent schedule success count
        if ($this->schedule) {
            $this->schedule->increment('success_count');
        }
    }

    /**
     * Mark execution as failed
     */
    public function markFailed($errorMessage = null)
    {
        $completedAt = Carbon::now();
        $duration = $this->started_at ? $completedAt->diffInSeconds($this->started_at) : 0;

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
            'error_message' => $errorMessage
        ]);

        // Update parent schedule failure count
        if ($this->schedule) {
            $this->schedule->increment('failure_count');
        }

        // Check if retry is needed
        $this->checkRetry();
    }

    /**
     * Mark execution for retry
     */
    public function markForRetry($delayMinutes = null)
    {
        if (!$this->schedule) {
            return false;
        }

        $maxRetries = $this->schedule->retry_attempts ?? 3;
        if ($this->retry_count >= $maxRetries) {
            return false; // Max retries reached
        }

        $delayMinutes = $delayMinutes ?? $this->schedule->retry_delay_minutes ?? 15;
        
        $this->update([
            'status' => self::STATUS_RETRY_PENDING,
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => Carbon::now()->addMinutes($delayMinutes)
        ]);

        return true;
    }

    /**
     * Check if execution should be retried
     */
    private function checkRetry()
    {
        if (!$this->schedule) {
            return;
        }

        $maxRetries = $this->schedule->retry_attempts ?? 3;
        if ($this->retry_count < $maxRetries) {
            $this->markForRetry();
        }
    }

    /**
     * Retry the execution
     */
    public function retry()
    {
        if ($this->status !== self::STATUS_RETRY_PENDING) {
            return false;
        }

        if ($this->next_retry_at && $this->next_retry_at->isFuture()) {
            return false; // Not time yet
        }

        try {
            $locationSetting = $this->location->woocommerceLocationSettings;
            if (!$locationSetting) {
                throw new \Exception('Location setting not found');
            }

            // Create new progress record for retry
            $syncProgress = WoocommerceSyncProgress::createSync(
                $this->business_id,
                $this->location_id,
                $this->sync_type
            );

            // Update execution
            $this->update([
                'sync_progress_id' => $syncProgress->id,
                'status' => self::STATUS_DISPATCHED,
                'error_message' => null
            ]);

            // Dispatch job
            \Modules\Woocommerce\Jobs\SyncLocationData::dispatch($locationSetting, $this->sync_type)
                ->onQueue($this->getQueueName());

            return true;

        } catch (\Exception $e) {
            $this->markFailed('Retry failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get appropriate queue based on priority
     */
    private function getQueueName()
    {
        if ($this->priority >= WoocommerceSyncSchedule::PRIORITY_CRITICAL) {
            return 'woocommerce-critical';
        } elseif ($this->priority >= WoocommerceSyncSchedule::PRIORITY_HIGH) {
            return 'woocommerce-high';
        } else {
            return 'woocommerce-normal';
        }
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
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . $remainingSeconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get success rate for this execution
     */
    public function getSuccessRateAttribute()
    {
        if (!$this->records_processed || $this->records_processed == 0) {
            return 100;
        }

        return round(($this->records_success / $this->records_processed) * 100, 1);
    }

    /**
     * Check if execution is still running
     */
    public function isRunning()
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_DISPATCHED, self::STATUS_PROCESSING]);
    }

    /**
     * Check if execution is completed (success or failure)
     */
    public function isCompleted()
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if execution can be retried
     */
    public function canRetry()
    {
        if (!$this->schedule) {
            return false;
        }

        $maxRetries = $this->schedule->retry_attempts ?? 3;
        return $this->status === self::STATUS_FAILED && $this->retry_count < $maxRetries;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_QUEUED => 'secondary',
            self::STATUS_DISPATCHED => 'info',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'warning',
            self::STATUS_RETRY_PENDING => 'warning'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Get status icon for UI
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            self::STATUS_QUEUED => 'fas fa-clock',
            self::STATUS_DISPATCHED => 'fas fa-paper-plane',
            self::STATUS_PROCESSING => 'fas fa-sync fa-spin',
            self::STATUS_COMPLETED => 'fas fa-check-circle',
            self::STATUS_FAILED => 'fas fa-exclamation-triangle',
            self::STATUS_CANCELLED => 'fas fa-times-circle',
            self::STATUS_RETRY_PENDING => 'fas fa-redo'
        ];

        return $icons[$this->status] ?? 'fas fa-question';
    }

    /**
     * Get execution summary for reporting
     */
    public function getSummary()
    {
        return [
            'id' => $this->id,
            'schedule_name' => $this->schedule->name ?? 'Unknown',
            'location_name' => $this->location->name ?? 'Unknown',
            'sync_type' => $this->sync_type,
            'status' => $this->status,
            'duration' => $this->formatted_duration,
            'success_rate' => $this->success_rate,
            'records_processed' => $this->records_processed,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'error_message' => $this->error_message
        ];
    }
}