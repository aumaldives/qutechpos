<?php

use Illuminate\Support\Facades\Route;
use Modules\Quickbooks\Http\Controllers\QuickbooksController;
use Modules\Quickbooks\Http\Controllers\QuickbooksOAuthController;

// OAuth callback - must be accessible without authentication
Route::middleware('web', 'SetSessionData')
     ->prefix('quickbooks')
     ->name('quickbooks.')
     ->group(function() {
    Route::get('/oauth/callback', [QuickbooksOAuthController::class, 'handleCallback'])
         ->name('oauth.callback');
});

// Authenticated QuickBooks routes
Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')
     ->prefix('quickbooks')
     ->name('quickbooks.')
     ->group(function() {
    
    Route::get('/', [QuickbooksController::class, 'index'])->name('index');
    
    Route::get('/location/{locationId}/settings', [QuickbooksController::class, 'showLocationSettings'])
         ->name('location.settings');
    
    Route::post('/location/{locationId}/settings', [QuickbooksController::class, 'saveLocationSettings'])
         ->name('location.settings.save');
    
    // Simplified OAuth Routes (authenticated)
    Route::get('/location/{locationId}/connect', [QuickbooksController::class, 'connectQuickBooks'])
         ->name('location.connect');
    
    Route::post('/oauth/initiate', [QuickbooksOAuthController::class, 'initiateOAuth'])
         ->name('oauth.initiate');
    
    Route::post('/oauth/disconnect', [QuickbooksOAuthController::class, 'disconnect'])
         ->name('oauth.disconnect');
    
    Route::get('/location/{locationId}/test-connection', [QuickbooksController::class, 'testConnection'])
         ->name('location.test');
    
    Route::post('/location/{locationId}/sync', [QuickbooksController::class, 'syncData'])
         ->name('location.sync');
    
    Route::get('/location/{locationId}/sync-status', [QuickbooksController::class, 'getSyncStatus'])
         ->name('location.sync.status');
});
