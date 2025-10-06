<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ApiKey;
use App\ApiUsageLog;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class ApiKeyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of API keys for the current business.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');

        if ($request->ajax()) {
            $api_keys = ApiKey::where('business_id', $business_id)
                ->with(['user'])
                ->orderBy('created_at', 'desc');

            return DataTables::of($api_keys)
                ->addColumn('action', function ($api_key) {
                    $actions = '<div class="btn-group">';
                    $actions .= '<button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . __('messages.actions') . ' <span class="caret"></span></button>';
                    $actions .= '<ul class="dropdown-menu dropdown-menu-right">';
                    
                    $actions .= '<li><a href="' . route('api-keys.show', $api_key->id) . '"><i class="fas fa-eye"></i> View Usage</a></li>';
                    
                    if ($api_key->is_active) {
                        $actions .= '<li><a href="#" class="revoke-api-key" data-href="' . route('api-keys.revoke', $api_key->id) . '"><i class="fas fa-ban"></i> Revoke</a></li>';
                    } else {
                        $actions .= '<li><a href="#" class="activate-api-key" data-href="' . route('api-keys.activate', $api_key->id) . '"><i class="fas fa-check"></i> Activate</a></li>';
                    }
                    
                    $actions .= '<li><a href="#" class="delete-api-key" data-href="' . route('api-keys.destroy', $api_key->id) . '"><i class="fas fa-trash"></i> Delete</a></li>';
                    $actions .= '</ul></div>';
                    
                    return $actions;
                })
                ->editColumn('name', function ($api_key) {
                    return $api_key->name;
                })
                ->editColumn('display_key', function ($api_key) {
                    return '<code>' . $api_key->display_key . '</code>';
                })
                ->editColumn('abilities', function ($api_key) {
                    if (!$api_key->abilities) {
                        return '<span class="label label-default">None</span>';
                    }
                    $badges = '';
                    foreach ($api_key->abilities as $ability) {
                        $badges .= '<span class="label label-info">' . $ability . '</span> ';
                    }
                    return $badges;
                })
                ->editColumn('is_active', function ($api_key) {
                    if ($api_key->is_active && !$api_key->isExpired()) {
                        return '<span class="label label-success">Active</span>';
                    } elseif ($api_key->isExpired()) {
                        return '<span class="label label-warning">Expired</span>';
                    } else {
                        return '<span class="label label-danger">Revoked</span>';
                    }
                })
                ->editColumn('last_used_at', function ($api_key) {
                    return $api_key->last_used_at ? $api_key->last_used_at->diffForHumans() : 'Never';
                })
                ->editColumn('created_by', function ($api_key) {
                    return $api_key->user ? $api_key->user->first_name . ' ' . $api_key->user->last_name : 'System';
                })
                ->editColumn('expires_at', function ($api_key) {
                    return $api_key->expires_at ? $api_key->expires_at->format('M d, Y') : 'Never';
                })
                ->rawColumns(['action', 'display_key', 'abilities', 'is_active'])
                ->make(true);
        }

        return view('api_keys.index');
    }

    /**
     * Show the form for creating a new API key.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $available_abilities = [
            'read' => 'Read Access - View data',
            'write' => 'Write Access - Create and update data', 
            'delete' => 'Delete Access - Remove data',
            'products' => 'Products - Manage products and inventory',
            'transactions' => 'Transactions - Manage sales and purchases',
            'contacts' => 'Contacts - Manage customers and suppliers',
            'reports' => 'Reports - Access business reports'
            // Note: Business settings are read-only via API for security
        ];

        return view('api_keys.create', compact('available_abilities'));
    }

    /**
     * Store a newly created API key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string|in:read,write,delete,products,transactions,contacts,reports',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'expires_at' => 'nullable|date|after:now'
        ]);

        try {
            $expires_at = $request->expires_at ? Carbon::parse($request->expires_at) : null;
            
            $result = ApiKey::generateKey(
                $business_id,
                auth()->id(),
                $request->name,
                $request->abilities,
                $request->rate_limit_per_minute,
                $expires_at
            );

            $output = [
                'success' => true,
                'msg' => 'API key created successfully!',
                'api_key' => $result['api_key'],
                'api_key_id' => $result['model']->id
            ];

        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => 'Failed to create API key: ' . $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        if ($output['success']) {
            // Store the generated key in session to show it once
            session(['new_api_key' => $result['api_key']]);
            return redirect()->route('api-keys.index')->with('status', $output);
        } else {
            return redirect()->back()->with('status', $output)->withInput();
        }
    }

    /**
     * Display API key usage statistics.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        
        $api_key = ApiKey::where('business_id', $business_id)->findOrFail($id);
        
        // Get usage statistics for different time periods
        $stats = [
            'last_24h' => ApiUsageLog::getUsageStats($api_key->id, now()->subDay()),
            'last_7d' => ApiUsageLog::getUsageStats($api_key->id, now()->subWeek()),
            'last_30d' => ApiUsageLog::getUsageStats($api_key->id, now()->subMonth()),
            'all_time' => ApiUsageLog::getUsageStats($api_key->id)
        ];

        // Get recent usage logs
        if ($request->ajax() && $request->get('logs')) {
            $logs = ApiUsageLog::where('api_key_id', $api_key->id)
                ->orderBy('created_at', 'desc')
                ->limit(100);

            return DataTables::of($logs)
                ->editColumn('created_at', function ($log) {
                    return $log->created_at->format('M d, Y H:i:s');
                })
                ->editColumn('endpoint', function ($log) {
                    return '<code>' . $log->method . '</code> ' . $log->endpoint;
                })
                ->editColumn('response_status', function ($log) {
                    $class = $log->response_status < 400 ? 'success' : 'danger';
                    return '<span class="label label-' . $class . '">' . $log->response_status . '</span>';
                })
                ->editColumn('response_time_ms', function ($log) {
                    return $log->response_time_ms . ' ms';
                })
                ->editColumn('ip_address', function ($log) {
                    return '<code>' . $log->ip_address . '</code>';
                })
                ->rawColumns(['endpoint', 'response_status', 'ip_address'])
                ->make(true);
        }

        return view('api_keys.show', compact('api_key', 'stats'));
    }

    /**
     * Revoke an API key.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function revoke(Request $request, $id)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        
        try {
            $api_key = ApiKey::where('business_id', $business_id)->findOrFail($id);
            $api_key->revoke();

            $output = [
                'success' => true,
                'msg' => 'API key revoked successfully!'
            ];

        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => 'Failed to revoke API key: ' . $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('api-keys.index')->with('status', $output);
    }

    /**
     * Activate an API key.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $id)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        
        try {
            $api_key = ApiKey::where('business_id', $business_id)->findOrFail($id);
            $api_key->update(['is_active' => true]);

            $output = [
                'success' => true,
                'msg' => 'API key activated successfully!'
            ];

        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => 'Failed to activate API key: ' . $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('api-keys.index')->with('status', $output);
    }

    /**
     * Remove an API key.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->can('manage_api_keys')) {
            abort(403, 'Unauthorized action.');
        }
        
        $business_id = $request->session()->get('user.business_id');
        
        try {
            $api_key = ApiKey::where('business_id', $business_id)->findOrFail($id);
            $api_key->delete();

            $output = [
                'success' => true,
                'msg' => 'API key deleted successfully!'
            ];

        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => 'Failed to delete API key: ' . $e->getMessage()
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->route('api-keys.index')->with('status', $output);
    }
}
