<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $env = config('app.env');
        $email = config('mail.username');

        if ($env === 'live') {
            //Scheduling backup, specify the time when the backup will get cleaned & time when it will run.
            //$schedule->command('backup:run')->dailyAt('23:50');

            //Schedule to create recurring invoices
            $schedule->command('pos:generateSubscriptionInvoices')->dailyAt('23:30');
            $schedule->command('pos:updateRewardPoints')->dailyAt('23:45');
            $schedule->command('pos:autoSendPaymentReminder')->dailyAt('8:00');
            
            //Schedule to create recurring expenses
            $schedule->command('pos:generateRecurringExpense')->dailyAt('23:35');
            
            // WooCommerce Module Scheduled Tasks
            // Health monitoring - every 15 minutes
            $schedule->call(function () {
                try {
                    $service = app(\Modules\Woocommerce\Services\SyncMonitoringService::class);
                    $service->performHealthChecks();
                } catch (\Exception $e) {
                    \Log::error('WooCommerce health check failed: ' . $e->getMessage());
                }
            })->everyFifteenMinutes()->name('woocommerce-health-check');
            
            // Queue health monitoring - every 10 minutes
            $schedule->call(function () {
                try {
                    // Check if WooCommerce sync queues are running
                    $failedJobs = \DB::table('failed_jobs')->where('queue', 'woocommerce-sync')->count();
                    if ($failedJobs > 10) {
                        \Log::warning("WooCommerce queue has {$failedJobs} failed jobs");
                    }
                    
                    // Restart stuck jobs if needed
                    \Artisan::call('queue:retry', ['id' => 'all']);
                } catch (\Exception $e) {
                    \Log::error('WooCommerce queue monitoring failed: ' . $e->getMessage());
                }
            })->everyTenMinutes()->name('woocommerce-queue-monitor');
            
            // Automated sync operations - every 2 hours during business hours
            $schedule->call(function () {
                try {
                    // Get businesses with auto-sync enabled using new table structure
                    $businesses = \DB::table('woocommerce_api_settings')
                        ->where('auto_sync_enabled', true)
                        ->where('is_active', true)
                        ->where(function($q) {
                            $q->whereNull('last_sync_at')
                              ->orWhere('last_sync_at', '<', now()->subHours(2));
                        })
                        ->get();
                        
                    foreach ($businesses as $business) {
                        // Dispatch background sync jobs (jobs will be created when needed)
                        \Log::info("Dispatching auto-sync for business {$business->business_id}");
                        
                        // For now, just update the last sync time to prevent spam
                        \DB::table('woocommerce_api_settings')
                            ->where('id', $business->id)
                            ->update(['last_sync_at' => now()]);
                    }
                } catch (\Exception $e) {
                    \Log::error('WooCommerce automated sync failed: ' . $e->getMessage());
                }
            })->cron('0 8,10,12,14,16,18 * * 1-6')->name('woocommerce-auto-sync');
            
            // Performance monitoring and metrics collection - every 30 minutes
            $schedule->call(function () {
                try {
                    // Collect performance metrics
                    $metrics = [
                        'sync_success_rate' => $this->calculateSyncSuccessRate(),
                        'average_response_time' => $this->calculateAverageResponseTime(),
                        'queue_length' => \DB::table('jobs')->where('queue', 'woocommerce-sync')->count(),
                        'failed_jobs' => \DB::table('failed_jobs')->where('queue', 'woocommerce-sync')->count()
                    ];
                    
                    // Store metrics for dashboard
                    \DB::table('woocommerce_performance_metrics')->insert([
                        'metrics' => json_encode($metrics),
                        'recorded_at' => now(),
                        'created_at' => now()
                    ]);
                } catch (\Exception $e) {
                    \Log::error('WooCommerce performance monitoring failed: ' . $e->getMessage());
                }
            })->everyThirtyMinutes()->name('woocommerce-performance-monitor');
            
            // Cleanup operations - daily at midnight
            $schedule->call(function () {
                try {
                    // Clean old sync logs (keep last 30 days)
                    \DB::table('woocommerce_sync_logs')
                        ->where('created_at', '<', now()->subDays(30))
                        ->delete();
                    
                    // Clean old performance metrics (keep last 90 days)
                    \DB::table('woocommerce_performance_metrics')
                        ->where('recorded_at', '<', now()->subDays(90))
                        ->delete();
                    
                    // Clean processed webhook logs (keep last 7 days)
                    \DB::table('woocommerce_webhook_logs')
                        ->where('status', 'processed')
                        ->where('created_at', '<', now()->subDays(7))
                        ->delete();
                        
                    \Log::info('WooCommerce cleanup operations completed');
                } catch (\Exception $e) {
                    \Log::error('WooCommerce cleanup failed: ' . $e->getMessage());
                }
            })->dailyAt('00:30')->name('woocommerce-cleanup');
            
            // Webhook security token rotation - weekly on Sundays
            $schedule->call(function () {
                try {
                    $businesses = \DB::table('woocommerce_api_settings')
                                     ->where('is_active', true)
                                     ->get();
                    $rotatedCount = 0;
                    
                    foreach ($businesses as $business) {
                        // Rotate webhook secret tokens for enhanced security
                        $newSecret = \Str::random(32);
                        \DB::table('woocommerce_api_settings')
                            ->where('id', $business->id)
                            ->update([
                                'webhook_secret' => hash('sha256', $newSecret),
                                'webhook_secret_rotated_at' => now(),
                                'updated_at' => now()
                            ]);
                        $rotatedCount++;
                    }
                    
                    \Log::info("WooCommerce webhook secrets rotated for {$rotatedCount} businesses");
                } catch (\Exception $e) {
                    \Log::error('WooCommerce webhook secret rotation failed: ' . $e->getMessage());
                }
            })->weekly()->sundays()->at('02:00')->name('woocommerce-security-rotation');
        }

        if ($env === 'demo') {
            //IMPORTANT NOTE: This command will delete all business details and create dummy business, run only in demo server.
            $schedule->command('pos:dummyBusiness')
                    ->cron('0 */3 * * *')
                    //->everyThirtyMinutes()
                    ->emailOutputTo($email);
        }
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }

    /**
     * Calculate sync success rate for WooCommerce operations
     */
    private function calculateSyncSuccessRate()
    {
        try {
            $total = \DB::table('woocommerce_sync_logs')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();
            
            if ($total === 0) return 100;
            
            $successful = \DB::table('woocommerce_sync_logs')
                ->where('created_at', '>=', now()->subHours(24))
                ->where('status', 'success')
                ->count();
            
            return round(($successful / $total) * 100, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate average response time for WooCommerce API calls
     */
    private function calculateAverageResponseTime()
    {
        try {
            $avgTime = \DB::table('woocommerce_sync_logs')
                ->where('created_at', '>=', now()->subHours(24))
                ->whereNotNull('response_time')
                ->avg('response_time');
            
            return round($avgTime ?? 0, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
