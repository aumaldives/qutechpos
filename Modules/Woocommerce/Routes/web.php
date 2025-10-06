<?php

// Legacy webhook routes (for backward compatibility)
Route::post(
    '/webhook/order-created/{business_id}',
    [Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderCreated']
);
Route::post(
    '/webhook/order-updated/{business_id}',
    [Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderUpdated']
);
Route::post(
    '/webhook/order-deleted/{business_id}',
    [Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderDeleted']
);
Route::post(
    '/webhook/order-restored/{business_id}',
    [Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderRestored']
);

// New location-specific webhook routes
Route::post(
    '/webhook/{location_id}',
    [Modules\Woocommerce\Http\Controllers\WoocommerceLocationWebhookController::class, 'handleWebhook']
)->name('woocommerce.webhook.location');

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('woocommerce')->group(function () {
    Route::get('/install', [Modules\Woocommerce\Http\Controllers\InstallController::class, 'index']);
    Route::get('/install/update', [Modules\Woocommerce\Http\Controllers\InstallController::class, 'update']);
    Route::get('/install/uninstall', [Modules\Woocommerce\Http\Controllers\InstallController::class, 'uninstall']);

    // Modern Blade Configuration Interface (Primary Route)
    Route::get('/', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'index'])->name('woocommerce.index');
    Route::get('/configuration', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'index'])->name('woocommerce.configuration');
    
    // AJAX API endpoints for the Blade interface
    Route::get('/api/settings', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'getSettings']);
    Route::post('/api/settings', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'updateSettings']);
    Route::post('/api/test-connection', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'testConnection']);
    Route::get('/api/stats', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'getStats']);
    Route::post('/api/sync', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'startSync']);
    Route::get('/api/sync-logs', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'getSyncLogs']);
    
    // Location-specific API endpoints
    Route::get('/api/location-settings/{location_id}', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'getLocationSettings']);
    Route::post('/api/location-settings', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'saveLocationSettings']);
    Route::post('/api/location-settings/{location_id}/sync', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'triggerLocationSync']);
    
    // Multi-Location Configuration Routes
    Route::resource('location-settings', Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class)
         ->names('woocommerce.location-settings');
    Route::post('/location-settings/test-connection', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'testConnection'])
         ->name('woocommerce.location-settings.test-connection');
    Route::post('/location-settings/{id}/sync-now', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'syncNow'])
         ->name('woocommerce.location-settings.sync-now');
    Route::post('/location-settings/{id}/sync-stock', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'syncLocationStock'])
         ->name('woocommerce.location-settings.sync-stock');
    
    // Webhook-specific endpoints (independent of API configuration)
    Route::post('/api/webhook-secret', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'saveWebhookSecret']);
    Route::get('/api/webhook-secret', [Modules\Woocommerce\Http\Controllers\ConfigurationController::class, 'getWebhookSecret']);
    
    // Sync Health Monitoring Dashboard
    Route::get('/sync-health', [Modules\Woocommerce\Http\Controllers\SyncHealthController::class, 'index'])
         ->name('woocommerce.sync-health');
    Route::get('/sync-health/location/{id}', [Modules\Woocommerce\Http\Controllers\SyncHealthController::class, 'locationHealth'])
         ->name('woocommerce.sync-health.location');
    Route::get('/sync-health/api/metrics', [Modules\Woocommerce\Http\Controllers\SyncHealthController::class, 'healthMetricsApi'])
         ->name('woocommerce.sync-health.metrics-api');
    Route::get('/sync-health/api/activity', [Modules\Woocommerce\Http\Controllers\SyncHealthController::class, 'activityFeedApi'])
         ->name('woocommerce.sync-health.activity-api');
    
    // Configuration alias for backward compatibility
    Route::post('/configuration/trigger-location-sync', [Modules\Woocommerce\Http\Controllers\WoocommerceLocationSettingsController::class, 'triggerLocationSync'])
         ->name('woocommerce.configuration.trigger-location-sync');
    
    // Order Status Mapping Configuration
    Route::prefix('order-status-mapping')->group(function () {
        Route::get('/settings', [Modules\Woocommerce\Http\Controllers\OrderStatusMappingController::class, 'getStatusMapping'])
             ->name('woocommerce.order-status.get');
        Route::post('/settings', [Modules\Woocommerce\Http\Controllers\OrderStatusMappingController::class, 'updateStatusMapping'])
             ->name('woocommerce.order-status.update');
        Route::post('/apply-preset', [Modules\Woocommerce\Http\Controllers\OrderStatusMappingController::class, 'applyPreset'])
             ->name('woocommerce.order-status.apply-preset');
        Route::post('/test', [Modules\Woocommerce\Http\Controllers\OrderStatusMappingController::class, 'testStatusMapping'])
             ->name('woocommerce.order-status.test');
        Route::get('/webhook-history', [Modules\Woocommerce\Http\Controllers\OrderStatusMappingController::class, 'getWebhookHistory'])
             ->name('woocommerce.order-status.webhook-history');
    });
    
    // Real-time Progress Tracking
    Route::get('/progress/stream', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'progressStream'])
         ->name('woocommerce.progress.stream');
    Route::get('/progress/api', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'getProgress'])
         ->name('woocommerce.progress.api');
    Route::get('/progress/stats', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'getSyncStats'])
         ->name('woocommerce.progress.stats');
    Route::post('/progress/{id}/cancel', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'cancelSync'])
         ->name('woocommerce.progress.cancel');
    Route::post('/progress/{id}/pause', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'pauseSync'])
         ->name('woocommerce.progress.pause');
    Route::post('/progress/{id}/resume', [Modules\Woocommerce\Http\Controllers\SyncProgressController::class, 'resumeSync'])
         ->name('woocommerce.progress.resume');
    
    // Advanced Sync Scheduling
    Route::get('/schedules', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'index'])
         ->name('woocommerce.schedules.index');
    Route::post('/schedules', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'store'])
         ->name('woocommerce.schedules.store');
    Route::get('/schedules/{id}', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'show'])
         ->name('woocommerce.schedules.show');
    Route::put('/schedules/{id}', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'update'])
         ->name('woocommerce.schedules.update');
    Route::delete('/schedules/{id}', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'destroy'])
         ->name('woocommerce.schedules.destroy');
    Route::post('/schedules/{id}/toggle', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'toggle'])
         ->name('woocommerce.schedules.toggle');
    Route::post('/schedules/{id}/execute', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'executeNow'])
         ->name('woocommerce.schedules.execute');
    Route::get('/schedules/{id}/executions', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'getExecutions'])
         ->name('woocommerce.schedules.executions');
    Route::get('/schedule-templates', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'getTemplates'])
         ->name('woocommerce.schedules.templates');
    Route::get('/cron-expressions', [Modules\Woocommerce\Http\Controllers\SyncScheduleController::class, 'getCronExpressions'])
         ->name('woocommerce.schedules.cron-expressions');

    // Legacy routes (kept for backward compatibility - can be removed after migration)
    Route::prefix('legacy')->group(function () {
        Route::get('/', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'index'])->name('woocommerce.legacy');
        Route::get('/api-settings', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'apiSettings']);
        Route::post('/update-api-settings', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'updateSettings']);
        Route::get('/sync-categories', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'syncCategories']);
        Route::get('/sync-products', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'syncProducts']);
        Route::get('/sync-log', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'getSyncLog']);
        Route::get('/sync-orders', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'syncOrders']);
        Route::post('/map-taxrates', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'mapTaxRates']);
        Route::get('/view-sync-log', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'viewSyncLog']);
        Route::get('/get-log-details/{id}', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'getLogDetails']);
        Route::get('/reset-categories', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'resetCategories']);
        Route::get('/reset-products', [Modules\Woocommerce\Http\Controllers\WoocommerceController::class, 'resetProducts']);
    });
});
