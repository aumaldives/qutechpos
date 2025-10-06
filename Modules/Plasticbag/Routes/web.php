<?php

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin')->prefix('plasticbag')->group(function () {
    Route::get('install', [Modules\Plasticbag\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [Modules\Plasticbag\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [Modules\Plasticbag\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [Modules\Plasticbag\Http\Controllers\InstallController::class, 'update']);
});

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('plasticbag')->group(function () {
    Route::get('/api', [Modules\Plasticbag\Http\Controllers\PlasticbagController::class, 'index']);
    // Route::resource('/client', 'Modules\Plasticbag\Http\Controllers\ClientController'); // Controller does not exist
    // Route::get('/regenerate', [Modules\Plasticbag\Http\Controllers\ClientController::class, 'regenerate']); // Controller does not exist
    Route::resource('/settings', 'Modules\Plasticbag\Http\Controllers\SettingsController');
    Route::post('save-plasticbag-settings', [Modules\Plasticbag\Http\Controllers\SettingsController::class, 'store'])->name('plasticbag.store');
});