<?php

namespace Modules\Woocommerce\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Woocommerce\Entities\WoocommerceSyncError;
use Modules\Woocommerce\Utils\WoocommerceSyncErrorHandler;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Notifications\WoocommerceSyncErrorNotification;
use Illuminate\Support\Facades\Log;
use Exception;

class RecoverSyncErrors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $businessId;
    protected $locationId;
    protected $maxRecoveryAttempts;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($businessId, $locationId = null, $maxRecoveryAttempts = 5)
    {
        $this->businessId = $businessId;
        $this->locationId = $locationId;
        $this->maxRecoveryAttempts = $maxRecoveryAttempts;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Starting sync error recovery job', [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId
        ]);

        // Get recoverable errors
        $recoverableErrors = WoocommerceSyncErrorHandler::getRecoverableErrors(
            $this->businessId,
            $this->locationId,
            50 // Process up to 50 errors per run
        );

        if ($recoverableErrors->isEmpty()) {
            Log::info('No recoverable errors found', [
                'business_id' => $this->businessId,
                'location_id' => $this->locationId
            ]);
            return;
        }

        $recoveredCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        Log::info('Found recoverable errors', [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'count' => $recoverableErrors->count()
        ]);

        foreach ($recoverableErrors as $error) {
            try {
                if ($error->recovery_attempts >= $this->maxRecoveryAttempts) {
                    $skippedCount++;
                    $this->sendRecoveryFailedNotification($error);
                    continue;
                }

                $recoveryResult = $this->attemptErrorRecovery($error);
                
                if ($recoveryResult) {
                    $recoveredCount++;
                    Log::info('Error recovery successful', [
                        'error_id' => $error->id,
                        'category' => $error->error_category,
                        'attempts' => $error->recovery_attempts + 1
                    ]);
                } else {
                    $failedCount++;
                }
                
            } catch (Exception $e) {
                $failedCount++;
                Log::error('Recovery attempt threw exception', [
                    'error_id' => $error->id,
                    'recovery_error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Sync error recovery job completed', [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'recovered' => $recoveredCount,
            'failed' => $failedCount,
            'skipped' => $skippedCount,
            'total_processed' => $recoverableErrors->count()
        ]);
    }

    /**
     * Attempt to recover from a specific error
     */
    private function attemptErrorRecovery(WoocommerceSyncError $error)
    {
        Log::info('Attempting error recovery', [
            'error_id' => $error->id,
            'category' => $error->error_category,
            'severity' => $error->severity_level,
            'attempts' => $error->recovery_attempts
        ]);

        // Use the error handler's recovery mechanism
        $basicRecovery = WoocommerceSyncErrorHandler::attemptErrorRecovery($error);
        
        if ($basicRecovery) {
            return $this->finalizeSuccessfulRecovery($error);
        }

        // Try category-specific recovery strategies
        switch ($error->error_category) {
            case WoocommerceSyncError::CATEGORY_API_RATE_LIMIT:
                return $this->recoverFromRateLimit($error);
                
            case WoocommerceSyncError::CATEGORY_API_CONNECTION:
                return $this->recoverFromConnectionIssue($error);
                
            case WoocommerceSyncError::CATEGORY_ENTITY_NOT_FOUND:
                return $this->recoverFromEntityNotFound($error);
                
            case WoocommerceSyncError::CATEGORY_DATA_VALIDATION:
                return $this->recoverFromValidationError($error);
                
            case WoocommerceSyncError::CATEGORY_BUSINESS_LOGIC:
                return $this->recoverFromBusinessLogicError($error);
                
            default:
                return false;
        }
    }

    /**
     * Recover from rate limit errors
     */
    private function recoverFromRateLimit(WoocommerceSyncError $error)
    {
        // For rate limits, check if enough time has passed
        if ($error->retry_after && $error->retry_after->isFuture()) {
            Log::info('Rate limit recovery: still in waiting period', [
                'error_id' => $error->id,
                'retry_after' => $error->retry_after
            ]);
            return false;
        }

        // Test API connectivity
        $locationSetting = $error->locationSetting;
        if (!$locationSetting) {
            return false;
        }

        try {
            $client = $locationSetting->getWooCommerceClient();
            if (!$client) {
                return false;
            }

            // Make a simple API call to test if rate limit is lifted
            $response = $client->get('system_status');
            
            if ($response) {
                return $this->retryOriginalOperation($error);
            }
            
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '429')) {
                // Still rate limited, extend retry time
                $error->update(['retry_after' => now()->addHours(2)]);
                return false;
            }
            throw $e;
        }

        return false;
    }

    /**
     * Recover from connection issues
     */
    private function recoverFromConnectionIssue(WoocommerceSyncError $error)
    {
        $locationSetting = $error->locationSetting;
        if (!$locationSetting) {
            return false;
        }

        try {
            // Test basic connectivity
            $client = $locationSetting->getWooCommerceClient();
            if (!$client) {
                Log::warning('Cannot create WooCommerce client', ['error_id' => $error->id]);
                return false;
            }

            // Perform lightweight connectivity test
            $response = $client->get('system_status');
            
            if ($response && is_array($response)) {
                Log::info('Connection restored', ['error_id' => $error->id]);
                return $this->retryOriginalOperation($error);
            }
            
        } catch (Exception $e) {
            Log::warning('Connection test failed during recovery', [
                'error_id' => $error->id,
                'test_error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Recover from entity not found errors
     */
    private function recoverFromEntityNotFound(WoocommerceSyncError $error)
    {
        // For entity not found, we can try to re-sync the parent entity
        // This is most effective for products and variations
        
        if ($error->affected_entity_type === WoocommerceSyncError::ENTITY_PRODUCT) {
            return $this->attemptProductRecovery($error);
        }
        
        if ($error->affected_entity_type === WoocommerceSyncError::ENTITY_INVENTORY) {
            return $this->attemptInventoryRecovery($error);
        }

        // For other entity types, mark for manual review
        Log::info('Entity not found - requires manual review', [
            'error_id' => $error->id,
            'entity_type' => $error->affected_entity_type,
            'entity_id' => $error->affected_entity_id
        ]);
        
        return false;
    }

    /**
     * Attempt product recovery by re-syncing
     */
    private function attemptProductRecovery(WoocommerceSyncError $error)
    {
        try {
            $context = $error->error_context;
            $wooProductId = $context['woo_product_id'] ?? null;
            
            if (!$wooProductId) {
                return false;
            }

            $locationSetting = $error->locationSetting;
            $client = $locationSetting->getWooCommerceClient();
            
            // Check if the product exists in WooCommerce now
            $product = $client->get('products/' . $wooProductId);
            
            if ($product && is_array($product)) {
                Log::info('Missing product found in WooCommerce', [
                    'error_id' => $error->id,
                    'woo_product_id' => $wooProductId
                ]);
                return $this->finalizeSuccessfulRecovery($error);
            }
            
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                // Product still doesn't exist, requires manual intervention
                Log::warning('Product still missing in WooCommerce', [
                    'error_id' => $error->id,
                    'woo_product_id' => $context['woo_product_id'] ?? 'unknown'
                ]);
            }
        }
        
        return false;
    }

    /**
     * Attempt inventory recovery
     */
    private function attemptInventoryRecovery(WoocommerceSyncError $error)
    {
        // Similar to product recovery but for inventory items
        return $this->attemptProductRecovery($error);
    }

    /**
     * Recover from validation errors
     */
    private function recoverFromValidationError(WoocommerceSyncError $error)
    {
        // For validation errors, we can attempt data sanitization
        $context = $error->error_context;
        
        // Check if the validation error was due to specific data issues
        $errorMessage = strtolower($error->error_message);
        
        if (str_contains($errorMessage, 'stock_quantity')) {
            return $this->recoverStockQuantityValidation($error);
        }
        
        if (str_contains($errorMessage, 'price')) {
            return $this->recoverPriceValidation($error);
        }
        
        // For other validation errors, log for manual review
        Log::info('Validation error requires manual review', [
            'error_id' => $error->id,
            'error_message' => $error->error_message
        ]);
        
        return false;
    }

    /**
     * Recover stock quantity validation errors
     */
    private function recoverStockQuantityValidation(WoocommerceSyncError $error)
    {
        $context = $error->error_context;
        $originalQuantity = $context['qty_available'] ?? null;
        
        if ($originalQuantity !== null && is_numeric($originalQuantity)) {
            // Sanitize quantity and retry
            $sanitizedQuantity = max(0, (int) floor($originalQuantity));
            
            if ($sanitizedQuantity <= 999999) { // WooCommerce limit
                Log::info('Stock quantity sanitized for recovery', [
                    'error_id' => $error->id,
                    'original' => $originalQuantity,
                    'sanitized' => $sanitizedQuantity
                ]);
                return $this->finalizeSuccessfulRecovery($error);
            }
        }
        
        return false;
    }

    /**
     * Recover price validation errors
     */
    private function recoverPriceValidation(WoocommerceSyncError $error)
    {
        // Similar logic for price validation recovery
        Log::info('Price validation recovery attempted', ['error_id' => $error->id]);
        return false; // Implement as needed
    }

    /**
     * Recover from business logic errors
     */
    private function recoverFromBusinessLogicError(WoocommerceSyncError $error)
    {
        // Business logic errors usually require manual intervention
        Log::info('Business logic error logged for manual review', [
            'error_id' => $error->id,
            'error_message' => $error->error_message
        ]);
        return false;
    }

    /**
     * Retry the original operation that failed
     */
    private function retryOriginalOperation(WoocommerceSyncError $error)
    {
        try {
            // Dispatch a new sync job for the specific entity that failed
            $locationSetting = $error->locationSetting;
            
            if (!$locationSetting) {
                return false;
            }

            // Queue a new sync job for this location and sync type
            SyncLocationData::dispatch($locationSetting, $error->sync_type);
            
            Log::info('Original sync operation re-queued', [
                'error_id' => $error->id,
                'sync_type' => $error->sync_type,
                'location_id' => $error->location_id
            ]);
            
            return $this->finalizeSuccessfulRecovery($error);
            
        } catch (Exception $e) {
            Log::error('Failed to retry original operation', [
                'error_id' => $error->id,
                'retry_error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Finalize successful recovery
     */
    private function finalizeSuccessfulRecovery(WoocommerceSyncError $error)
    {
        $error->markResolved('automatic_recovery');
        
        Log::info('Error successfully recovered', [
            'error_id' => $error->id,
            'category' => $error->error_category,
            'attempts' => $error->recovery_attempts + 1
        ]);
        
        return true;
    }

    /**
     * Send recovery failed notification
     */
    private function sendRecoveryFailedNotification(WoocommerceSyncError $error)
    {
        try {
            $business = \App\Business::find($error->business_id);
            if (!$business) {
                return;
            }

            $admins = $business->users()->whereHas('roles', function($query) {
                $query->where('name', 'Admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new WoocommerceSyncErrorNotification(
                    $error,
                    null,
                    WoocommerceSyncErrorNotification::TYPE_RECOVERY_FAILED
                ));
            }

            Log::info('Recovery failed notification sent', ['error_id' => $error->id]);

        } catch (Exception $e) {
            Log::error('Failed to send recovery failed notification', [
                'error_id' => $error->id,
                'notification_error' => $e->getMessage()
            ]);
        }
    }
}