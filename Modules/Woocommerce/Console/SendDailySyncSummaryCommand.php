<?php

namespace Modules\Woocommerce\Console;

use Illuminate\Console\Command;
use Modules\Woocommerce\Utils\WoocommerceSyncErrorHandler;
use Illuminate\Support\Facades\Log;

class SendDailySyncSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'woocommerce:send-daily-summary
                            {--business-id= : Send summary for specific business only}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     */
    protected $description = 'Send daily WooCommerce sync summary notifications to business administrators';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessId = $this->option('business-id');
        $dryRun = $this->option('dry-run');

        $this->info('📊 WooCommerce Daily Summary Notifications');
        $this->info('===========================================');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No notifications will be sent');
        }

        if ($businessId) {
            $this->processBusiness($businessId, $dryRun);
        } else {
            $this->processAllBusinesses($dryRun);
        }

        $this->info('✅ Daily summary process completed');
        return 0;
    }

    /**
     * Process all businesses with WooCommerce integration
     */
    private function processAllBusinesses($dryRun)
    {
        $businesses = \App\Business::whereHas('locations.woocommerceLocationSettings')->get();

        if ($businesses->isEmpty()) {
            $this->error('❌ No businesses found with WooCommerce configurations');
            return;
        }

        $this->info("📊 Processing {$businesses->count()} business(es)...");

        foreach ($businesses as $business) {
            $this->processBusiness($business->id, $dryRun);
        }
    }

    /**
     * Process individual business
     */
    private function processBusiness($businessId, $dryRun)
    {
        $business = \App\Business::find($businessId);
        if (!$business) {
            $this->error("❌ Business with ID {$businessId} not found");
            return;
        }

        $this->info("🏢 Processing business: {$business->name} (ID: {$business->id})");

        // Get business administrators
        $admins = $business->users()->whereHas('roles', function($query) {
            $query->where('name', 'Admin');
        })->get();

        if ($admins->isEmpty()) {
            $this->warn("   ⚠️  No administrators found for this business");
            return;
        }

        // Generate daily statistics
        $stats = WoocommerceSyncErrorHandler::generateDailyStats($business->id);
        
        $this->info("   📈 Daily Statistics:");
        $this->info("      Total Syncs: {$stats['overall']['total_syncs']}");
        $this->info("      Success Rate: " . number_format($stats['overall']['success_rate'], 1) . "%");
        $this->info("      New Errors: {$stats['overall']['new_errors']}");
        $this->info("      Resolved Errors: {$stats['overall']['resolved_errors']}");
        $this->info("      Pending Errors: {$stats['overall']['pending_errors']}");

        // Show location breakdown
        if (!empty($stats['locations'])) {
            $this->info("   📍 Location Breakdown:");
            foreach ($stats['locations'] as $location) {
                $status = $location['error_count'] > 0 ? '⚠️  Issues' : '✅ Healthy';
                $this->info("      {$location['name']}: {$location['sync_count']} syncs, {$location['error_count']} errors - {$status}");
            }
        }

        // Check if we should send notification
        $shouldSend = $stats['overall']['total_syncs'] > 0 || $stats['overall']['new_errors'] > 0;

        if (!$shouldSend) {
            $this->info("   ℹ️  No activity detected, skipping notification");
            return;
        }

        if ($dryRun) {
            $this->info("   📧 Would send daily summary to {$admins->count()} administrator(s):");
            foreach ($admins as $admin) {
                $this->info("      - {$admin->first_name} {$admin->last_name} ({$admin->email})");
            }
        } else {
            try {
                WoocommerceSyncErrorHandler::sendDailySummaryNotifications();
                $this->info("   ✅ Daily summary sent to {$admins->count()} administrator(s)");
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to send notifications: {$e->getMessage()}");
                Log::error('Failed to send daily summary via command', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}