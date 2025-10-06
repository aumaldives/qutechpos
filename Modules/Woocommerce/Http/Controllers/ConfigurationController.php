<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Business;
use Modules\Woocommerce\Utils\WoocommerceUtil;
use Modules\Woocommerce\Utils\EnhancedWoocommerceUtil;
use Modules\Woocommerce\Entities\WoocommerceSyncLog;
use Automattic\WooCommerce\Client;
use Exception;

class ConfigurationController extends Controller
{
    protected WoocommerceUtil $woocommerceUtil;
    protected EnhancedWoocommerceUtil $enhancedWoocommerceUtil;

    public function __construct(WoocommerceUtil $woocommerceUtil)
    {
        $this->woocommerceUtil = $woocommerceUtil;
        
        // Inject dependencies for EnhancedWoocommerceUtil using app container
        $this->enhancedWoocommerceUtil = app()->make(EnhancedWoocommerceUtil::class);
    }

    /**
     * Display the WooCommerce configuration page
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');
        
        // Get business locations for the dropdown
        $business_locations = \App\BusinessLocation::where('business_id', $business_id)->get();
        
        return view('woocommerce::configuration.index', compact('business_locations'));
    }

    /**
     * Get configuration settings for the interface
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $businessId = $request->session()->get('business.id');
            $settings = $this->woocommerceUtil->get_api_settings($businessId);

            return response()->json([
                'success' => true,
                'data' => [
                    'store_url' => $settings->woocommerce_api_url ?? '',
                    'consumer_key' => $settings->woocommerce_api_consumer_key ? '****' . substr($settings->woocommerce_api_consumer_key, -4) : '',
                    'consumer_secret' => $settings->woocommerce_api_consumer_secret ? '****' . substr($settings->woocommerce_api_consumer_secret, -4) : '',
                    'api_version' => $settings->woocommerce_api_version ?? 'wc/v3',
                    'auto_sync' => $settings->woocommerce_auto_sync ?? false
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update configuration settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'store_url' => 'required|url',
            'consumer_key' => 'required|string|min:10',
            'consumer_secret' => 'required|string|min:10',
            'api_version' => 'required|in:wc/v1,wc/v2,wc/v3',
            'auto_sync' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessId = $request->session()->get('business.id');
            $business = Business::find($businessId);

            $settings = [
                'woocommerce_api_url' => $request->store_url,
                'woocommerce_api_consumer_key' => $request->consumer_key,
                'woocommerce_api_consumer_secret' => $request->consumer_secret,
                'woocommerce_api_version' => $request->api_version,
                'woocommerce_auto_sync' => $request->auto_sync ?? false
            ];

            $business->woocommerce_api_settings = json_encode($settings);
            $business->save();

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WooCommerce API connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $businessId = $request->session()->get('business.id');
            
            if (empty($businessId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business session not found. Please login again.'
                ]);
            }

            $settings = $this->woocommerceUtil->get_api_settings($businessId);

            // Enhanced validation
            if (empty($settings) || empty($settings->woocommerce_api_url) || empty($settings->woocommerce_api_consumer_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please configure API settings first. Store URL and Consumer Key are required.'
                ]);
            }

            // Validate URL format
            if (!filter_var($settings->woocommerce_api_url, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid store URL format. Please enter a valid URL (e.g., https://yourstore.com)'
                ]);
            }

            $client = new Client(
                $settings->woocommerce_api_url,
                $settings->woocommerce_api_consumer_key,
                $settings->woocommerce_api_consumer_secret,
                [
                    'wp_api' => true,
                    'version' => $settings->woocommerce_api_version ?? 'wc/v3',
                    'timeout' => 30,
                    'verify_ssl' => false // Allow self-signed certificates for testing
                ]
            );

            // Test connection by getting system status
            $response = $client->get('system_status');

            if (empty($response)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty response from WooCommerce API. Please check your API credentials.'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Connection successful! WooCommerce API is accessible.',
                'data' => [
                    'store_name' => $response['settings']['title'] ?? 'Unknown',
                    'wc_version' => $this->extractWooCommerceVersion($response),
                    'wp_version' => $response['wp_version'] ?? 'Unknown',
                    'active_plugins' => count($response['active_plugins'] ?? [])
                ]
            ]);
        } catch (\Automattic\WooCommerce\HttpClient\HttpClientException $e) {
            // Handle specific WooCommerce API errors
            $message = $this->parseWooCommerceError($e);
            return response()->json([
                'success' => false,
                'message' => $message
            ]);
        } catch (Exception $e) {
            Log::error('WooCommerce connection test failed', [
                'business_id' => $businessId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $this->simplifyErrorMessage($e->getMessage())
            ]);
        }
    }

    /**
     * Parse WooCommerce specific API errors
     */
    private function parseWooCommerceError($e): string
    {
        $message = $e->getMessage();
        
        if (strpos($message, 'consumer_key_unknown') !== false) {
            return 'Invalid Consumer Key. Please check your WooCommerce API credentials.';
        } elseif (strpos($message, 'consumer_secret_unknown') !== false) {
            return 'Invalid Consumer Secret. Please check your WooCommerce API credentials.';
        } elseif (strpos($message, 'woocommerce_rest_authentication_error') !== false) {
            return 'WooCommerce authentication failed. Please verify your API keys and permissions.';
        } elseif (strpos($message, 'rest_no_route') !== false) {
            return 'WooCommerce REST API not found. Please ensure WooCommerce is installed and activated.';
        } elseif (strpos($message, 'cURL error 28') !== false) {
            return 'Connection timeout. Please check your store URL and internet connection.';
        } elseif (strpos($message, 'cURL error 7') !== false) {
            return 'Could not connect to store. Please verify your store URL is correct.';
        } else {
            return 'API Error: ' . $message;
        }
    }

    /**
     * Simplify generic error messages
     */
    private function simplifyErrorMessage(string $message): string
    {
        // Remove technical details and provide user-friendly messages
        if (strpos($message, 'cURL error') !== false) {
            return 'Connection failed. Please check your store URL and internet connection.';
        } elseif (strpos($message, 'SSL certificate') !== false) {
            return 'SSL certificate issue. Please contact your hosting provider.';
        } elseif (strpos($message, 'HTTP 404') !== false) {
            return 'Store not found. Please check your store URL.';
        } elseif (strpos($message, 'HTTP 403') !== false) {
            return 'Access denied. Please check your API permissions.';
        } elseif (strpos($message, 'HTTP 500') !== false) {
            return 'Store server error. Please try again later or contact your hosting provider.';
        } else {
            return strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
        }
    }

    /**
     * Extract WooCommerce version from response
     */
    private function extractWooCommerceVersion($response): string
    {
        if (isset($response['active_plugins'])) {
            foreach ($response['active_plugins'] as $plugin) {
                if (isset($plugin['name']) && strpos(strtolower($plugin['name']), 'woocommerce') !== false) {
                    return $plugin['version'] ?? 'Unknown';
                }
            }
        }
        return 'Unknown';
    }

    /**
     * Get synchronization statistics with caching
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $businessId = $request->session()->get('business.id');
            
            if (empty($businessId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business session not found'
                ], 401);
            }
            
            // Cache statistics for 5 minutes to improve performance
            $cacheKey = "woocommerce_stats_{$businessId}";
            
            $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($businessId) {
                // Optimize queries with select and indexing
                $productsCount = \App\Product::where('business_id', $businessId)
                    ->whereNotNull('woocommerce_product_id')
                    ->count();
                
                $ordersCount = \App\Transaction::where('business_id', $businessId)
                    ->where('type', 'sell')
                    ->whereNotNull('woocommerce_order_id')
                    ->count();
                
                $categoriesCount = \App\Category::where('business_id', $businessId)
                    ->whereNotNull('woocommerce_cat_id')
                    ->count();
                
                $lastSync = WoocommerceSyncLog::where('business_id', $businessId)
                    ->select(['created_at', 'sync_type'])
                    ->latest()
                    ->first();

                return [
                    'products_count' => $productsCount,
                    'orders_count' => $ordersCount,
                    'categories_count' => $categoriesCount,
                    'last_sync' => $lastSync ? $lastSync->created_at->diffForHumans() : 'Never',
                    'last_sync_type' => $lastSync ? ucfirst($lastSync->sync_type) : null,
                    'total_synced' => $productsCount + $ordersCount + $categoriesCount,
                    'sync_health' => $this->calculateSyncHealth($productsCount, $ordersCount, $categoriesCount, $lastSync)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            Log::error('Failed to load WooCommerce statistics', [
                'business_id' => $businessId ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Calculate sync health score
     */
    private function calculateSyncHealth($products, $orders, $categories, $lastSync): array
    {
        $score = 0;
        $issues = [];
        
        // Check if there's any synced data
        if ($products > 0 || $orders > 0 || $categories > 0) {
            $score += 40; // Base score for having synced data
        } else {
            $issues[] = 'No synchronized data found';
        }
        
        // Check recent sync activity
        if ($lastSync) {
            $hoursSinceSync = $lastSync->created_at->diffInHours(now());
            if ($hoursSinceSync < 24) {
                $score += 30; // Recent sync
            } elseif ($hoursSinceSync < 168) { // 1 week
                $score += 20; // Moderate sync
                $issues[] = 'Last sync was more than 24 hours ago';
            } else {
                $score += 10; // Old sync
                $issues[] = 'Last sync was more than a week ago';
            }
        } else {
            $issues[] = 'No sync history found';
        }
        
        // Check data balance
        if ($products > 0 && $categories > 0) {
            $score += 20; // Good product-category relationship
        }
        
        if ($orders > 0) {
            $score += 10; // Order sync is working
        }
        
        // Determine health status
        if ($score >= 80) {
            $status = 'excellent';
            $color = 'success';
        } elseif ($score >= 60) {
            $status = 'good';
            $color = 'info';
        } elseif ($score >= 40) {
            $status = 'fair';
            $color = 'warning';
        } else {
            $status = 'poor';
            $color = 'danger';
        }
        
        return [
            'score' => $score,
            'status' => $status,
            'color' => $color,
            'issues' => $issues
        ];
    }

    /**
     * Start synchronization process
     */
    public function startSync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sync_type' => 'required|in:products,orders,categories,full'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid sync type'
            ], 422);
        }

        try {
            $businessId = $request->session()->get('business.id');
            $syncType = $request->sync_type;
            $userId = auth()->id();

            // Log sync start
            WoocommerceSyncLog::create([
                'business_id' => $businessId,
                'sync_type' => $syncType,
                'operation_type' => 'created',
                'details' => json_encode(['status' => 'started', 'user_id' => $userId]),
                'created_by' => $userId
            ]);

            // Start the appropriate sync process with enhanced error handling and progress tracking
            switch ($syncType) {
                case 'products':
                    $result = $this->enhancedWoocommerceUtil->syncProductsEnhanced($businessId, $userId, 'all', 50);
                    break;
                case 'orders':
                    $result = $this->enhancedWoocommerceUtil->syncOrdersEnhanced($businessId, $userId);
                    break;
                case 'categories':
                    $result = $this->enhancedWoocommerceUtil->syncCategoriesEnhanced($businessId, $userId);
                    break;
                case 'full':
                    // Run all sync operations with enhanced processing
                    $categoryResult = $this->enhancedWoocommerceUtil->syncCategoriesEnhanced($businessId, $userId);
                    $productResult = $this->enhancedWoocommerceUtil->syncProductsEnhanced($businessId, $userId, 'all', 50);
                    $result = $this->enhancedWoocommerceUtil->syncOrdersEnhanced($businessId, $userId);
                    
                    // Combine results for full sync
                    if ($categoryResult['success'] && $productResult['success'] && $result['success']) {
                        $result = [
                            'success' => true,
                            'message' => 'Full synchronization completed successfully',
                            'stats' => [
                                'categories' => $categoryResult['stats'],
                                'products' => $productResult['stats'],
                                'orders' => $result['stats']
                            ]
                        ];
                    }
                    break;
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'stats' => $result['stats'] ?? null
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Synchronization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sync logs for display
     */
    public function getSyncLogs(Request $request): JsonResponse
    {
        try {
            $businessId = $request->session()->get('business.id');
            
            $logs = WoocommerceSyncLog::where('business_id', $businessId)
                ->latest()
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'type' => ucfirst($log->sync_type),
                        'operation' => $log->operation_type,
                        'time' => $log->created_at->format('Y-m-d H:i:s'),
                        'details' => $log->details ? json_decode($log->details) : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load sync logs'
            ], 500);
        }
    }

    /**
     * Save webhook secret independent of API configuration
     */
    public function saveWebhookSecret(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_secret' => 'required|string|min:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook secret must be at least 20 characters long',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessId = $request->session()->get('business.id');
            $business = Business::find($businessId);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found'
                ], 404);
            }

            // Get existing settings or create empty array
            $existingSettings = $business->woocommerce_api_settings ? 
                json_decode($business->woocommerce_api_settings, true) : [];

            // Update only the webhook secret
            $existingSettings['woocommerce_webhook_secret'] = $request->webhook_secret;

            $business->woocommerce_api_settings = json_encode($existingSettings);
            $business->save();

            return response()->json([
                'success' => true,
                'message' => 'Webhook secret saved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save webhook secret: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook secret independent of API configuration
     */
    public function getWebhookSecret(Request $request): JsonResponse
    {
        try {
            $businessId = $request->session()->get('business.id');
            $business = Business::find($businessId);

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found'
                ], 404);
            }

            $settings = $business->woocommerce_api_settings ? 
                json_decode($business->woocommerce_api_settings, true) : [];

            $webhookSecret = $settings['woocommerce_webhook_secret'] ?? '';

            return response()->json([
                'success' => true,
                'data' => [
                    'webhook_secret' => $webhookSecret ? '****' . substr($webhookSecret, -4) : '',
                    'has_secret' => !empty($webhookSecret),
                    'webhook_url' => url('/webhook/order-created/' . $businessId)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load webhook secret: ' . $e->getMessage()
            ], 500);
        }
    }
}