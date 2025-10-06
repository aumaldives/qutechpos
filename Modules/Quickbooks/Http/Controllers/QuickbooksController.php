<?php

namespace Modules\Quickbooks\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;
use Modules\Quickbooks\Entities\QuickbooksAppConfig;
use Modules\Quickbooks\Services\QuickBooksSyncService;
use Modules\Quickbooks\Services\QuickBooksApiClient;

class QuickbooksController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone']);
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        // Check if QuickBooks module is enabled
        if (!\Module::find('Quickbooks') || !\Module::find('Quickbooks')->isEnabled()) {
            return redirect()->route('integrations')
                           ->with('error', 'QuickBooks integration requires an upgraded package. Please contact your administrator.');
        }
        
        $business_id = request()->session()->get('user.business_id');
        
        if (!$business_id) {
            return redirect()->route('home')->with('error', 'Business context required');
        }

        // Check subscription permissions
        if (!auth()->user()->can('superadmin') && 
            !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'quickbooks_module', 'superadmin_package')) {
            return redirect()->route('integrations')
                           ->with('error', 'QuickBooks integration requires an upgraded package. Please upgrade your subscription.');
        }

        $locations = BusinessLocation::where('business_id', $business_id)->get();
        $settings = QuickbooksLocationSettings::where('business_id', $business_id)
                                            ->with('location')
                                            ->get()
                                            ->keyBy('location_id');

        // Check if global app config is available
        $appConfigExists = QuickbooksAppConfig::where('is_active', true)->exists();

        return view('quickbooks::simplified-index', compact('locations', 'settings', 'appConfigExists'));
    }

    public function showLocationSettings($locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $location = BusinessLocation::where('business_id', $business_id)
                                  ->where('id', $locationId)
                                  ->firstOrFail();

        $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId)
                 ?? new QuickbooksLocationSettings(['business_id' => $business_id, 'location_id' => $locationId]);

        return view('quickbooks::location-settings', compact('location', 'settings'));
    }

    public function saveLocationSettings(Request $request, $locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Check permissions
        if (!auth()->user()->can('superadmin') && 
            !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'quickbooks_module', 'superadmin_package')) {
            return redirect()->back()->with('error', 'Insufficient permissions');
        }
        
        $request->validate([
            'sandbox_mode' => 'required|in:sandbox,production',
            'sync_customers' => 'boolean',
            'sync_suppliers' => 'boolean',
            'sync_products' => 'boolean',
            'sync_invoices' => 'boolean',
            'sync_payments' => 'boolean',
            'sync_purchases' => 'boolean',
            'sync_inventory' => 'boolean',
            'enable_auto_sync' => 'boolean',
            'sync_interval_minutes' => 'integer|min:15|max:1440',
        ]);

        $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId)
                 ?? QuickbooksLocationSettings::createForLocation($business_id, $locationId);

        $settings->update($request->only([
            'sandbox_mode',
            'sync_customers', 'sync_suppliers', 'sync_products',
            'sync_invoices', 'sync_payments', 'sync_purchases', 'sync_inventory',
            'enable_auto_sync', 'sync_interval_minutes'
        ]));

        return redirect()->back()->with('success', 'Settings saved successfully');
    }

    public function connectQuickBooks($locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Check permissions
        if (!auth()->user()->can('superadmin') && 
            !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'quickbooks_module', 'superadmin_package')) {
            return redirect()->back()->with('error', 'Insufficient permissions');
        }
        
        $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId);
        
        if (!$settings) {
            $settings = QuickbooksLocationSettings::createForLocation($business_id, $locationId);
        }

        try {
            // Use the simplified OAuth flow
            $authUrl = $settings->getAuthorizationUrl();
            
            // Store OAuth initiation for security
            session(['quickbooks_oauth_initiated' => [
                'business_id' => $business_id,
                'location_id' => $locationId,
                'timestamp' => time()
            ]]);

            return redirect($authUrl);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to initiate QuickBooks connection: ' . $e->getMessage());
        }
    }

    // OAuth callback now handled by QuickbooksOAuthController

    public function testConnection($locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        try {
            $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId);
            
            if (!$settings || !$settings->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QuickBooks not configured for this location'
                ]);
            }

            $apiClient = new QuickBooksApiClient($settings);
            $result = $apiClient->testConnection();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    public function syncData(Request $request, $locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        $syncType = $request->get('sync_type', 'all');

        try {
            $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId);
            
            if (!$settings || !$settings->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QuickBooks not configured for this location'
                ]);
            }

            $syncService = new QuickBooksSyncService($settings);
            
            $result = match($syncType) {
                'customers' => ['customers' => $syncService->syncCustomers()],
                'suppliers' => ['suppliers' => $syncService->syncSuppliers()],
                'products' => ['products' => $syncService->syncProducts()],
                'invoices' => ['invoices' => $syncService->syncInvoices()],
                'payments' => ['payments' => $syncService->syncPayments()],
                'purchases' => ['purchases' => $syncService->syncPurchases()],
                default => $syncService->syncAll()
            };

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    public function disconnectQuickBooks($locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        try {
            $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId);
            
            if (!$settings) {
                return redirect()->back()->with('error', 'QuickBooks settings not found');
            }

            $oauthService = new QuickBooksOAuthService($settings->sandbox_mode === 'sandbox');
            $oauthService->revokeConnection($settings);

            return redirect()->back()->with('success', 'QuickBooks disconnected successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Disconnect failed: ' . $e->getMessage());
        }
    }

    public function getSyncStatus($locationId)
    {
        $business_id = request()->session()->get('user.business_id');
        
        try {
            $settings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $locationId);
            
            if (!$settings) {
                return response()->json([
                    'status' => 'not_configured',
                    'message' => 'QuickBooks not configured'
                ]);
            }

            return response()->json([
                'status' => $settings->sync_status,
                'last_sync' => $settings->last_successful_sync_at?->format('Y-m-d H:i:s'),
                'next_sync' => $settings->next_sync_time?->format('Y-m-d H:i:s'),
                'sync_counts' => [
                    'customers' => $settings->total_customers_synced,
                    'suppliers' => $settings->total_suppliers_synced,
                    'products' => $settings->total_products_synced,
                    'invoices' => $settings->total_invoices_synced,
                    'payments' => $settings->total_payments_synced,
                    'purchases' => $settings->total_purchases_synced,
                ],
                'failed_syncs' => $settings->failed_syncs_count,
                'last_error' => $settings->last_sync_error,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
