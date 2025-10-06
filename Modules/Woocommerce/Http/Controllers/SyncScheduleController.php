<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Woocommerce\Entities\WoocommerceSyncSchedule;
use Modules\Woocommerce\Entities\WoocommerceSyncExecution;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SyncScheduleController extends Controller
{
    /**
     * Display schedules for a specific location
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $location_id = $request->get('location_id');

        $query = WoocommerceSyncSchedule::forBusiness($business_id)
                                       ->with(['location', 'locationSetting']);

        if ($location_id) {
            $query->forLocation($location_id);
        }

        $schedules = $query->orderBy('priority', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'schedules' => $schedules->map(function ($schedule) {
                return $this->formatScheduleForResponse($schedule);
            })
        ]);
    }

    /**
     * Create a new schedule for a location
     */
    public function store(Request $request): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $validator = Validator::make($request->all(), [
            'location_id' => 'required|integer|exists:business_locations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sync_type' => 'required|in:all,products,orders,customers,inventory',
            'cron_expression' => 'required|string',
            'priority' => 'nullable|integer|min:1|max:10',
            'timezone' => 'nullable|string',
            'max_runtime_minutes' => 'nullable|integer|min:5|max:480',
            'retry_attempts' => 'nullable|integer|min:0|max:5',
            'retry_delay_minutes' => 'nullable|integer|min:1|max:120',
            'conditions' => 'nullable|array',
            'notifications' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'template' => 'nullable|string|in:frequent,standard,nightly,conservative'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Verify location belongs to business
            $locationSetting = WoocommerceLocationSetting::where('business_id', $business_id)
                                                        ->where('location_id', $request->location_id)
                                                        ->first();

            if (!$locationSetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not configured for WooCommerce or access denied'
                ], 403);
            }

            // Validate cron expression
            if (!$this->isValidCronExpression($request->cron_expression)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid cron expression'
                ], 400);
            }

            // Apply template if specified
            $scheduleData = $request->all();
            if ($request->template) {
                $template = WoocommerceSyncSchedule::getTemplates()[$request->template] ?? null;
                if ($template) {
                    $scheduleData = array_merge($template, $scheduleData);
                }
            }

            // Create schedule
            $schedule = WoocommerceSyncSchedule::createSchedule(
                $business_id,
                $request->location_id,
                $request->sync_type,
                $request->cron_expression,
                $scheduleData
            );

            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'schedule' => $this->formatScheduleForResponse($schedule)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create sync schedule', [
                'error' => $e->getMessage(),
                'business_id' => $business_id,
                'location_id' => $request->location_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific schedule
     */
    public function show(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)
                                          ->with(['location', 'locationSetting', 'executions' => function($query) {
                                              $query->latest()->limit(10);
                                          }])
                                          ->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'schedule' => $this->formatScheduleForResponse($schedule, true)
        ]);
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cron_expression' => 'sometimes|required|string',
            'priority' => 'nullable|integer|min:1|max:10',
            'timezone' => 'nullable|string',
            'max_runtime_minutes' => 'nullable|integer|min:5|max:480',
            'retry_attempts' => 'nullable|integer|min:0|max:5',
            'retry_delay_minutes' => 'nullable|integer|min:1|max:120',
            'conditions' => 'nullable|array',
            'notifications' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Validate cron expression if provided
            if ($request->has('cron_expression') && !$this->isValidCronExpression($request->cron_expression)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid cron expression'
                ], 400);
            }

            $updateData = $request->only([
                'name', 'description', 'cron_expression', 'priority', 'timezone',
                'max_runtime_minutes', 'retry_attempts', 'retry_delay_minutes',
                'conditions', 'notifications', 'is_active'
            ]);

            $schedule->update($updateData);

            // Recalculate next run if cron expression changed
            if ($request->has('cron_expression')) {
                $schedule->calculateNextRun();
            }

            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'schedule' => $this->formatScheduleForResponse($schedule)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update sync schedule', [
                'error' => $e->getMessage(),
                'schedule_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a schedule
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        try {
            $scheduleName = $schedule->name;
            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => "Schedule '{$scheduleName}' deleted successfully"
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete sync schedule', [
                'error' => $e->getMessage(),
                'schedule_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle schedule active status
     */
    public function toggle(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        try {
            if ($schedule->is_active) {
                $schedule->pause();
                $message = 'Schedule paused successfully';
            } else {
                $schedule->resume();
                $message = 'Schedule resumed successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'schedule' => $this->formatScheduleForResponse($schedule)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute a schedule immediately
     */
    public function executeNow(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)
                                          ->with('locationSetting')
                                          ->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        if (!$schedule->locationSetting || !$schedule->locationSetting->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Location WooCommerce integration is disabled'
            ], 400);
        }

        try {
            $execution = $schedule->execute();

            return response()->json([
                'success' => true,
                'message' => 'Schedule executed successfully',
                'execution_id' => $execution->id,
                'sync_progress_id' => $execution->sync_progress_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to execute schedule', [
                'error' => $e->getMessage(),
                'schedule_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get schedule templates
     */
    public function getTemplates(): JsonResponse
    {
        $templates = WoocommerceSyncSchedule::getTemplates();

        return response()->json([
            'success' => true,
            'templates' => $templates
        ]);
    }

    /**
     * Get predefined cron expressions
     */
    public function getCronExpressions(): JsonResponse
    {
        $expressions = [
            'every_minute' => ['expression' => '* * * * *', 'description' => 'Every minute'],
            'every_5_minutes' => ['expression' => '*/5 * * * *', 'description' => 'Every 5 minutes'],
            'every_15_minutes' => ['expression' => '*/15 * * * *', 'description' => 'Every 15 minutes'],
            'every_30_minutes' => ['expression' => '*/30 * * * *', 'description' => 'Every 30 minutes'],
            'hourly' => ['expression' => '0 * * * *', 'description' => 'Every hour'],
            'every_2_hours' => ['expression' => '0 */2 * * *', 'description' => 'Every 2 hours'],
            'every_6_hours' => ['expression' => '0 */6 * * *', 'description' => 'Every 6 hours'],
            'daily' => ['expression' => '0 0 * * *', 'description' => 'Daily at midnight'],
            'daily_6am' => ['expression' => '0 6 * * *', 'description' => 'Daily at 6:00 AM'],
            'daily_2am' => ['expression' => '0 2 * * *', 'description' => 'Daily at 2:00 AM'],
            'weekly' => ['expression' => '0 0 * * 0', 'description' => 'Weekly on Sunday'],
            'monthly' => ['expression' => '0 0 1 * *', 'description' => 'Monthly on 1st day'],
        ];

        return response()->json([
            'success' => true,
            'expressions' => $expressions
        ]);
    }

    /**
     * Get schedule execution history
     */
    public function getExecutions(Request $request, $id): JsonResponse
    {
        $business_id = $request->session()->get('business.id');
        $limit = $request->get('limit', 50);

        $schedule = WoocommerceSyncSchedule::forBusiness($business_id)->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $executions = WoocommerceSyncExecution::forSchedule($id)
                                             ->with('syncProgress')
                                             ->latest()
                                             ->limit($limit)
                                             ->get()
                                             ->map(function ($execution) {
                                                 return $this->formatExecutionForResponse($execution);
                                             });

        return response()->json([
            'success' => true,
            'executions' => $executions
        ]);
    }

    /**
     * Validate cron expression
     */
    private function isValidCronExpression($expression): bool
    {
        try {
            new \Cron\CronExpression($expression);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Format schedule for API response
     */
    private function formatScheduleForResponse($schedule, $includeExecutions = false): array
    {
        $data = [
            'id' => $schedule->id,
            'name' => $schedule->name,
            'description' => $schedule->description,
            'sync_type' => $schedule->sync_type,
            'cron_expression' => $schedule->cron_expression,
            'cron_description' => $schedule->cron_description,
            'is_active' => $schedule->is_active,
            'priority' => $schedule->priority,
            'timezone' => $schedule->timezone,
            'max_runtime_minutes' => $schedule->max_runtime_minutes,
            'retry_attempts' => $schedule->retry_attempts,
            'retry_delay_minutes' => $schedule->retry_delay_minutes,
            'conditions' => $schedule->conditions,
            'notifications' => $schedule->notifications,
            'last_run_at' => $schedule->last_run_at?->toISOString(),
            'next_run_at' => $schedule->next_run_at?->toISOString(),
            'next_run_human' => $schedule->next_run_human,
            'success_count' => $schedule->success_count,
            'failure_count' => $schedule->failure_count,
            'success_rate' => $schedule->success_rate,
            'created_at' => $schedule->created_at->toISOString(),
            'location' => [
                'id' => $schedule->location->id,
                'name' => $schedule->location->name
            ]
        ];

        if ($includeExecutions && $schedule->relationLoaded('executions')) {
            $data['recent_executions'] = $schedule->executions->map(function ($execution) {
                return $this->formatExecutionForResponse($execution);
            });
        }

        return $data;
    }

    /**
     * Format execution for API response
     */
    private function formatExecutionForResponse($execution): array
    {
        return [
            'id' => $execution->id,
            'status' => $execution->status,
            'status_color' => $execution->status_color,
            'status_icon' => $execution->status_icon,
            'priority' => $execution->priority,
            'started_at' => $execution->started_at?->toISOString(),
            'completed_at' => $execution->completed_at?->toISOString(),
            'formatted_duration' => $execution->formatted_duration,
            'records_processed' => $execution->records_processed,
            'records_success' => $execution->records_success,
            'records_failed' => $execution->records_failed,
            'success_rate' => $execution->success_rate,
            'error_message' => $execution->error_message,
            'retry_count' => $execution->retry_count,
            'next_retry_at' => $execution->next_retry_at?->toISOString(),
            'sync_progress_id' => $execution->sync_progress_id,
            'created_at' => $execution->created_at->toISOString()
        ];
    }
}