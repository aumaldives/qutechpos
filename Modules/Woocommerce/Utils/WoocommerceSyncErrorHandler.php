<?php

namespace Modules\Woocommerce\Utils;

use Modules\Woocommerce\Entities\WoocommerceSyncError;
use Modules\Woocommerce\Notifications\WoocommerceSyncErrorNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Exception;

class WoocommerceSyncErrorHandler
{
    /**
     * Handle and categorize WooCommerce API errors
     */
    public static function handleApiError(
        Exception $exception,
        $businessId,
        $locationId,
        $syncType,
        $context = []
    ) {
        $errorCode = method_exists($exception, 'getCode') ? $exception->getCode() : null;
        $errorMessage = $exception->getMessage();
        $category = self::categorizeError($exception, $errorCode);
        
        // Enhanced context with stack trace for debugging
        $enhancedContext = array_merge($context, [
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => self::sanitizeStackTrace($exception->getTraceAsString())
        ]);

        // Log to database
        $errorRecord = WoocommerceSyncError::logError(
            $businessId,
            $locationId,
            $syncType,
            $category,
            $errorMessage,
            $enhancedContext,
            $context['entity_type'] ?? null,
            $context['entity_id'] ?? null,
            $errorCode
        );

        // Log to application log with appropriate level
        $logLevel = self::mapSeverityToLogLevel($errorRecord->severity_level);
        Log::log($logLevel, "WooCommerce sync error [{$category}]", [
            'error_id' => $errorRecord->id,
            'business_id' => $businessId,
            'location_id' => $locationId,
            'sync_type' => $syncType,
            'error_code' => $errorCode,
            'message' => $errorMessage
        ]);

        // Send notifications for critical errors
        self::handleErrorNotifications($errorRecord);

        return $errorRecord;
    }

    /**
     * Categorize error based on exception type and error code
     */
    private static function categorizeError(Exception $exception, $errorCode)
    {
        // Handle WooCommerce specific exceptions
        if (str_contains(get_class($exception), 'HttpClientException')) {
            switch ($errorCode) {
                case 401:
                case 403:
                    return WoocommerceSyncError::CATEGORY_API_AUTH;
                case 429:
                    return WoocommerceSyncError::CATEGORY_API_RATE_LIMIT;
                case 404:
                    return WoocommerceSyncError::CATEGORY_ENTITY_NOT_FOUND;
                case 500:
                case 502:
                case 503:
                case 504:
                    return WoocommerceSyncError::CATEGORY_API_CONNECTION;
                default:
                    return WoocommerceSyncError::CATEGORY_API_CONNECTION;
            }
        }

        // Handle validation exceptions
        if (str_contains(get_class($exception), 'ValidationException') ||
            str_contains($exception->getMessage(), 'validation')) {
            return WoocommerceSyncError::CATEGORY_DATA_VALIDATION;
        }

        // Handle configuration issues
        if (str_contains($exception->getMessage(), 'configuration') ||
            str_contains($exception->getMessage(), 'API key') ||
            str_contains($exception->getMessage(), 'credentials')) {
            return WoocommerceSyncError::CATEGORY_CONFIGURATION;
        }

        // Handle business logic errors
        if (str_contains($exception->getMessage(), 'not found') ||
            str_contains($exception->getMessage(), 'missing') ||
            str_contains($exception->getMessage(), 'invalid')) {
            return WoocommerceSyncError::CATEGORY_BUSINESS_LOGIC;
        }

        // Default to system error
        return WoocommerceSyncError::CATEGORY_SYSTEM_ERROR;
    }

    /**
     * Map severity level to log level
     */
    private static function mapSeverityToLogLevel($severity)
    {
        switch ($severity) {
            case WoocommerceSyncError::SEVERITY_CRITICAL:
                return 'critical';
            case WoocommerceSyncError::SEVERITY_HIGH:
                return 'error';
            case WoocommerceSyncError::SEVERITY_MEDIUM:
                return 'warning';
            case WoocommerceSyncError::SEVERITY_LOW:
                return 'info';
            default:
                return 'warning';
        }
    }

    /**
     * Sanitize stack trace for database storage
     */
    private static function sanitizeStackTrace($trace)
    {
        // Limit trace length and remove sensitive information
        $trace = substr($trace, 0, 2000);
        
        // Remove absolute paths for security
        $trace = preg_replace('/\/[^:\s]+\//', '.../', $trace);
        
        return $trace;
    }

    /**
     * Handle product-specific errors with enhanced context
     */
    public static function handleProductError(
        Exception $exception,
        $businessId,
        $locationId,
        $syncType,
        $product,
        $wooProductId = null
    ) {
        $context = [
            'entity_type' => WoocommerceSyncError::ENTITY_PRODUCT,
            'entity_id' => $product['id'] ?? $product->id ?? null,
            'product_name' => $product['name'] ?? $product->name ?? 'Unknown',
            'woo_product_id' => $wooProductId,
            'product_type' => $product['type'] ?? $product->type ?? null
        ];

        return self::handleApiError($exception, $businessId, $locationId, $syncType, $context);
    }

    /**
     * Handle order-specific errors with enhanced context
     */
    public static function handleOrderError(
        Exception $exception,
        $businessId,
        $locationId,
        $syncType,
        $order,
        $wooOrderId = null
    ) {
        $context = [
            'entity_type' => WoocommerceSyncError::ENTITY_ORDER,
            'entity_id' => $order['id'] ?? $order->id ?? null,
            'order_number' => $order['number'] ?? $order->invoice_no ?? 'Unknown',
            'woo_order_id' => $wooOrderId,
            'order_status' => $order['status'] ?? $order->status ?? null,
            'order_total' => $order['total'] ?? $order->final_total ?? null
        ];

        return self::handleApiError($exception, $businessId, $locationId, $syncType, $context);
    }

    /**
     * Handle customer-specific errors with enhanced context
     */
    public static function handleCustomerError(
        Exception $exception,
        $businessId,
        $locationId,
        $syncType,
        $customer,
        $wooCustomerId = null
    ) {
        $context = [
            'entity_type' => WoocommerceSyncError::ENTITY_CUSTOMER,
            'entity_id' => $customer['id'] ?? $customer->id ?? null,
            'customer_email' => $customer['email'] ?? $customer->email ?? 'Unknown',
            'woo_customer_id' => $wooCustomerId,
            'customer_name' => ($customer['first_name'] ?? $customer->first_name ?? '') . ' ' . 
                             ($customer['last_name'] ?? $customer->last_name ?? '')
        ];

        return self::handleApiError($exception, $businessId, $locationId, $syncType, $context);
    }

    /**
     * Handle inventory-specific errors with enhanced context
     */
    public static function handleInventoryError(
        Exception $exception,
        $businessId,
        $locationId,
        $syncType,
        $inventoryItem
    ) {
        $context = [
            'entity_type' => WoocommerceSyncError::ENTITY_INVENTORY,
            'entity_id' => $inventoryItem->product_id ?? null,
            'product_name' => $inventoryItem->product_name ?? 'Unknown',
            'variation_name' => $inventoryItem->variation_name ?? null,
            'woo_product_id' => $inventoryItem->woocommerce_product_id ?? null,
            'woo_variation_id' => $inventoryItem->woocommerce_variation_id ?? null,
            'qty_available' => $inventoryItem->qty_available ?? null
        ];

        return self::handleApiError($exception, $businessId, $locationId, $syncType, $context);
    }

    /**
     * Get recoverable errors for automatic retry
     */
    public static function getRecoverableErrors($businessId, $locationId = null, $limit = 50)
    {
        $query = WoocommerceSyncError::where('business_id', $businessId)
                                    ->unresolved()
                                    ->retryable()
                                    ->where('recovery_attempts', '<', 5) // Max 5 attempts
                                    ->whereNotIn('error_category', [
                                        WoocommerceSyncError::CATEGORY_API_AUTH,
                                        WoocommerceSyncError::CATEGORY_CONFIGURATION
                                    ])
                                    ->orderBy('severity_level', 'desc')
                                    ->orderBy('created_at', 'asc')
                                    ->limit($limit);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->get();
    }

    /**
     * Attempt to recover from specific error types
     */
    public static function attemptErrorRecovery(WoocommerceSyncError $error)
    {
        $error->incrementRecoveryAttempts();
        
        try {
            switch ($error->error_category) {
                case WoocommerceSyncError::CATEGORY_API_RATE_LIMIT:
                    return self::handleRateLimitRecovery($error);
                    
                case WoocommerceSyncError::CATEGORY_API_CONNECTION:
                    return self::handleConnectionRecovery($error);
                    
                case WoocommerceSyncError::CATEGORY_ENTITY_NOT_FOUND:
                    return self::handleEntityNotFoundRecovery($error);
                    
                case WoocommerceSyncError::CATEGORY_DATA_VALIDATION:
                    return self::handleValidationRecovery($error);
                    
                default:
                    return false;
            }
        } catch (Exception $e) {
            Log::error('Error recovery attempt failed', [
                'error_id' => $error->id,
                'recovery_error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle rate limit recovery by scheduling retry
     */
    private static function handleRateLimitRecovery(WoocommerceSyncError $error)
    {
        // For rate limits, just wait - the retry_after is already set
        Log::info('Rate limit recovery scheduled', [
            'error_id' => $error->id,
            'retry_after' => $error->retry_after
        ]);
        return true;
    }

    /**
     * Handle connection recovery by testing API connectivity
     */
    private static function handleConnectionRecovery(WoocommerceSyncError $error)
    {
        $locationSetting = $error->locationSetting;
        if (!$locationSetting) {
            return false;
        }

        try {
            $client = $locationSetting->getWooCommerceClient();
            if (!$client) {
                return false;
            }

            // Test connection with simple API call
            $client->get('system_status');
            
            Log::info('Connection recovery successful', ['error_id' => $error->id]);
            return true;
            
        } catch (Exception $e) {
            Log::warning('Connection recovery failed', [
                'error_id' => $error->id,
                'test_error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle entity not found recovery
     */
    private static function handleEntityNotFoundRecovery(WoocommerceSyncError $error)
    {
        // For missing entities, check if they exist now
        // This is a placeholder - specific logic depends on entity type
        Log::info('Entity recovery attempted', ['error_id' => $error->id]);
        return false; // Implement specific logic based on needs
    }

    /**
     * Handle validation error recovery
     */
    private static function handleValidationRecovery(WoocommerceSyncError $error)
    {
        // For validation errors, could implement data sanitization
        // This is a placeholder for future enhancement
        Log::info('Validation recovery attempted', ['error_id' => $error->id]);
        return false; // Implement specific logic based on needs
    }

    /**
     * Handle error notifications based on severity and conditions
     */
    public static function handleErrorNotifications(WoocommerceSyncError $error)
    {
        try {
            // Get business administrators
            $business = \App\Business::find($error->business_id);
            if (!$business) {
                return;
            }

            $admins = $business->users()->whereHas('roles', function($query) {
                $query->where('name', 'Admin');
            })->get();

            if ($admins->isEmpty()) {
                return;
            }

            // Send critical error notifications immediately
            if ($error->severity_level === WoocommerceSyncError::SEVERITY_CRITICAL) {
                foreach ($admins as $admin) {
                    $admin->notify(new WoocommerceSyncErrorNotification(
                        $error, 
                        null, 
                        WoocommerceSyncErrorNotification::TYPE_CRITICAL_ERROR
                    ));
                }
                
                Log::info('Critical error notification sent', ['error_id' => $error->id]);
            }

            // Check if error threshold has been exceeded
            self::checkErrorThresholdNotification($error, $admins);

        } catch (Exception $e) {
            Log::error('Failed to send error notification', [
                'error_id' => $error->id,
                'notification_error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if error threshold has been exceeded and send notifications
     */
    private static function checkErrorThresholdNotification(WoocommerceSyncError $error, $admins)
    {
        $threshold = 10; // 10 errors in 24 hours
        $timeFrame = now()->subHours(24);

        $recentErrors = WoocommerceSyncError::where('business_id', $error->business_id)
                                          ->where('location_id', $error->location_id)
                                          ->where('created_at', '>=', $timeFrame)
                                          ->count();

        if ($recentErrors >= $threshold) {
            // Check if we already sent threshold notification recently (within 6 hours)
            $recentThresholdNotification = \DB::table('notifications')
                ->where('type', 'Modules\\Woocommerce\\Notifications\\WoocommerceSyncErrorNotification')
                ->where('created_at', '>=', now()->subHours(6))
                ->whereRaw("JSON_EXTRACT(data, '$.type') = ?", [WoocommerceSyncErrorNotification::TYPE_ERROR_THRESHOLD])
                ->exists();

            if (!$recentThresholdNotification) {
                $errorStats = self::generateErrorStatistics($error->business_id, $error->location_id);
                
                foreach ($admins as $admin) {
                    $admin->notify(new WoocommerceSyncErrorNotification(
                        null,
                        $errorStats,
                        WoocommerceSyncErrorNotification::TYPE_ERROR_THRESHOLD
                    ));
                }

                Log::info('Error threshold notification sent', [
                    'business_id' => $error->business_id,
                    'location_id' => $error->location_id,
                    'error_count' => $recentErrors
                ]);
            }
        }
    }

    /**
     * Generate error statistics for notifications
     */
    public static function generateErrorStatistics($businessId, $locationId = null, $hours = 24)
    {
        $timeFrame = now()->subHours($hours);
        $query = WoocommerceSyncError::where('business_id', $businessId)
                                    ->where('created_at', '>=', $timeFrame);

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        $business = \App\Business::find($businessId);
        $location = $locationId ? \App\BusinessLocation::find($locationId) : null;

        $stats = [
            'business_name' => $business->name ?? 'Unknown Business',
            'location_name' => $location->name ?? 'All Locations',
            'total_errors' => $query->count(),
            'critical_errors' => $query->where('severity_level', WoocommerceSyncError::SEVERITY_CRITICAL)->count(),
            'unresolved_errors' => $query->where('is_resolved', false)->count(),
            'by_category' => $query->groupBy('error_category')
                                 ->selectRaw('error_category, COUNT(*) as count')
                                 ->pluck('count', 'error_category')
                                 ->toArray()
        ];

        return $stats;
    }

    /**
     * Send daily summary notifications
     */
    public static function sendDailySummaryNotifications()
    {
        $businesses = \App\Business::whereHas('locations.woocommerceLocationSettings')->get();

        foreach ($businesses as $business) {
            try {
                $admins = $business->users()->whereHas('roles', function($query) {
                    $query->where('name', 'Admin');
                })->get();

                if ($admins->isEmpty()) {
                    continue;
                }

                // Generate comprehensive stats for the business
                $overallStats = self::generateDailyStats($business->id);

                if ($overallStats['overall']['total_syncs'] > 0 || $overallStats['overall']['new_errors'] > 0) {
                    foreach ($admins as $admin) {
                        $admin->notify(new WoocommerceSyncErrorNotification(
                            null,
                            $overallStats,
                            WoocommerceSyncErrorNotification::TYPE_DAILY_SUMMARY
                        ));
                    }
                }

                Log::info('Daily summary notification sent', ['business_id' => $business->id]);

            } catch (Exception $e) {
                Log::error('Failed to send daily summary', [
                    'business_id' => $business->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Generate comprehensive daily statistics
     */
    private static function generateDailyStats($businessId)
    {
        $timeFrame = now()->subHours(24);
        $business = \App\Business::find($businessId);

        // Overall statistics
        $totalSyncs = \DB::table('woocommerce_sync_logs')
                        ->where('business_id', $businessId)
                        ->where('created_at', '>=', $timeFrame)
                        ->count();

        $successfulSyncs = \DB::table('woocommerce_sync_logs')
                             ->where('business_id', $businessId)
                             ->where('created_at', '>=', $timeFrame)
                             ->where('status', 'completed')
                             ->count();

        $newErrors = WoocommerceSyncError::where('business_id', $businessId)
                                        ->where('created_at', '>=', $timeFrame)
                                        ->count();

        $resolvedErrors = WoocommerceSyncError::where('business_id', $businessId)
                                             ->where('updated_at', '>=', $timeFrame)
                                             ->where('is_resolved', true)
                                             ->count();

        $autoRecovered = WoocommerceSyncError::where('business_id', $businessId)
                                            ->where('updated_at', '>=', $timeFrame)
                                            ->where('resolution_type', 'automatic_recovery')
                                            ->count();

        $pendingErrors = WoocommerceSyncError::where('business_id', $businessId)
                                            ->where('is_resolved', false)
                                            ->count();

        $stats = [
            'business_name' => $business->name,
            'overall' => [
                'total_syncs' => $totalSyncs,
                'successful_syncs' => $successfulSyncs,
                'failed_syncs' => $totalSyncs - $successfulSyncs,
                'success_rate' => $totalSyncs > 0 ? ($successfulSyncs / $totalSyncs) * 100 : 100,
                'new_errors' => $newErrors,
                'resolved_errors' => $resolvedErrors,
                'auto_recovered' => $autoRecovered,
                'pending_errors' => $pendingErrors
            ],
            'locations' => []
        ];

        // Per-location breakdown
        $locations = \App\BusinessLocation::where('business_id', $businessId)
                                         ->whereHas('woocommerceLocationSettings')
                                         ->get();

        foreach ($locations as $location) {
            $locationSyncs = \DB::table('woocommerce_sync_logs')
                               ->where('business_id', $businessId)
                               ->where('location_id', $location->id)
                               ->where('created_at', '>=', $timeFrame)
                               ->count();

            $locationErrors = WoocommerceSyncError::where('business_id', $businessId)
                                                 ->where('location_id', $location->id)
                                                 ->where('created_at', '>=', $timeFrame)
                                                 ->count();

            $stats['locations'][] = [
                'name' => $location->name,
                'sync_count' => $locationSyncs,
                'error_count' => $locationErrors
            ];
        }

        return $stats;
    }
}