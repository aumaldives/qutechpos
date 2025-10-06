<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| WooCommerce API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// Business Locations endpoint for settings dropdown
Route::middleware(['auth'])->get('/woocommerce/business-locations', function (Request $request) {
    $businessId = $request->session()->get('user.business_id');
    $locations = \App\BusinessLocation::where('business_id', $businessId)->get();
    
    return response()->json(['locations' => $locations]);
});

// Legacy API routes (for backward compatibility)
Route::middleware(['auth'])->prefix('api/v1/woocommerce')->group(function () {
    // Add legacy API routes here if needed for backward compatibility
});