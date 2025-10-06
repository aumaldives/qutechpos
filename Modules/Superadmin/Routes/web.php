<?php

// use App\Http\Controllers\BusinessController;
// use App\Http\Controllers\Modules;
// use Illuminate\Support\Facades\Route;

Route::get('/pricing', [Modules\Superadmin\Http\Controllers\PricingController::class, 'index'])->name('pricing');

Route::middleware('web', 'auth', 'language', 'AdminSidebarMenu', 'superadmin')->prefix('superadmin')->group(function () {
    Route::get('/install', [Modules\Superadmin\Http\Controllers\InstallController::class, 'index']);
    Route::get('/install/update', [Modules\Superadmin\Http\Controllers\InstallController::class, 'update']);
    Route::get('/install/uninstall', [Modules\Superadmin\Http\Controllers\InstallController::class, 'uninstall']);

    Route::get('/', [Modules\Superadmin\Http\Controllers\SuperadminController::class, 'index']);
    Route::get('/stats', [Modules\Superadmin\Http\Controllers\SuperadminController::class, 'stats']);

    Route::get('/{business_id}/toggle-active/{is_active}', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'toggleActive']);

    Route::get('/users/{business_id}', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'usersList']);
    Route::post('/update-password', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'updatePassword']);

    Route::resource('/business', Modules\Superadmin\Http\Controllers\BusinessController::class);
    Route::get('/business/{id}/destroy', [Modules\Superadmin\Http\Controllers\BusinessController::class, 'destroy']);

    Route::resource('/packages', 'Modules\Superadmin\Http\Controllers\PackagesController');
    Route::get('/packages/{id}/destroy', [Modules\Superadmin\Http\Controllers\PackagesController::class, 'destroy']);

    Route::get('/settings', [Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'edit']);
    Route::put('/settings', [Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'update']);
    Route::get('/edit-subscription/{id}', [Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController::class, 'editSubscription']);
    Route::post('/update-subscription', [Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController::class, 'updateSubscription']);
    Route::resource('/superadmin-subscription', 'Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController');

    Route::get('/communicator', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'index']);
    Route::post('/communicator/send', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'send']);
    Route::get('/communicator/get-history', [Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'getHistory']);

    Route::resource('/frontend-pages', 'Modules\Superadmin\Http\Controllers\PageController');
    
    // Bank Management routes
    Route::prefix('banks')->name('superadmin.banks.')->group(function () {
        Route::get('/', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'index'])->name('index');
        Route::post('/', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'store'])->name('store');
        Route::get('/{id}', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'show'])->name('show');
        Route::put('/{id}', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [Modules\Superadmin\Http\Controllers\BankManagementController::class, 'toggleStatus'])->name('toggle-status');
    });
    
    // Reports routes
    Route::prefix('reports')->name('superadmin.reports.')->group(function () {
        // Transaction Reports
        Route::get('/transaction', [Modules\Superadmin\Http\Controllers\TransactionReportsController::class, 'index'])->name('transaction');
        Route::get('/transaction/data', [Modules\Superadmin\Http\Controllers\TransactionReportsController::class, 'getData'])->name('transaction.data');
        Route::get('/transaction/export', [Modules\Superadmin\Http\Controllers\TransactionReportsController::class, 'export'])->name('transaction.export');
        
        // Tax Reports (MIRA)
        Route::get('/tax', [Modules\Superadmin\Http\Controllers\TaxReportsController::class, 'index'])->name('tax');
        Route::get('/tax/export', [Modules\Superadmin\Http\Controllers\TaxReportsController::class, 'export'])->name('tax.export');
    });
    
    // Legacy MIRA reports route (for backward compatibility)
    Route::get('/mira/export', [Modules\Superadmin\Http\Controllers\MiraReportsController::class, 'export'])->name('superadmin.mira.export');
});

// Stripe webhook route (no middleware - Stripe needs direct access)
Route::post('/webhook/stripe', [Modules\Superadmin\Http\Controllers\StripeWebhookController::class, 'handle'])->name('stripe.webhook');

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->group(function () {
    //Routes related to paypal checkout
    Route::get('/subscription/{package_id}/paypal-express-checkout', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'paypalExpressCheckout']);

    Route::get('/subscription/post-flutterwave-payment', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'postFlutterwavePaymentCallback']);

    Route::post('/subscription/pay-stack', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'getRedirectToPaystack']);
    Route::get('/subscription/post-payment-pay-stack-callback', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'postPaymentPaystackCallback']);

    //Routes related to pesapal checkout
    Route::get('/subscription/{package_id}/pesapal-callback', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pesapalCallback'])->name('pesapalCallback');

    Route::get('/subscription/{package_id}/pay', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay']);
    Route::any('/subscription/{package_id}/confirm', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'])->name('subscription-confirm');
    Route::get('/all-subscriptions', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'allSubscriptions']);

    Route::get('/subscription/{package_id}/register-pay', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'registerPay'])->name('register-pay');

    // Stripe subscription management routes
    Route::get('/subscription/stripe/manage', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeManage'])->name('subscription.stripe.manage');
    Route::post('/subscription/stripe/cancel', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeCancel'])->name('subscription.stripe.cancel');
    Route::post('/subscription/stripe/update-payment', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeUpdatePayment'])->name('subscription.stripe.update-payment');
    Route::get('/subscription/stripe/customer-portal', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeCustomerPortal'])->name('subscription.stripe.customer-portal');
    Route::get('/subscription/stripe/confirm', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeConfirmSubscription'])->name('subscription.stripe.confirm');
    
    // Location validation routes
    Route::post('/subscription/{package_id}/deactivate-locations-proceed', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'deactivateLocationsAndProceed'])->name('subscription.deactivate-locations-proceed');
    Route::post('/subscription/validate-location-reactivation', [Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'validateLocationReactivation'])->name('subscription.validate-location-reactivation');

    Route::resource('/subscription', 'Modules\Superadmin\Http\Controllers\SubscriptionController');
});

Route::get('/page/{slug}', [Modules\Superadmin\Http\Controllers\PageController::class, 'showPage'])->name('frontend-pages');
