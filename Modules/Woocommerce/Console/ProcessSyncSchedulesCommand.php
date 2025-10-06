<?php

namespace Modules\Woocommerce\Console;

use Illuminate\Console\Command;
use Modules\Woocommerce\Entities\WoocommerceSyncSchedule;
use Modules\Woocommerce\Entities\WoocommerceSyncExecution;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSyncSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'woocommerce:process-schedules
                            {--limit=50 : Maximum number of schedules to process}
                            {--business-id= : Process schedules for specific business only}
                            {--dry-run : Show what would be processed without executing}
                            {--force : Force execution even if conditions are not met}';

    /**
     * The console command description.
     */
    protected $description = 'Process due WooCommerce sync schedules and execute them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $businessId = $this->option('business-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('⏰ WooCommerce Schedule Processor');
        $this->info('=================================');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No schedules will be executed');
        }

        // Process retries first
        $this->processRetries($dryRun);

        // Get due schedules
        $dueSchedules = $this->getDueSchedules($businessId, $limit);

        if ($dueSchedules->isEmpty()) {
            $this->info('✅ No schedules are due for execution');
            return 0;
        }

        $this->info("📋 Found {$dueSchedules->count()} schedule(s) due for execution");

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($dueSchedules as $schedule) {
            try {
                $this->processSchedule($schedule, $dryRun, $force);
                $processed++;
                $succeeded++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to process schedule {$schedule->id}: {$e->getMessage()}");
                Log::error('Schedule processing failed', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        $this->info('📊 Processing Summary:');
        $this->info("   Processed: {$processed}");
        $this->info("   Succeeded: {$succeeded}");
        $this->info("   Failed: {$failed}");

        return 0;
    }

    /**
     * Process pending retries
     */
    private function processRetries($dryRun)
    {
        $pendingRetries = WoocommerceSyncExecution::pendingRetry()->limit(20)->get();

        if ($pendingRetries->isEmpty()) {
            return;
        }

        $this->info("🔄 Processing {$pendingRetries->count()} pending retries...");

        foreach ($pendingRetries as $execution) {
            if ($dryRun) {
                $this->info("   Would retry execution {$execution->id} for schedule '{$execution->schedule->name}'");
            } else {
                try {
                    if ($execution->retry()) {
                        $this->info("   ✅ Retry queued for execution {$execution->id}");
                    } else {
                        $this->warn("   ⚠️  Retry failed for execution {$execution->id}");
                    }
                } catch (\Exception $e) {
                    $this->error("   ❌ Retry error for execution {$execution->id}: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Get schedules that are due for execution
     */
    private function getDueSchedules($businessId, $limit)
    {
        $query = WoocommerceSyncSchedule::due()
                                       ->byPriority('desc')
                                       ->with(['business', 'location', 'locationSetting'])
                                       ->limit($limit);

        if ($businessId) {
            $query->forBusiness($businessId);
        }

        return $query->get();
    }

    /**
     * Process a single schedule
     */
    private function processSchedule(WoocommerceSyncSchedule $schedule, $dryRun, $force)
    {
        $locationName = $schedule->location->name ?? 'Unknown Location';
        $businessName = $schedule->business->name ?? 'Unknown Business';

        $this->info("🔄 Processing: {$schedule->name}");
        $this->info("   Business: {$businessName}");
        $this->info("   Location: {$locationName}");
        $this->info("   Type: " . ucfirst($schedule->sync_type));
        $this->info("   Priority: {$schedule->priority}");
        $this->info("   Next Run: {$schedule->next_run_at}");

        // Check if location setting exists and is active
        if (!$schedule->locationSetting) {
            throw new \Exception('Location WooCommerce setting not found');
        }

        if (!$schedule->locationSetting->is_active) {
            $this->warn("   ⚠️  Skipping - Location WooCommerce integration is disabled");
            $schedule->calculateNextRun();
            return;
        }

        // Check conditions unless forced
        if (!$force && !$schedule->checkConditions()) {
            $this->warn("   ⚠️  Skipping - Schedule conditions not met");
            $schedule->calculateNextRun();
            return;
        }

        if ($dryRun) {
            $this->info("   ✅ Would execute {$schedule->sync_type} sync for {$locationName}");
            return;
        }

        // Check for existing active executions
        $activeExecution = WoocommerceSyncExecution::forSchedule($schedule->id)
                                                  ->where('status', 'processing')
                                                  ->first();

        if ($activeExecution) {
            $this->warn("   ⚠️  Skipping - Schedule already has an active execution (ID: {$activeExecution->id})");
            return;
        }

        // Execute the schedule
        try {
            $execution = $schedule->execute();
            
            $this->info("   ✅ Schedule executed successfully");
            $this->info("   📋 Execution ID: {$execution->id}");
            $this->info("   🕐 Next Run: {$schedule->next_run_human}");

            // Show execution details
            $this->showExecutionDetails($execution);

        } catch (\Exception $e) {
            $this->error("   ❌ Execution failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Show execution details
     */
    private function showExecutionDetails($execution)
    {
        if (!$execution) {
            return;
        }

        $this->line("   📊 Execution Details:");
        $this->line("      Status: {$execution->status}");
        $this->line("      Queue: {$execution->getQueueName()}");
        
        if ($execution->syncProgress) {
            $this->line("      Progress ID: {$execution->sync_progress_id}");
        }

        if ($execution->metadata) {
            $this->line("      Metadata: " . json_encode($execution->metadata, JSON_PRETTY_PRINT));
        }
    }

    /**
     * Show schedule statistics
     */
    public function showStats()
    {
        $this->info('📊 Schedule Statistics:');

        $totalSchedules = WoocommerceSyncSchedule::count();
        $activeSchedules = WoocommerceSyncSchedule::active()->count();
        $dueSchedules = WoocommerceSyncSchedule::due()->count();

        $this->info("   Total Schedules: {$totalSchedules}");
        $this->info("   Active Schedules: {$activeSchedules}");
        $this->info("   Due Now: {$dueSchedules}");

        // Recent execution stats
        $recentExecutions = WoocommerceSyncExecution::recent(7)->get();
        $successfulExecutions = $recentExecutions->where('status', 'completed')->count();
        $failedExecutions = $recentExecutions->where('status', 'failed')->count();

        $this->info("   Executions (7 days): {$recentExecutions->count()}");
        $this->info("   Successful: {$successfulExecutions}");
        $this->info("   Failed: {$failedExecutions}");

        if ($recentExecutions->count() > 0) {
            $successRate = round(($successfulExecutions / $recentExecutions->count()) * 100, 1);
            $this->info("   Success Rate: {$successRate}%");
        }
    }
}

// Additional helper command for schedule management
class ManageSyncSchedulesCommand extends Command
{
    protected $signature = 'woocommerce:manage-schedules
                            {action : Action to perform: list, enable, disable, delete}
                            {--schedule-id= : Specific schedule ID}
                            {--business-id= : Filter by business ID}
                            {--location-id= : Filter by location ID}';

    protected $description = 'Manage WooCommerce sync schedules';

    public function handle()
    {
        $action = $this->argument('action');
        $scheduleId = $this->option('schedule-id');
        $businessId = $this->option('business-id');
        $locationId = $this->option('location-id');

        switch ($action) {
            case 'list':
                $this->listSchedules($businessId, $locationId);
                break;
            case 'enable':
                $this->enableSchedule($scheduleId);
                break;
            case 'disable':
                $this->disableSchedule($scheduleId);
                break;
            case 'delete':
                $this->deleteSchedule($scheduleId);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    private function listSchedules($businessId = null, $locationId = null)
    {
        $query = WoocommerceSyncSchedule::with(['business', 'location']);

        if ($businessId) {
            $query->forBusiness($businessId);
        }

        if ($locationId) {
            $query->forLocation($locationId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No schedules found');
            return;
        }

        $headers = ['ID', 'Name', 'Business', 'Location', 'Type', 'Cron', 'Active', 'Next Run', 'Success Rate'];
        $rows = [];

        foreach ($schedules as $schedule) {
            $rows[] = [
                $schedule->id,
                $schedule->name,
                $schedule->business->name ?? 'N/A',
                $schedule->location->name ?? 'N/A',
                ucfirst($schedule->sync_type),
                $schedule->cron_expression,
                $schedule->is_active ? 'Yes' : 'No',
                $schedule->next_run_human ?? 'Not scheduled',
                $schedule->success_rate . '%'
            ];
        }

        $this->table($headers, $rows);
    }

    private function enableSchedule($scheduleId)
    {
        if (!$scheduleId) {
            $this->error('Schedule ID is required');
            return;
        }

        $schedule = WoocommerceSyncSchedule::find($scheduleId);
        if (!$schedule) {
            $this->error("Schedule {$scheduleId} not found");
            return;
        }

        $schedule->resume();
        $this->info("Schedule '{$schedule->name}' enabled");
    }

    private function disableSchedule($scheduleId)
    {
        if (!$scheduleId) {
            $this->error('Schedule ID is required');
            return;
        }

        $schedule = WoocommerceSyncSchedule::find($scheduleId);
        if (!$schedule) {
            $this->error("Schedule {$scheduleId} not found");
            return;
        }

        $schedule->pause();
        $this->info("Schedule '{$schedule->name}' disabled");
    }

    private function deleteSchedule($scheduleId)
    {
        if (!$scheduleId) {
            $this->error('Schedule ID is required');
            return;
        }

        $schedule = WoocommerceSyncSchedule::find($scheduleId);
        if (!$schedule) {
            $this->error("Schedule {$scheduleId} not found");
            return;
        }

        if (!$this->confirm("Are you sure you want to delete schedule '{$schedule->name}'?")) {
            $this->info('Operation cancelled');
            return;
        }

        $schedule->delete();
        $this->info("Schedule '{$schedule->name}' deleted");
    }
}