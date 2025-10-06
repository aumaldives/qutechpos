<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Cron\CronExpression;

class WoocommerceSyncSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'location_id',
        'name',
        'description',
        'sync_type',
        'cron_expression',
        'is_active',
        'priority',
        'timezone',
        'max_runtime_minutes',
        'retry_attempts',
        'retry_delay_minutes',
        'conditions',
        'notifications',
        'last_run_at',
        'next_run_at',
        'success_count',
        'failure_count',
        'created_by_user_id',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_runtime_minutes' => 'integer',
        'retry_attempts' => 'integer',
        'retry_delay_minutes' => 'integer',
        'conditions' => 'array',
        'notifications' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'metadata' => 'array'
    ];

    // Schedule status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_PAUSED = 'paused';

    // Priority levels
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 5;
    const PRIORITY_HIGH = 8;
    const PRIORITY_CRITICAL = 10;

    // Sync type constants (matching SyncLocationData)
    const SYNC_TYPE_ALL = 'all';
    const SYNC_TYPE_PRODUCTS = 'products';
    const SYNC_TYPE_ORDERS = 'orders';
    const SYNC_TYPE_CUSTOMERS = 'customers';
    const SYNC_TYPE_INVENTORY = 'inventory';

    // Predefined cron expressions
    const CRON_EVERY_MINUTE = '* * * * *';
    const CRON_EVERY_5_MINUTES = '*/5 * * * *';
    const CRON_EVERY_15_MINUTES = '*/15 * * * *';
    const CRON_EVERY_30_MINUTES = '*/30 * * * *';
    const CRON_HOURLY = '0 * * * *';
    const CRON_EVERY_2_HOURS = '0 */2 * * *';
    const CRON_EVERY_6_HOURS = '0 */6 * * *';
    const CRON_DAILY = '0 0 * * *';
    const CRON_WEEKLY = '0 0 * * 0';
    const CRON_MONTHLY = '0 0 1 * *';

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

    public function createdByUser()
    {
        return $this->belongsTo(\App\User::class, 'created_by_user_id');
    }

    public function executions()
    {
        return $this->hasMany(WoocommerceSyncExecution::class, 'schedule_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeDue($query, $timezone = null)
    {
        $now = $timezone ? Carbon::now($timezone) : Carbon::now();
        return $query->active()->where('next_run_at', '<=', $now);
    }

    public function scopeByPriority($query, $order = 'desc')
    {
        return $query->orderBy('priority', $order);
    }

    /**
     * Create a new schedule with intelligent defaults
     */
    public static function createSchedule($businessId, $locationId, $syncType, $cronExpression, $options = [])
    {
        $defaults = [
            'name' => self::generateScheduleName($syncType, $cronExpression),
            'description' => self::generateScheduleDescription($syncType, $cronExpression),
            'is_active' => true,
            'priority' => self::PRIORITY_NORMAL,
            'timezone' => config('app.timezone', 'UTC'),
            'max_runtime_minutes' => self::getDefaultRuntime($syncType),
            'retry_attempts' => 3,
            'retry_delay_minutes' => 15,
            'conditions' => [],
            'notifications' => ['on_failure' => true, 'on_success' => false],
            'created_by_user_id' => auth()->id()
        ];

        $data = array_merge($defaults, $options, [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'sync_type' => $syncType,
            'cron_expression' => $cronExpression
        ]);

        $schedule = self::create($data);
        $schedule->calculateNextRun();

        return $schedule;
    }

    /**
     * Calculate and update the next run time
     */
    public function calculateNextRun()
    {
        try {
            $cron = new CronExpression($this->cron_expression);
            $timezone = $this->timezone ?? config('app.timezone', 'UTC');
            $now = Carbon::now($timezone);
            
            $nextRun = $cron->getNextRunDate($now);
            
            $this->update(['next_run_at' => $nextRun]);
            
            return $nextRun;
        } catch (\Exception $e) {
            \Log::error('Failed to calculate next run for schedule', [
                'schedule_id' => $this->id,
                'cron_expression' => $this->cron_expression,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to daily schedule
            $this->update(['next_run_at' => Carbon::now()->addDay()]);
            return Carbon::now()->addDay();
        }
    }

    /**
     * Check if schedule is due to run
     */
    public function isDue()
    {
        if (!$this->is_active || !$this->next_run_at) {
            return false;
        }

        $timezone = $this->timezone ?? config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        
        return $this->next_run_at <= $now;
    }

    /**
     * Execute the scheduled sync
     */
    public function execute()
    {
        if (!$this->isDue()) {
            return false;
        }

        // Check conditions before execution
        if (!$this->checkConditions()) {
            $this->calculateNextRun();
            return false;
        }

        // Create execution record
        $execution = WoocommerceSyncExecution::create([
            'schedule_id' => $this->id,
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'sync_type' => $this->sync_type,
            'status' => 'queued',
            'started_at' => Carbon::now(),
            'metadata' => [
                'triggered_by' => 'schedule',
                'cron_expression' => $this->cron_expression,
                'priority' => $this->priority
            ]
        ]);

        // Dispatch the sync job with execution tracking
        try {
            $locationSetting = $this->locationSetting;
            if (!$locationSetting) {
                throw new \Exception('Location setting not found');
            }

            // Create progress record linked to execution
            $syncProgress = WoocommerceSyncProgress::createSync(
                $this->business_id,
                $this->location_id,
                $this->sync_type
            );

            // Update execution with progress ID
            $execution->update([
                'sync_progress_id' => $syncProgress->id,
                'status' => 'dispatched'
            ]);

            // Dispatch job with priority
            \Modules\Woocommerce\Jobs\SyncLocationData::dispatch($locationSetting, $this->sync_type)
                ->onQueue($this->getQueueName());

            // Update schedule
            $this->update(['last_run_at' => Carbon::now()]);
            $this->calculateNextRun();

            return $execution;

        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'completed_at' => Carbon::now(),
                'error_message' => $e->getMessage()
            ]);

            $this->increment('failure_count');
            $this->calculateNextRun();

            \Log::error('Scheduled sync execution failed', [
                'schedule_id' => $this->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Check if conditions are met for execution
     */
    public function checkConditions()
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition($condition)
    {
        switch ($condition['type']) {
            case 'time_range':
                return $this->checkTimeRange($condition);
            case 'business_hours_only':
                return $this->checkBusinessHours();
            case 'no_recent_errors':
                return $this->checkRecentErrors($condition);
            case 'min_interval_since_last':
                return $this->checkMinInterval($condition);
            default:
                return true;
        }
    }

    /**
     * Check if current time is within allowed range
     */
    private function checkTimeRange($condition)
    {
        $timezone = $this->timezone ?? config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $currentTime = $now->format('H:i');
        
        return $currentTime >= $condition['start_time'] && $currentTime <= $condition['end_time'];
    }

    /**
     * Check if within business hours
     */
    private function checkBusinessHours()
    {
        $timezone = $this->timezone ?? config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        
        // Default business hours: 9 AM to 6 PM, Monday to Friday
        if ($now->isWeekend()) {
            return false;
        }
        
        $hour = $now->hour;
        return $hour >= 9 && $hour <= 18;
    }

    /**
     * Check if there are no recent sync errors
     */
    private function checkRecentErrors($condition)
    {
        $hours = $condition['hours'] ?? 1;
        $threshold = $condition['max_errors'] ?? 5;
        
        $recentErrors = WoocommerceSyncError::where('business_id', $this->business_id)
                                           ->where('location_id', $this->location_id)
                                           ->where('created_at', '>=', Carbon::now()->subHours($hours))
                                           ->count();
        
        return $recentErrors <= $threshold;
    }

    /**
     * Check minimum interval since last execution
     */
    private function checkMinInterval($condition)
    {
        if (!$this->last_run_at) {
            return true;
        }
        
        $minutes = $condition['minutes'] ?? 60;
        return $this->last_run_at <= Carbon::now()->subMinutes($minutes);
    }

    /**
     * Get appropriate queue name based on priority
     */
    public function getQueueName()
    {
        if ($this->priority >= self::PRIORITY_CRITICAL) {
            return 'woocommerce-critical';
        } elseif ($this->priority >= self::PRIORITY_HIGH) {
            return 'woocommerce-high';
        } else {
            return 'woocommerce-normal';
        }
    }

    /**
     * Pause the schedule
     */
    public function pause()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Resume the schedule
     */
    public function resume()
    {
        $this->update(['is_active' => true]);
        $this->calculateNextRun();
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        $total = $this->success_count + $this->failure_count;
        
        if ($total == 0) {
            return 100; // No executions yet
        }
        
        return round(($this->success_count / $total) * 100, 1);
    }

    /**
     * Get human-readable next run time
     */
    public function getNextRunHumanAttribute()
    {
        if (!$this->next_run_at) {
            return 'Not scheduled';
        }
        
        return $this->next_run_at->diffForHumans();
    }

    /**
     * Get cron expression description
     */
    public function getCronDescriptionAttribute()
    {
        try {
            $cron = new CronExpression($this->cron_expression);
            return $cron->getExpression();
        } catch (\Exception $e) {
            return 'Invalid expression';
        }
    }

    /**
     * Generate default schedule name
     */
    private static function generateScheduleName($syncType, $cronExpression)
    {
        $typeNames = [
            'all' => 'Full Sync',
            'products' => 'Product Sync',
            'orders' => 'Order Sync',
            'customers' => 'Customer Sync',
            'inventory' => 'Inventory Sync'
        ];

        $typeName = $typeNames[$syncType] ?? ucfirst($syncType) . ' Sync';
        $frequency = self::describeCronFrequency($cronExpression);
        
        return "{$typeName} - {$frequency}";
    }

    /**
     * Generate schedule description
     */
    private static function generateScheduleDescription($syncType, $cronExpression)
    {
        $frequency = self::describeCronFrequency($cronExpression);
        return "Automatically sync {$syncType} data {$frequency}";
    }

    /**
     * Describe cron frequency in human terms
     */
    private static function describeCronFrequency($cronExpression)
    {
        $descriptions = [
            '* * * * *' => 'Every Minute',
            '*/5 * * * *' => 'Every 5 Minutes',
            '*/15 * * * *' => 'Every 15 Minutes',
            '*/30 * * * *' => 'Every 30 Minutes',
            '0 * * * *' => 'Hourly',
            '0 */2 * * *' => 'Every 2 Hours',
            '0 */6 * * *' => 'Every 6 Hours',
            '0 0 * * *' => 'Daily',
            '0 0 * * 0' => 'Weekly',
            '0 0 1 * *' => 'Monthly'
        ];

        return $descriptions[$cronExpression] ?? 'Custom Schedule';
    }

    /**
     * Get default runtime based on sync type
     */
    private static function getDefaultRuntime($syncType)
    {
        $runtimes = [
            'all' => 120, // 2 hours
            'products' => 60, // 1 hour
            'orders' => 30, // 30 minutes
            'customers' => 30, // 30 minutes
            'inventory' => 45 // 45 minutes
        ];

        return $runtimes[$syncType] ?? 60;
    }

    /**
     * Get predefined schedule templates
     */
    public static function getTemplates()
    {
        return [
            'frequent' => [
                'name' => 'Frequent Sync',
                'description' => 'High-frequency sync for active stores',
                'cron_expression' => self::CRON_EVERY_15_MINUTES,
                'priority' => self::PRIORITY_HIGH,
                'conditions' => [
                    ['type' => 'business_hours_only'],
                    ['type' => 'no_recent_errors', 'hours' => 1, 'max_errors' => 3]
                ]
            ],
            'standard' => [
                'name' => 'Standard Sync',
                'description' => 'Balanced sync for regular operations',
                'cron_expression' => self::CRON_HOURLY,
                'priority' => self::PRIORITY_NORMAL,
                'conditions' => []
            ],
            'nightly' => [
                'name' => 'Nightly Full Sync',
                'description' => 'Complete synchronization during off-hours',
                'cron_expression' => '0 2 * * *', // 2 AM daily
                'priority' => self::PRIORITY_HIGH,
                'conditions' => [
                    ['type' => 'time_range', 'start_time' => '01:00', 'end_time' => '06:00']
                ]
            ],
            'conservative' => [
                'name' => 'Conservative Sync',
                'description' => 'Less frequent sync to minimize load',
                'cron_expression' => self::CRON_EVERY_6_HOURS,
                'priority' => self::PRIORITY_LOW,
                'conditions' => []
            ]
        ];
    }
}