<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Entities\WoocommerceSyncProgress;
use App\BusinessLocation;
use App\Utils\Util;
use Yajra\DataTables\Facades\DataTables;
use DB;

class WoocommerceLocationSettingsController extends Controller
{
    protected $util;

    public function __construct()
    {
        $this->util = new Util();
    }

    /**
     * Display location settings page
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $location_settings = WoocommerceLocationSetting::leftJoin('business_locations', 'woocommerce_location_settings.location_id', '=', 'business_locations.id')
                ->where('woocommerce_location_settings.business_id', $business_id)
                ->select([
                    'woocommerce_location_settings.id',
                    'woocommerce_location_settings.location_id',
                    'woocommerce_location_settings.woocommerce_app_url',
                    'woocommerce_location_settings.is_active',
                    'woocommerce_location_settings.last_successful_sync_at',
                    'woocommerce_location_settings.total_products_synced',
                    'woocommerce_location_settings.total_orders_synced',
                    'woocommerce_location_settings.total_customers_synced',
                    'woocommerce_location_settings.failed_syncs_count',
                    'woocommerce_location_settings.last_sync_error',
                    'business_locations.name as location_name'
                ]);

            if (!empty($request->location_id)) {
                $location_settings->where('woocommerce_location_settings.location_id', $request->location_id);
            }

            return DataTables::of($location_settings)
                ->addColumn('sync_status', function ($row) {
                    $setting = WoocommerceLocationSetting::find($row->id);
                    $status = $setting->getSyncHealthStatus();
                    
                    $badges = [
                        'healthy' => '<span class="label label-success">Healthy</span>',
                        'stale' => '<span class="label label-warning">Stale</span>',
                        'failing' => '<span class="label label-danger">Failing</span>',
                        'disabled' => '<span class="label label-default">Disabled</span>',
                        'no_config' => '<span class="label label-info">Not Configured</span>',
                        'never_synced' => '<span class="label label-warning">Never Synced</span>',
                    ];
                    
                    return $badges[$status] ?? '<span class="label label-secondary">Unknown</span>';
                })
                ->addColumn('last_sync', function ($row) {
                    if ($row->last_successful_sync_at) {
                        return $this->util->format_date($row->last_successful_sync_at, true);
                    }
                    return '<span class="text-muted">Never</span>';
                })
                ->addColumn('sync_stats', function ($row) {
                    $stats = [];
                    if ($row->total_products_synced > 0) {
                        $stats[] = "Products: {$row->total_products_synced}";
                    }
                    if ($row->total_orders_synced > 0) {
                        $stats[] = "Orders: {$row->total_orders_synced}";
                    }
                    if ($row->total_customers_synced > 0) {
                        $stats[] = "Customers: {$row->total_customers_synced}";
                    }
                    if ($row->failed_syncs_count > 0) {
                        $stats[] = "<span class='text-danger'>Failed: {$row->failed_syncs_count}</span>";
                    }
                    
                    return !empty($stats) ? implode('<br>', $stats) : '<span class="text-muted">No data</span>';
                })
                ->addColumn('woocommerce_app_url', function ($row) {
                    if ($row->woocommerce_app_url) {
                        $domain = parse_url($row->woocommerce_app_url, PHP_URL_HOST);
                        return "<a href='{$row->woocommerce_app_url}' target='_blank' title='{$row->woocommerce_app_url}'>{$domain}</a>";
                    }
                    return '<span class="text-muted">Not configured</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info btn-xs dropdown-toggle btn-flat" data-toggle="dropdown" aria-expanded="false">' . __("messages.actions") . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>';
                    $html .= '<ul class="dropdown-menu" role="menu">';
                    
                    $html .= '<li><a href="#" class="edit-config" data-id="' . $row->id . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    
                    if ($row->is_active) {
                        $html .= '<li><a href="#" class="sync-now" data-id="' . $row->id . '"><i class="fa fa-refresh"></i> ' . __("woocommerce::lang.sync_now") . '</a></li>';
                    }
                    
                    $html .= '<li><a href="#" class="view-sync-logs" data-id="' . $row->id . '"><i class="fa fa-list"></i> ' . __("woocommerce::lang.view_sync_logs") . '</a></li>';
                    $html .= '<li class="divider"></li>';
                    $html .= '<li><a href="#" class="delete-config text-red" data-id="' . $row->id . '"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</a></li>';
                    
                    $html .= '</ul></div>';
                    
                    return $html;
                })
                ->rawColumns(['action', 'sync_status', 'last_sync', 'sync_stats', 'woocommerce_app_url'])
                ->make(true);
        }

        // Get business locations for dropdown
        $locations = BusinessLocation::forDropdown($business_id, false, true);

        return view('woocommerce::woocommerce.location_settings', compact('locations'));
    }

    /**
     * Store new location configuration
     */
    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'location_id' => 'required|integer|exists:business_locations,id',
                'woocommerce_app_url' => 'required|url',
                'woocommerce_consumer_key' => 'required|string',
                'woocommerce_consumer_secret' => 'required|string',
                'sync_interval_minutes' => 'integer|min:5|max:1440',
                'is_active' => 'boolean',
                'enable_auto_sync' => 'boolean',
                'sync_products' => 'boolean',
                'sync_orders' => 'boolean',
                'sync_inventory' => 'boolean',
                'sync_customers' => 'boolean',
            ]);

            // Check if location already has configuration
            $existing = WoocommerceLocationSetting::where('business_id', $business_id)
                                                  ->where('location_id', $request->location_id)
                                                  ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => __('woocommerce::lang.location_already_configured')
                ], 422);
            }

            // Generate webhook URL
            $webhook_url = url('modules/woocommerce/webhook/' . $request->location_id);

            $data = $request->only([
                'location_id',
                'woocommerce_app_url',
                'woocommerce_consumer_key',
                'woocommerce_consumer_secret',
                'sync_interval_minutes',
                'is_active',
                'enable_auto_sync',
                'sync_products',
                'sync_orders',
                'sync_inventory',
                'sync_customers'
            ]);

            $data['business_id'] = $business_id;
            $data['webhook_url'] = $webhook_url;
            $data['webhook_secret'] = $this->generateWebhookSecret();

            // Set defaults for checkboxes
            $data['is_active'] = $request->has('is_active') ? 1 : 0;
            $data['enable_auto_sync'] = $request->has('enable_auto_sync') ? 1 : 0;
            $data['sync_products'] = $request->has('sync_products') ? 1 : 0;
            $data['sync_orders'] = $request->has('sync_orders') ? 1 : 0;
            $data['sync_inventory'] = $request->has('sync_inventory') ? 1 : 0;
            $data['sync_customers'] = $request->has('sync_customers') ? 1 : 0;

            WoocommerceLocationSetting::create($data);

            return response()->json([
                'success' => true,
                'message' => __('woocommerce::lang.location_config_created_successfully')
            ]);

        } catch (\Exception $e) {
            \Log::error('Location WooCommerce config creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Show edit form data
     */
    public function edit($id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $config = WoocommerceLocationSetting::where('business_id', $business_id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }

    /**
     * Update location configuration
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'location_id' => 'required|integer|exists:business_locations,id',
                'woocommerce_app_url' => 'required|url',
                'woocommerce_consumer_key' => 'required|string',
                'woocommerce_consumer_secret' => 'sometimes|string',
                'sync_interval_minutes' => 'integer|min:5|max:1440',
                'is_active' => 'boolean',
                'enable_auto_sync' => 'boolean',
                'sync_products' => 'boolean',
                'sync_orders' => 'boolean',
                'sync_inventory' => 'boolean',
                'sync_customers' => 'boolean',
            ]);

            $config = WoocommerceLocationSetting::where('business_id', $business_id)->findOrFail($id);

            $data = $request->only([
                'location_id',
                'woocommerce_app_url',
                'woocommerce_consumer_key',
                'sync_interval_minutes',
                'is_active',
                'enable_auto_sync',
                'sync_products',
                'sync_orders',
                'sync_inventory',
                'sync_customers'
            ]);

            // Only update secret if provided
            if ($request->filled('woocommerce_consumer_secret')) {
                $data['woocommerce_consumer_secret'] = $request->woocommerce_consumer_secret;
            }

            // Set defaults for checkboxes
            $data['is_active'] = $request->has('is_active') ? 1 : 0;
            $data['enable_auto_sync'] = $request->has('enable_auto_sync') ? 1 : 0;
            $data['sync_products'] = $request->has('sync_products') ? 1 : 0;
            $data['sync_orders'] = $request->has('sync_orders') ? 1 : 0;
            $data['sync_inventory'] = $request->has('sync_inventory') ? 1 : 0;
            $data['sync_customers'] = $request->has('sync_customers') ? 1 : 0;

            $config->update($data);

            return response()->json([
                'success' => true,
                'message' => __('woocommerce::lang.location_config_updated_successfully')
            ]);

        } catch (\Exception $e) {
            \Log::error('Location WooCommerce config update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Delete location configuration
     */
    public function destroy($id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $config = WoocommerceLocationSetting::where('business_id', $business_id)->findOrFail($id);
            
            $config->delete();

            return response()->json([
                'success' => true,
                'message' => __('woocommerce::lang.location_config_deleted_successfully')
            ]);

        } catch (\Exception $e) {
            \Log::error('Location WooCommerce config deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Test API connection for a location
     */
    public function testConnection(Request $request): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'woocommerce_app_url' => 'required|url',
                'woocommerce_consumer_key' => 'required|string',
                'woocommerce_consumer_secret' => 'required|string'
            ]);

            // Create temporary client for testing
            $client = new \Automattic\WooCommerce\Client(
                rtrim($request->woocommerce_app_url, '/'),
                $request->woocommerce_consumer_key,
                $request->woocommerce_consumer_secret,
                [
                    'version' => 'wc/v3',
                    'verify_ssl' => false,
                    'timeout' => 10
                ]
            );

            // Test with simple system status call
            $response = $client->get('system_status');

            if (is_array($response) && isset($response['environment'])) {
                return response()->json([
                    'success' => true,
                    'message' => __('woocommerce::lang.connection_successful') . ' (WooCommerce ' . $response['version'] . ')'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('woocommerce::lang.unexpected_response')
            ], 400);

        } catch (\Exception $e) {
            \Log::error('WooCommerce connection test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('woocommerce::lang.connection_failed') . ': ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Trigger manual sync for a location
     */
    public function syncNow($id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $config = WoocommerceLocationSetting::where('business_id', $business_id)->findOrFail($id);

            if (!$config->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => __('woocommerce::lang.location_sync_disabled')
                ], 400);
            }

            // Dispatch sync job
            \Modules\Woocommerce\Jobs\SyncLocationData::dispatch($config);

            return response()->json([
                'success' => true,
                'message' => __('woocommerce::lang.sync_started_successfully')
            ]);

        } catch (\Exception $e) {
            \Log::error('Manual sync trigger failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get location settings for API
     */
    public function getLocationSettings($location_id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            \Log::info('Getting location settings', ['business_id' => $business_id, 'location_id' => $location_id]);
            
            $settings = WoocommerceLocationSetting::where('business_id', $business_id)
                                                  ->where('location_id', $location_id)
                                                  ->first();

            return response()->json([
                'success' => true,
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get location settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Save location settings via API
     */
    public function saveLocationSettings(Request $request): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $request->validate([
                'location_id' => 'required|integer|exists:business_locations,id',
                'woocommerce_app_url' => 'sometimes|url',
                'woocommerce_consumer_key' => 'sometimes|string',
                'woocommerce_consumer_secret' => 'sometimes|string',
                'webhook_secret' => 'sometimes|string|min:20',
                'sync_interval_minutes' => 'integer|min:5|max:1440',
            ]);

            // Check if location configuration exists
            $config = WoocommerceLocationSetting::where('business_id', $business_id)
                                                ->where('location_id', $request->location_id)
                                                ->first();

            // Generate webhook URL
            $webhook_url = url('modules/woocommerce/webhook/' . $request->location_id);

            $data = [
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'woocommerce_app_url' => $request->input('woocommerce_app_url'),
                'woocommerce_consumer_key' => $request->input('woocommerce_consumer_key'),
                'woocommerce_consumer_secret' => $request->input('woocommerce_consumer_secret'),
                'webhook_url' => $webhook_url,
                'webhook_secret' => $request->input('webhook_secret'),
                'sync_interval_minutes' => $request->input('sync_interval_minutes', 15),
                'is_active' => $request->input('is_active', 0),
                'enable_auto_sync' => $request->input('enable_auto_sync', 0),
                'sync_products' => $request->input('sync_products', 0),
                'sync_orders' => $request->input('sync_orders', 0),
                'sync_inventory' => $request->input('sync_inventory', 0),
                'sync_customers' => $request->input('sync_customers', 0),
            ];

            if ($config) {
                // Update existing configuration
                if ($request->filled('woocommerce_consumer_secret')) {
                    $data['woocommerce_consumer_secret'] = $request->woocommerce_consumer_secret;
                }
                $config->update($data);
                $message = __('woocommerce::lang.location_config_updated_successfully');
            } else {
                // Create new configuration
                $data['webhook_secret'] = $this->generateWebhookSecret();
                WoocommerceLocationSetting::create($data);
                $message = __('woocommerce::lang.location_config_created_successfully');
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Location WooCommerce config save failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Trigger sync for specific location
     */
    public function triggerLocationSync($location_id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $config = WoocommerceLocationSetting::where('business_id', $business_id)
                                                ->where('location_id', $location_id)
                                                ->first();

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => __('woocommerce::lang.location_config_not_found')
                ], 404);
            }

            if (!$config->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => __('woocommerce::lang.location_sync_disabled')
                ], 400);
            }

            // Get sync type from request, default to 'all'
            $syncType = request()->input('sync_type', 'all');
            
            // Map sync type names to match the job expectations
            $syncTypeMap = [
                'sync-products' => 'products',
                'sync-orders' => 'orders', 
                'sync-customers' => 'customers',
                'sync-inventory' => 'inventory',
                'full-sync' => 'all',
                'products' => 'products',
                'orders' => 'orders',
                'customers' => 'customers', 
                'inventory' => 'inventory',
                'all' => 'all'
            ];
            
            $syncType = $syncTypeMap[$syncType] ?? 'all';
            
            // Check if the specific sync type is enabled for this location
            $syncEnabled = true;
            switch ($syncType) {
                case 'products':
                    $syncEnabled = $config->sync_products;
                    break;
                case 'orders':
                    $syncEnabled = $config->sync_orders;
                    break;
                case 'customers':
                    $syncEnabled = $config->sync_customers;
                    break;
                case 'inventory':
                    $syncEnabled = $config->sync_inventory;
                    break;
            }
            
            if (!$syncEnabled && $syncType !== 'all') {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($syncType) . ' sync is disabled for this location.'
                ], 400);
            }
            
            // Create progress tracking record first
            $syncProgress = WoocommerceSyncProgress::createSync(
                $business_id,
                $location_id,
                $syncType
            );

            // Dispatch sync job with specific sync type
            \Modules\Woocommerce\Jobs\SyncLocationData::dispatch($config, $syncType);

            return response()->json([
                'success' => true,
                'message' => __('woocommerce::lang.sync_started_successfully') . ' (' . ucfirst($syncType) . ')',
                'sync_id' => $syncProgress->id,
                'sync_type' => $syncType,
                'location_id' => $location_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Manual location sync trigger failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Sync stock for a location
     */
    public function syncLocationStock(Request $request, $location_id): JsonResponse
    {
        if (!auth()->user()->can('superadmin') && !auth()->user()->can('woocommerce.access_woocommerce_api_settings')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            
            // Validate location belongs to business
            $location = BusinessLocation::where('id', $location_id)
                                      ->where('business_id', $business_id)
                                      ->first();
            
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found or access denied'
                ], 404);
            }

            // Get location setting
            $locationSetting = WoocommerceLocationSetting::where('business_id', $business_id)
                                                         ->where('location_id', $location_id)
                                                         ->where('is_active', true)
                                                         ->first();

            if (!$locationSetting) {
                return response()->json([
                    'success' => false,
                    'message' => 'WooCommerce not configured for this location'
                ], 400);
            }

            if (!$locationSetting->sync_inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory sync is disabled for this location'
                ], 400);
            }

            // Get sync options from request
            $options = [
                'sync_zero_stock' => $request->input('sync_zero_stock', true),
                'allow_backorders' => $request->input('allow_backorders', false),
                'low_stock_threshold' => $request->input('low_stock_threshold', null)
            ];

            // Initialize sync service
            $syncService = new \Modules\Woocommerce\Services\WooCommerceSyncService($business_id);
            
            // Trigger stock sync
            $result = $syncService->syncAllLocationStock($location_id, $options);

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Stock sync failed', [
                'location_id' => $location_id,
                'business_id' => $request->session()->get('user.business_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Stock sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate secure webhook secret
     */
    private function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}