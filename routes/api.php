<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\ProductController as V1ProductController;
use App\Http\Controllers\Api\V1\ContactController as V1ContactController;
use App\Http\Controllers\Api\V1\TransactionController as V1TransactionController;
use App\Http\Controllers\Api\V1\BusinessController as V1BusinessController;
use App\Http\Controllers\Api\V1\ReportController as V1ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Modern REST API with versioning and comprehensive business management.
| All routes require API key authentication via ApiKeyAuth middleware.
|
*/

// API Status Check (no authentication required)
Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'IsleBooks API is operational',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'endpoints' => [
            'v1' => '/api/v1/*'
        ]
    ]);
});

// API v1 Routes - Modern REST API with comprehensive business management
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // Public endpoints (no authentication)
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API v1 is healthy',
            'timestamp' => now()->toISOString()
        ]);
    });
    
    // Public information endpoints
    Route::get('/info', [App\Http\Controllers\Api\V1\PublicInfoController::class, 'info'])->name('info');
    Route::get('/status', [App\Http\Controllers\Api\V1\PublicInfoController::class, 'status'])->name('status');
    Route::get('/versions', [App\Http\Controllers\Api\V1\PublicInfoController::class, 'versions'])->name('versions');
    Route::get('/rate-limits', [App\Http\Controllers\Api\V1\PublicInfoController::class, 'rateLimits'])->name('rate-limits');
    
    // Protected endpoints (require API key authentication)
    Route::middleware(['api.auth'])->group(function () {
        
        // Business Information Endpoints (READ-ONLY for security)
        // Note: Business settings modification is intentionally not available via API
        // Use the web interface for business configuration changes
        Route::prefix('business')->name('business.')->group(function () {
            Route::get('/', [V1BusinessController::class, 'show'])->name('show');
            Route::get('/locations', [V1BusinessController::class, 'locations'])->name('locations');
            Route::get('/settings', [V1BusinessController::class, 'settings'])->name('settings');
        });
        
        // Products & Inventory Management
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [V1ProductController::class, 'index'])->name('index');
            Route::post('/', [V1ProductController::class, 'store'])->middleware('api.auth:write,products')->name('store');
            Route::get('/{id}', [V1ProductController::class, 'show'])->name('show');
            Route::put('/{id}', [V1ProductController::class, 'update'])->middleware('api.auth:write,products')->name('update');
            Route::delete('/{id}', [V1ProductController::class, 'destroy'])->middleware('api.auth:delete,products')->name('destroy');
            
            // Product variations
            Route::get('/{id}/variations', [V1ProductController::class, 'variations'])->name('variations');
            Route::get('/{id}/stock', [V1ProductController::class, 'stock'])->name('stock');
            
            // Bulk operations
            Route::post('/bulk', [V1ProductController::class, 'bulkStore'])->middleware('api.auth:write,products')->name('bulk.store');
            Route::put('/bulk', [V1ProductController::class, 'bulkUpdate'])->middleware('api.auth:write,products')->name('bulk.update');
        });
        
        // Categories Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\CategoryController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\CategoryController::class, 'store'])->middleware('api.auth:write,products')->name('store');
            Route::get('/dropdown', [App\Http\Controllers\Api\V1\CategoryController::class, 'dropdown'])->name('dropdown');
            Route::get('/hierarchical', [App\Http\Controllers\Api\V1\CategoryController::class, 'hierarchical'])->name('hierarchical');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\CategoryController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\CategoryController::class, 'update'])->middleware('api.auth:write,products')->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\CategoryController::class, 'destroy'])->middleware('api.auth:delete,products')->name('destroy');
        });
        
        // Brands Management
        Route::prefix('brands')->name('brands.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\BrandController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\BrandController::class, 'store'])->middleware('api.auth:write,products')->name('store');
            Route::get('/dropdown', [App\Http\Controllers\Api\V1\BrandController::class, 'dropdown'])->name('dropdown');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\BrandController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\BrandController::class, 'update'])->middleware('api.auth:write,products')->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\BrandController::class, 'destroy'])->middleware('api.auth:delete,products')->name('destroy');
        });
        
        // Contacts Management (Customers, Suppliers)
        Route::prefix('contacts')->name('contacts.')->group(function () {
            Route::get('/', [V1ContactController::class, 'index'])->name('index');
            Route::post('/', [V1ContactController::class, 'store'])->middleware('api.auth:write,contacts')->name('store');
            Route::get('/{id}', [V1ContactController::class, 'show'])->name('show');
            Route::put('/{id}', [V1ContactController::class, 'update'])->middleware('api.auth:write,contacts')->name('update');
            Route::delete('/{id}', [V1ContactController::class, 'destroy'])->middleware('api.auth:delete,contacts')->name('destroy');
            
            // Contact specific endpoints
            Route::get('/{id}/transactions', [V1ContactController::class, 'transactions'])->name('transactions');
            Route::get('/{id}/balance', [V1ContactController::class, 'balance'])->name('balance');
        });
        
        // Transactions Management (Sales, Purchases, Expenses)
        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [V1TransactionController::class, 'index'])->name('index');
            Route::post('/', [V1TransactionController::class, 'store'])->middleware('api.auth:write,transactions')->name('store');
            Route::get('/{id}', [V1TransactionController::class, 'show'])->name('show');
            Route::put('/{id}', [V1TransactionController::class, 'update'])->middleware('api.auth:write,transactions')->name('update');
            Route::delete('/{id}', [V1TransactionController::class, 'destroy'])->middleware('api.auth:delete,transactions')->name('destroy');
            
            // Transaction specific endpoints
            Route::post('/{id}/payments', [V1TransactionController::class, 'addPayment'])->middleware('api.auth:write,transactions')->name('payments.store');
            Route::get('/{id}/payments', [V1TransactionController::class, 'payments'])->name('payments.index');
        });

        // Payment Management (Invoice-based payments)
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/invoices', [App\Http\Controllers\Api\V1\PaymentController::class, 'getInvoices'])->name('invoices');
            Route::get('/invoice', [App\Http\Controllers\Api\V1\PaymentController::class, 'getInvoice'])->name('invoice');
            Route::post('/invoice', [App\Http\Controllers\Api\V1\PaymentController::class, 'addPayment'])->middleware('api.auth:write,payments')->name('add');
            Route::get('/history', [App\Http\Controllers\Api\V1\PaymentController::class, 'getPaymentHistory'])->name('history');
        });
        
        // Sales specific endpoints
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', [V1TransactionController::class, 'sales'])->name('index');
            Route::post('/', [V1TransactionController::class, 'createSale'])->middleware('api.auth:write,transactions')->name('store');
            Route::get('/recent', [V1TransactionController::class, 'recentSales'])->name('recent');
        });
        
        // POS Management (Point of Sale)
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/business-info', [App\Http\Controllers\Api\V1\PosController::class, 'getBusinessInfo'])->name('business-info');
            Route::get('/product-suggestions', [App\Http\Controllers\Api\V1\PosController::class, 'getProductSuggestions'])->name('product-suggestions');
            Route::get('/product-row', [App\Http\Controllers\Api\V1\PosController::class, 'getProductRow'])->name('product-row');
            Route::get('/plastic-bags', [App\Http\Controllers\Api\V1\PosController::class, 'getPlasticBagTypes'])->name('plastic-bags');
            Route::post('/sale', [App\Http\Controllers\Api\V1\PosController::class, 'createSale'])->middleware('api.auth:write,transactions')->name('create-sale');
            Route::get('/recent-transactions', [App\Http\Controllers\Api\V1\PosController::class, 'getRecentTransactions'])->name('recent-transactions');
            Route::get('/settings', [App\Http\Controllers\Api\V1\PosController::class, 'getSettings'])->name('settings');
            
            // Draft management
            Route::post('/drafts', [App\Http\Controllers\Api\V1\PosController::class, 'saveDraft'])->middleware('api.auth:write,transactions')->name('save-draft');
            Route::get('/drafts', [App\Http\Controllers\Api\V1\PosController::class, 'getDrafts'])->name('get-drafts');
            Route::get('/drafts/{draft_id}', [App\Http\Controllers\Api\V1\PosController::class, 'loadDraft'])->name('load-draft');
            Route::delete('/drafts/{draft_id}', [App\Http\Controllers\Api\V1\PosController::class, 'deleteDraft'])->middleware('api.auth:delete,transactions')->name('delete-draft');
        });
        
        // Purchase Management
        Route::prefix('purchases')->name('purchases.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\PurchaseController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\PurchaseController::class, 'store'])->middleware('api.auth:write,purchases')->name('store');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\PurchaseController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\PurchaseController::class, 'update'])->middleware('api.auth:write,purchases')->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\PurchaseController::class, 'destroy'])->middleware('api.auth:delete,purchases')->name('destroy');
            
            // Purchase specific endpoints
            Route::get('/recent', [App\Http\Controllers\Api\V1\PurchaseController::class, 'recent'])->name('recent');
            Route::get('/suppliers', [App\Http\Controllers\Api\V1\PurchaseController::class, 'suppliers'])->name('suppliers');
            Route::get('/product-row', [App\Http\Controllers\Api\V1\PurchaseController::class, 'getProductRow'])->name('product-row');
        });
        
        // Expense Management  
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\ExpenseController::class, 'index'])->name('index');
            Route::post('/', [App\Http\Controllers\Api\V1\ExpenseController::class, 'store'])->middleware('api.auth:write,expenses')->name('store');
            Route::get('/{id}', [App\Http\Controllers\Api\V1\ExpenseController::class, 'show'])->name('show');
            Route::put('/{id}', [App\Http\Controllers\Api\V1\ExpenseController::class, 'update'])->middleware('api.auth:write,expenses')->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Api\V1\ExpenseController::class, 'destroy'])->middleware('api.auth:delete,expenses')->name('destroy');
            
            // Expense specific endpoints
            Route::get('/categories', [App\Http\Controllers\Api\V1\ExpenseController::class, 'categories'])->name('categories');
            Route::get('/summary', [App\Http\Controllers\Api\V1\ExpenseController::class, 'summary'])->name('summary');
        });
        
        // Stock Management
        Route::prefix('stock')->name('stock.')->group(function () {
            // Stock adjustments
            Route::get('/adjustments', [App\Http\Controllers\Api\V1\StockController::class, 'adjustments'])->name('adjustments.index');
            Route::post('/adjustments', [App\Http\Controllers\Api\V1\StockController::class, 'createAdjustment'])->middleware('api.auth:write,stock')->name('adjustments.create');
            
            // Stock transfers
            Route::get('/transfers', [App\Http\Controllers\Api\V1\StockController::class, 'transfers'])->name('transfers.index');
            Route::post('/transfers', [App\Http\Controllers\Api\V1\StockController::class, 'createTransfer'])->middleware('api.auth:write,stock')->name('transfers.create');
            Route::put('/transfers/{id}/status', [App\Http\Controllers\Api\V1\StockController::class, 'updateTransferStatus'])->middleware('api.auth:write,stock')->name('transfers.update-status');
            
            // Stock levels and movements
            Route::get('/levels', [App\Http\Controllers\Api\V1\StockController::class, 'levels'])->name('levels');
            Route::get('/opening-stock', [App\Http\Controllers\Api\V1\StockController::class, 'openingStock'])->name('opening-stock');
            Route::get('/movements', [App\Http\Controllers\Api\V1\StockController::class, 'movements'])->name('movements');
        });
        
        // Business Configuration
        Route::prefix('config')->name('config.')->group(function () {
            // Public configuration endpoints (business level access)
            Route::get('/locations', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'locations'])->name('locations');
            Route::get('/units', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'units'])->name('units');
            Route::get('/brands', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'brands'])->name('brands');
            Route::get('/categories', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'categories'])->name('categories');
            Route::get('/tax-rates', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'taxRates'])->name('tax-rates');
            Route::get('/payment-methods', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'paymentMethods'])->name('payment-methods');
            
            // System level endpoints (restricted)
            Route::middleware('api.auth.level:system')->group(function () {
                Route::get('/users', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'users'])->name('users');
                Route::get('/roles', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'roles'])->name('roles');
                Route::get('/business-settings', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'businessSettings'])->name('business-settings');
                Route::get('/app-config', [App\Http\Controllers\Api\V1\ConfigurationController::class, 'appConfig'])->name('app-config');
            });
        });
        
        // Reports and Analytics
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/dashboard', [V1ReportController::class, 'dashboard'])->name('dashboard');
            Route::get('/sales-analytics', [V1ReportController::class, 'salesAnalytics'])->name('sales-analytics');
            Route::get('/profit-loss', [V1ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/stock-report', [V1ReportController::class, 'stockReport'])->name('stock-report');
            Route::get('/trending-products', [V1ReportController::class, 'trendingProducts'])->name('trending-products');
        });
        
        // HRM (Human Resource Management)
        Route::prefix('hrm')->name('hrm.')->group(function () {
            // User Management
            Route::get('/users', [App\Http\Controllers\Api\V1\HrmController::class, 'users'])->name('users');
            Route::get('/users/{id}', [App\Http\Controllers\Api\V1\HrmController::class, 'getUserDetails'])->name('user-details');

            // Attendance Management
            Route::post('/check-in', [App\Http\Controllers\Api\V1\HrmController::class, 'checkIn'])->middleware('api.auth:write,hrm')->name('check-in');
            Route::post('/check-out', [App\Http\Controllers\Api\V1\HrmController::class, 'checkOut'])->middleware('api.auth:write,hrm')->name('check-out');
            Route::get('/attendance', [App\Http\Controllers\Api\V1\HrmController::class, 'attendance'])->name('attendance');

            // Overtime Management
            Route::post('/overtime-in', [App\Http\Controllers\Api\V1\HrmController::class, 'overtimeIn'])->middleware('api.auth:write,hrm')->name('overtime-in');
            Route::post('/overtime-out', [App\Http\Controllers\Api\V1\HrmController::class, 'overtimeOut'])->middleware('api.auth:write,hrm')->name('overtime-out');
            Route::get('/overtime', [App\Http\Controllers\Api\V1\HrmController::class, 'overtime'])->name('overtime');
            Route::post('/overtime-request', [App\Http\Controllers\Api\V1\HrmController::class, 'createOvertimeRequest'])->middleware('api.auth:write,hrm')->name('overtime-request');
        });

        // Connector API - Custom integrations and simplified endpoints
        Route::prefix('connector')->name('connector.')->group(function () {
            // Purchase creation with form-data compatibility
            Route::post('/purchase', [App\Http\Controllers\Api\V1\ConnectorController::class, 'createPurchase'])->middleware('api.auth:write,purchases')->name('purchase.create');

            // Simplified USDT stock addition
            Route::post('/usdt-stock', [App\Http\Controllers\Api\V1\ConnectorController::class, 'addUSDTStock'])->middleware('api.auth:write,purchases')->name('usdt-stock.add');
        });
        
    });
});

// Legacy compatibility endpoint (for existing integrations during migration)
Route::middleware(['api.auth'])->group(function () {
    Route::get('/legacy/products', [V1ProductController::class, 'index']);
    Route::get('/legacy/contacts', [V1ContactController::class, 'index']);
    Route::get('/legacy/transactions', [V1TransactionController::class, 'index']);
});
