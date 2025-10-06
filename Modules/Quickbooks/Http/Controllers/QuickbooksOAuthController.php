<?php

namespace Modules\Quickbooks\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Quickbooks\Models\QuickbooksLocationSettings;
use Modules\Quickbooks\Entities\QuickbooksAppConfig;
use Modules\Quickbooks\Services\QuickBooksTokenRefreshService;
use App\Utils\ModuleUtil;

class QuickbooksOAuthController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone']);
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Initiate OAuth flow for a specific business location
     * This redirects the business to QuickBooks for consent
     */
    public function initiateOAuth(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');

        // Validate permissions
        if (!auth()->user()->can('superadmin') && 
            !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'quickbooks_module', 'superadmin_package')) {
            return response()->json([
                'success' => false,
                'message' => 'QuickBooks integration requires an upgraded package.'
            ], 403);
        }

        try {
            // Get or create location settings
            $locationSettings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $location_id);
            
            if (!$locationSettings) {
                $locationSettings = QuickbooksLocationSettings::createForLocation($business_id, $location_id, [
                    'sandbox_mode' => $request->input('environment', 'sandbox')
                ]);
            }

            // Check if app config exists
            $appConfig = QuickbooksAppConfig::getActiveConfig($locationSettings->sandbox_mode);
            if (!$appConfig || !$appConfig->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QuickBooks app configuration not found. Please contact support.'
                ], 500);
            }

            // Generate authorization URL
            $authUrl = $locationSettings->getAuthorizationUrl();

            // Store the initiation in session for security
            session(['quickbooks_oauth_initiated' => [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'timestamp' => time()
            ]]);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl,
                'message' => 'Redirecting to QuickBooks for authorization...'
            ]);

        } catch (\Exception $e) {
            Log::error('QuickBooks OAuth initiation failed', [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate QuickBooks connection: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle OAuth callback from QuickBooks
     * This is where QuickBooks redirects after user consent
     */
    public function handleCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $realmId = $request->input('realmId'); // QuickBooks Company ID
        $error = $request->input('error');
        $errorDescription = $request->input('error_description');

        // Handle OAuth errors
        if ($error) {
            Log::warning('QuickBooks OAuth error', [
                'error' => $error,
                'description' => $errorDescription
            ]);

            return redirect()->route('quickbooks.index')
                           ->with('status', [
                               'success' => 0,
                               'msg' => 'QuickBooks authorization failed: ' . ($errorDescription ?? $error)
                           ]);
        }

        // Validate required parameters
        if (!$code || !$state || !$realmId) {
            return redirect()->route('quickbooks.index')
                           ->with('status', [
                               'success' => 0,
                               'msg' => 'Invalid callback parameters from QuickBooks.'
                           ]);
        }

        try {
            // Validate and decode state
            $stateData = QuickbooksLocationSettings::validateOAuthState($state);
            if (!$stateData) {
                throw new \Exception('Invalid or expired OAuth state parameter');
            }

            $business_id = $stateData['business_id'];
            $location_id = $stateData['location_id'];

            // Verify session consistency
            $sessionData = session('quickbooks_oauth_initiated');
            if (!$sessionData || 
                $sessionData['business_id'] != $business_id || 
                $sessionData['location_id'] != $location_id) {
                throw new \Exception('OAuth session mismatch');
            }

            // Get location settings
            $locationSettings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $location_id);
            if (!$locationSettings) {
                throw new \Exception('Location settings not found');
            }

            // Exchange authorization code for tokens
            $this->exchangeCodeForTokens($locationSettings, $code, $realmId);

            // Clear OAuth session
            session()->forget('quickbooks_oauth_initiated');

            return redirect()->route('quickbooks.index')
                           ->with('status', [
                               'success' => 1,
                               'msg' => 'Successfully connected to QuickBooks! You can now start syncing your data.'
                           ]);

        } catch (\Exception $e) {
            Log::error('QuickBooks OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('quickbooks.index')
                           ->with('status', [
                               'success' => 0,
                               'msg' => 'Failed to complete QuickBooks connection: ' . $e->getMessage()
                           ]);
        }
    }

    /**
     * Exchange authorization code for access and refresh tokens
     */
    private function exchangeCodeForTokens(QuickbooksLocationSettings $locationSettings, string $code, string $realmId)
    {
        $appConfig = $locationSettings->appConfig();
        if (!$appConfig) {
            throw new \Exception('App configuration not found');
        }

        // Prepare token request
        $tokenData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $appConfig->oauth_redirect_uri
        ];

        // Make token request
        $response = Http::asForm()
            ->withBasicAuth($appConfig->client_id, $appConfig->client_secret)
            ->post($appConfig->getTokenEndpoint(), $tokenData);

        if (!$response->successful()) {
            throw new \Exception('Token exchange failed: ' . $response->body());
        }

        $tokenResponse = $response->json();

        if (!isset($tokenResponse['access_token'], $tokenResponse['refresh_token'])) {
            throw new \Exception('Invalid token response from QuickBooks');
        }

        // Store tokens and company info
        DB::transaction(function () use ($locationSettings, $tokenResponse, $appConfig, $realmId) {
            $locationSettings->update([
                'company_id' => $realmId,
                'access_token' => $tokenResponse['access_token'],
                'refresh_token' => $tokenResponse['refresh_token'],
                'token_expires_at' => now()->addSeconds($tokenResponse['expires_in'] ?? 3600),
                'base_url' => $appConfig->getApiBaseUrl(),
                'connection_status' => 'connected',
                'connected_at' => now(),
                'is_active' => true,
                'last_sync_error' => null,
                'consecutive_failed_refreshes' => 0
            ]);

            // Fetch and store company info
            $this->fetchAndStoreCompanyInfo($locationSettings);
        });
    }

    /**
     * Fetch company information from QuickBooks and store it
     */
    private function fetchAndStoreCompanyInfo(QuickbooksLocationSettings $locationSettings)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $locationSettings->access_token,
                'Accept' => 'application/json'
            ])->get($locationSettings->base_url . '/v3/company/' . $locationSettings->company_id . '/companyinfo/1');

            if ($response->successful()) {
                $companyData = $response->json();
                $companyInfo = $companyData['QueryResponse']['CompanyInfo'][0] ?? null;

                if ($companyInfo) {
                    $locationSettings->update([
                        'quickbooks_company_name' => $companyInfo['CompanyName'] ?? null,
                        'quickbooks_country' => $companyInfo['Country'] ?? null,
                        'connection_metadata' => [
                            'company_info' => $companyInfo,
                            'fetched_at' => now()->toISOString()
                        ]
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Don't fail the whole process if company info fetch fails
            Log::warning('Failed to fetch QuickBooks company info', [
                'location_id' => $locationSettings->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Disconnect a location from QuickBooks
     */
    public function disconnect(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->input('location_id');

        // Validate permissions
        if (!auth()->user()->can('superadmin') && 
            !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'quickbooks_module', 'superadmin_package')) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions.'
            ], 403);
        }

        try {
            $locationSettings = QuickbooksLocationSettings::findByBusinessAndLocation($business_id, $location_id);
            
            if (!$locationSettings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location settings not found.'
                ], 404);
            }

            // Reset connection data
            $locationSettings->update([
                'connection_status' => 'disconnected',
                'company_id' => null,
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
                'connected_at' => null,
                'quickbooks_company_name' => null,
                'quickbooks_country' => null,
                'is_active' => false,
                'connection_metadata' => null,
                'last_sync_error' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully disconnected from QuickBooks.'
            ]);

        } catch (\Exception $e) {
            Log::error('QuickBooks disconnect failed', [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect: ' . $e->getMessage()
            ], 500);
        }
    }
}