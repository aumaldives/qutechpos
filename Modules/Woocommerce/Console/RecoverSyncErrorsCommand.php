<?php

namespace Modules\Woocommerce\Console;

use Illuminate\Console\Command;
use Modules\Woocommerce\Jobs\RecoverSyncErrors;
use Modules\Woocommerce\Entities\WoocommerceSyncError;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;

class RecoverSyncErrorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'woocommerce:recover-errors
                            {--business-id= : Specific business ID to process}
                            {--location-id= : Specific location ID to process}  
                            {--dry-run : Show what would be recovered without actually doing it}
                            {--max-attempts=5 : Maximum recovery attempts per error}';

    /**
     * The console command description.
     */
    protected $description = 'Recover from WooCommerce sync errors automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessId = $this->option('business-id');
        $locationId = $this->option('location-id');
        $dryRun = $this->option('dry-run');
        $maxAttempts = (int) $this->option('max-attempts');

        $this->info('🔄 WooCommerce Sync Error Recovery');
        $this->info('================================');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No actual recovery will be performed');
        }

        // Get businesses to process
        $businesses = $this->getBusinessesToProcess($businessId);
        
        if ($businesses->isEmpty()) {
            $this->error('❌ No businesses found with WooCommerce configurations');
            return 1;
        }

        $this->info("📊 Processing {$businesses->count()} business(es)...");

        foreach ($businesses as $business) {
            $this->processBusinessErrors($business, $locationId, $dryRun, $maxAttempts);
        }

        $this->info('✅ Error recovery process completed');
        return 0;
    }

    /**
     * Get businesses to process based on options
     */
    private function getBusinessesToProcess($businessId)
    {
        $query = \App\Business::query();
        
        if ($businessId) {
            $query->where('id', $businessId);
        }
        
        // Only get businesses that have WooCommerce location settings
        return $query->whereHas('locations.woocommerceLocationSettings')->get();
    }

    /**
     * Process errors for a specific business
     */
    private function processBusinessErrors($business, $locationId, $dryRun, $maxAttempts)
    {
        $this->info("🏢 Processing business: {$business->name} (ID: {$business->id})");

        // Get locations for this business
        $locations = $business->locations();
        
        if ($locationId) {
            $locations->where('id', $locationId);
        }
        
        $locations = $locations->whereHas('woocommerceLocationSettings')->get();

        if ($locations->isEmpty()) {
            $this->warn("   ⚠️  No WooCommerce-configured locations found");
            return;
        }

        foreach ($locations as $location) {
            $this->processLocationErrors($business->id, $location, $dryRun, $maxAttempts);
        }
    }

    /**
     * Process errors for a specific location
     */
    private function processLocationErrors($businessId, $location, $dryRun, $maxAttempts)
    {
        $this->info("   📍 Location: {$location->name} (ID: {$location->id})");

        // Get error statistics
        $errorStats = WoocommerceSyncError::where('business_id', $businessId)
                                          ->where('location_id', $location->id)
                                          ->selectRaw('
                                              COUNT(*) as total_errors,
                                              SUM(CASE WHEN is_resolved = 0 THEN 1 ELSE 0 END) as unresolved_errors,
                                              SUM(CASE WHEN is_resolved = 0 AND (retry_after IS NULL OR retry_after <= NOW()) THEN 1 ELSE 0 END) as recoverable_errors,
                                              SUM(CASE WHEN severity_level = "critical" AND is_resolved = 0 THEN 1 ELSE 0 END) as critical_errors
                                          ')
                                          ->first();

        if ($errorStats->total_errors == 0) {
            $this->info("      ✅ No sync errors found");
            return;
        }

        $this->info("      📊 Error Statistics:");
        $this->info("         Total Errors: {$errorStats->total_errors}");
        $this->info("         Unresolved: {$errorStats->unresolved_errors}");
        $this->info("         Recoverable: {$errorStats->recoverable_errors}");
        $this->info("         Critical: {$errorStats->critical_errors}");

        if ($errorStats->recoverable_errors == 0) {
            $this->info("      ℹ️  No recoverable errors at this time");
            return;
        }

        if ($dryRun) {
            $this->showRecoverableErrors($businessId, $location->id);
            return;
        }

        // Dispatch recovery job
        $this->info("      🚀 Dispatching recovery job...");
        
        try {
            RecoverSyncErrors::dispatch($businessId, $location->id, $maxAttempts);
            $this->info("      ✅ Recovery job dispatched successfully");
        } catch (\Exception $e) {
            $this->error("      ❌ Failed to dispatch recovery job: {$e->getMessage()}");
        }
    }

    /**
     * Show recoverable errors in dry run mode
     */
    private function showRecoverableErrors($businessId, $locationId)
    {
        $errors = WoocommerceSyncError::where('business_id', $businessId)
                                     ->where('location_id', $locationId)
                                     ->unresolved()
                                     ->retryable()
                                     ->where('recovery_attempts', '<', 5)
                                     ->orderBy('severity_level', 'desc')
                                     ->orderBy('created_at', 'asc')
                                     ->limit(10)
                                     ->get();

        if ($errors->isEmpty()) {
            return;
        }

        $this->info("      🔍 Recoverable Errors (showing first 10):");
        
        $headers = ['ID', 'Category', 'Severity', 'Entity', 'Attempts', 'Created'];
        $rows = [];
        
        foreach ($errors as $error) {
            $rows[] = [
                $error->id,
                $error->error_category,
                $error->severity_level,
                $error->affected_entity_type ?? 'N/A',
                $error->recovery_attempts,
                $error->created_at->format('Y-m-d H:i')
            ];
        }
        
        $this->table($headers, $rows);
    }
}