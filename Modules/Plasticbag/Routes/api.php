<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api', 'timezone')->prefix('plasticbag/api')->group(function () {
    Route::resource('business-location', Modules\Plasticbag\Http\Controllers\Api\BusinessLocationController::class)->only('index', 'show');

    Route::resource('contactapi', Modules\Plasticbag\Http\Controllers\Api\ContactController::class)->only('index', 'show', 'store', 'update');

    Route::post('contactapi-payment', [Modules\Plasticbag\Http\Controllers\Api\ContactController::class, 'contactPay']);

    Route::resource('unit', Modules\Plasticbag\Http\Controllers\Api\UnitController::class)->only('index', 'show');

    Route::resource('taxonomy', 'Modules\Plasticbag\Http\Controllers\Api\CategoryController')->only('index', 'show');

    Route::resource('brand', Modules\Plasticbag\Http\Controllers\Api\BrandController::class)->only('index', 'show');

    Route::resource('product', Modules\Plasticbag\Http\Controllers\Api\ProductController::class)->only('index', 'show');

    Route::get('selling-price-group', [Modules\Plasticbag\Http\Controllers\Api\ProductController::class, 'getSellingPriceGroup']);

    Route::get('variation/{id?}', [Modules\Plasticbag\Http\Controllers\Api\ProductController::class, 'listVariations']);

    Route::resource('tax', 'Modules\Plasticbag\Http\Controllers\Api\TaxController')->only('index', 'show');

    Route::resource('table', Modules\Plasticbag\Http\Controllers\Api\TableController::class)->only('index', 'show');

    Route::get('user/loggedin', [Modules\Plasticbag\Http\Controllers\Api\UserController::class, 'loggedin']);
    Route::post('user-registration', [Modules\Plasticbag\Http\Controllers\Api\UserController::class, 'registerUser']);
    Route::resource('user', Modules\Plasticbag\Http\Controllers\Api\UserController::class)->only('index', 'show');

    Route::resource('types-of-service', Modules\Plasticbag\Http\Controllers\Api\TypesOfServiceController::class)->only('index', 'show');

    Route::get('payment-accounts', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getPaymentAccounts']);

    Route::get('payment-methods', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getPaymentMethods']);

    Route::resource('sell', Modules\Plasticbag\Http\Controllers\Api\SellController::class)->only('index', 'store', 'show', 'update', 'destroy');

    Route::post('sell-return', [Modules\Plasticbag\Http\Controllers\Api\SellController::class, 'addSellReturn']);

    Route::get('list-sell-return', [Modules\Plasticbag\Http\Controllers\Api\SellController::class, 'listSellReturn']);

    Route::post('update-shipping-status', [Modules\Plasticbag\Http\Controllers\Api\SellController::class, 'updateSellShippingStatus']);

    Route::resource('expense', Modules\Plasticbag\Http\Controllers\Api\ExpenseController::class)->only('index', 'store', 'show', 'update');
    Route::get('expense-refund', [Modules\Plasticbag\Http\Controllers\Api\ExpenseController::class, 'listExpenseRefund']);

    Route::get('expense-categories', [Modules\Plasticbag\Http\Controllers\Api\ExpenseController::class, 'listExpenseCategories']);

    Route::resource('cash-register', Modules\Plasticbag\Http\Controllers\Api\CashRegisterController::class)->only('index', 'store', 'show', 'update');

    Route::get('business-details', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getBusinessDetails']);

    Route::get('profit-loss-report', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getProfitLoss']);

    Route::get('product-stock-report', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getProductStock']);
    Route::get('notifications', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getNotifications']);

    Route::get('active-subscription', [Modules\Plasticbag\Http\Controllers\Api\SuperadminController::class, 'getActiveSubscription']);
    Route::get('packages', [Modules\Plasticbag\Http\Controllers\Api\SuperadminController::class, 'getPackages']);

    Route::get('get-attendance/{user_id}', [Modules\Plasticbag\Http\Controllers\Api\AttendanceController::class, 'getAttendance']);
    Route::post('clock-in', [Modules\Plasticbag\Http\Controllers\Api\AttendanceController::class, 'clockin']);
    Route::post('clock-out', [Modules\Plasticbag\Http\Controllers\Api\AttendanceController::class, 'clockout']);
    Route::get('holidays', [Modules\Plasticbag\Http\Controllers\Api\AttendanceController::class, 'getHolidays']);
    Route::post('update-password', [Modules\Plasticbag\Http\Controllers\Api\UserController::class, 'updatePassword']);
    Route::post('forget-password', [Modules\Plasticbag\Http\Controllers\Api\UserController::class, 'forgetPassword']);
    Route::get('get-location', [Modules\Plasticbag\Http\Controllers\Api\CommonResourceController::class, 'getLocation']);

    Route::get('new_product', [Modules\Plasticbag\Http\Controllers\Api\ProductSellController::class, 'newProduct'])->name('new_product');
    Route::get('new_sell', [Modules\Plasticbag\Http\Controllers\Api\ProductSellController::class, 'newSell'])->name('new_sell');
    Route::get('new_contactapi', [Modules\Plasticbag\Http\Controllers\Api\ProductSellController::class, 'newContactApi'])->name('new_contactapi');
});

Route::middleware('auth:api', 'timezone')->prefix('plasticbag/api/crm')->group(function () {
    Route::resource('follow-ups', 'Modules\Plasticbag\Http\Controllers\Api\Crm\FollowUpController')->only('index', 'store', 'show', 'update');

    Route::get('follow-up-resources', [Modules\Plasticbag\Http\Controllers\Api\Crm\FollowUpController::class, 'getFollowUpResources']);

    Route::get('leads', [Modules\Plasticbag\Http\Controllers\Api\Crm\FollowUpController::class, 'getLeads']);

    Route::post('call-logs', [Modules\Plasticbag\Http\Controllers\Api\Crm\CallLogsController::class, 'saveCallLogs']);
});

Route::middleware('auth:api', 'timezone')->prefix('plasticbag/api')->group(function () {
    Route::get('field-force', [Modules\Plasticbag\Http\Controllers\Api\FieldForce\FieldForceController::class, 'index']);
    Route::post('field-force/create', [Modules\Plasticbag\Http\Controllers\Api\FieldForce\FieldForceController::class, 'store']);
    Route::post('field-force/update-visit-status/{id}', [Modules\Plasticbag\Http\Controllers\Api\FieldForce\FieldForceController::class, 'updateStatus']);
});
