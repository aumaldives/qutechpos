<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\ApiKey;
use App\Business;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ApiKeyManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('superadmin');
    }

    /**
     * Display API keys management page
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getApiKeysDataTable();
        }

        $businesses = Business::select('id', 'name')->orderBy('name')->get();
        
        return view('superadmin.api_keys.index', compact('businesses'));
    }

    /**
     * Get API keys DataTable data
     */
    private function getApiKeysDataTable()
    {
        $query = ApiKey::with(['business', 'user'])
            ->select('api_keys.*');

        return DataTables::of($query)
            ->addColumn('business_name', function ($apiKey) {
                return $apiKey->business ? $apiKey->business->name : 'N/A';
            })
            ->addColumn('user_name', function ($apiKey) {
                return $apiKey->user ? $apiKey->user->first_name . ' ' . $apiKey->user->last_name : 'System';
            })
            ->addColumn('access_level_badge', function ($apiKey) {
                $colors = [
                    'business' => 'primary',
                    'system' => 'warning', 
                    'superadmin' => 'danger'
                ];
                $color = $colors[$apiKey->access_level ?? 'business'] ?? 'secondary';
                return '<span class="label label-' . $color . '">' . ucfirst($apiKey->access_level ?? 'business') . '</span>';
            })
            ->addColumn('abilities_display', function ($apiKey) {
                $abilities = $apiKey->abilities ?? [];
                if (in_array('*', $abilities)) {
                    return '<span class="label label-success">All Permissions</span>';
                }
                return '<span class="label label-info">' . implode(', ', array_slice($abilities, 0, 3)) . 
                       (count($abilities) > 3 ? '...' : '') . '</span>';
            })
            ->addColumn('status', function ($apiKey) {
                $status = 'Active';
                $color = 'success';
                
                if (!$apiKey->is_active) {
                    $status = 'Inactive';
                    $color = 'danger';
                } elseif ($apiKey->isExpired()) {
                    $status = 'Expired';
                    $color = 'warning';
                }
                
                return '<span class="label label-' . $color . '">' . $status . '</span>';
            })
            ->addColumn('usage_info', function ($apiKey) {
                $lastUsed = $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'Never';
                $rateLimit = $apiKey->rate_limit_per_minute ?? 60;
                return "Last used: {$lastUsed}<br>Rate limit: {$rateLimit}/min";
            })
            ->addColumn('actions', function ($apiKey) {
                $actions = '';
                
                if ($apiKey->is_active) {
                    $actions .= '<button class="btn btn-xs btn-warning revoke-key" data-id="' . $apiKey->id . '">
                                    <i class="fa fa-ban"></i> Revoke
                                </button> ';
                } else {
                    $actions .= '<button class="btn btn-xs btn-success activate-key" data-id="' . $apiKey->id . '">
                                    <i class="fa fa-check"></i> Activate
                                </button> ';
                }
                
                $actions .= '<button class="btn btn-xs btn-danger delete-key" data-id="' . $apiKey->id . '">
                                <i class="fa fa-trash"></i> Delete
                            </button>';
                
                return $actions;
            })
            ->rawColumns(['access_level_badge', 'abilities_display', 'status', 'usage_info', 'actions'])
            ->make(true);
    }

    /**
     * Show create API key form
     */
    public function create()
    {
        $businesses = Business::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'first_name', 'last_name', 'business_id')->get();
        
        return view('superadmin.api_keys.create', compact('businesses', 'users'));
    }

    /**
     * Store new API key
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'business_id' => 'required|exists:businesses,id',
                'user_id' => 'nullable|exists:users,id',
                'access_level' => 'required|in:business,system,superadmin',
                'abilities' => 'required|array|min:1',
                'abilities.*' => 'required|string',
                'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
                'expires_at' => 'nullable|date|after:now'
            ]);

            // Only superadmin can create system/superadmin level keys
            if (in_array($request->access_level, ['system', 'superadmin'])) {
                if (!auth()->user()->can('superadmin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient permissions to create ' . $request->access_level . ' level API keys'
                    ], 403);
                }
            }

            $expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : null;

            $result = ApiKey::generateKey(
                $request->business_id,
                $request->user_id,
                $request->name,
                $request->abilities,
                $request->rate_limit_per_minute,
                $expires_at
            );

            // Update with access level and superadmin info
            $result['model']->update([
                'access_level' => $request->access_level,
                'is_internal' => in_array($request->access_level, ['system', 'superadmin']),
                'created_by_superadmin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => [
                    'api_key' => $result['api_key'],
                    'display_key' => $result['model']->display_key,
                    'access_level' => $result['model']->access_level,
                    'warning' => 'Save this API key now. You will not be able to see it again.'
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke API key
     */
    public function revoke(Request $request, $id): JsonResponse
    {
        try {
            $apiKey = ApiKey::findOrFail($id);
            $apiKey->revoke();

            return response()->json([
                'success' => true,
                'message' => 'API key revoked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke API key'
            ], 500);
        }
    }

    /**
     * Activate API key
     */
    public function activate(Request $request, $id): JsonResponse
    {
        try {
            $apiKey = ApiKey::findOrFail($id);
            $apiKey->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'API key activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate API key'
            ], 500);
        }
    }

    /**
     * Delete API key permanently
     */
    public function destroy($id): JsonResponse
    {
        try {
            $apiKey = ApiKey::findOrFail($id);
            $apiKey->delete();

            return response()->json([
                'success' => true,
                'message' => 'API key deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete API key'
            ], 500);
        }
    }

    /**
     * Get API key statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_keys' => ApiKey::count(),
            'active_keys' => ApiKey::where('is_active', true)->count(),
            'expired_keys' => ApiKey::where('expires_at', '<', now())->count(),
            'by_access_level' => [
                'business' => ApiKey::where('access_level', 'business')->count(),
                'system' => ApiKey::where('access_level', 'system')->count(),
                'superadmin' => ApiKey::where('access_level', 'superadmin')->count(),
            ],
            'recent_usage' => ApiKey::whereNotNull('last_used_at')
                ->where('last_used_at', '>', now()->subDays(7))
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}