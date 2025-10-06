<?php
Route::group(['middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone',
 'AdminSidebarMenu'], 'prefix' => 'ageingreport', 'namespace' => 'Modules\AgeingReport\Http\Controllers'], function () {

    Route::get('install', 'InstallController@index');
    Route::post('install', 'InstallController@install');
    Route::get('install/uninstall', 'InstallController@uninstall');
    Route::get('install/update', 'InstallController@update');
    
    Route::resource('ageingreport', 'AgeingReportController')->only('index','store','show');
    Route::post('/get-ageing-details', 'AgeingReportController@getAgeingDetails');
    Route::get('/supplier-ageing', 'AgeingReportController@getSuppliersAgeing')->name('supplier-ageing');

    Route::get('/contact/{id}', [\App\Http\Controllers\ContactController::class, 'show'])->name('contact.show');
    Route::get('/purchases/{transaction_id}', [\App\Http\Controllers\PurchaseController::class, 'show'])->name('purchase.show');
    Route::get('/sells/{transaction_id}', [\App\Http\Controllers\SellController::class, 'show'])->name('sell.show');
   

}); 
 