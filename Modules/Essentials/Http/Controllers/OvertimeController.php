<?php

namespace Modules\Essentials\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsOvertimeRequest;
use Modules\Essentials\Utils\EssentialsUtil;
use Yajra\DataTables\Facades\DataTables;

class OvertimeController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;
    protected $essentialsUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
    }

    /**
     * Display a listing of overtime requests
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_superadmin = auth()->user()->can('superadmin');
        $can_manage_all_overtime = auth()->user()->can('essentials.crud_all_overtime') || $is_superadmin;
        $can_manage_own_overtime = auth()->user()->can('essentials.crud_own_overtime') || $is_superadmin;
        $view_type = request()->get('view', 'my'); // Default to 'my' view

        // Check if user has access to essentials module
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }
        
        // Check permissions based on view type
        if ($view_type === 'all' && !$can_manage_all_overtime) {
            abort(403, 'You do not have permission to view all overtime requests.');
        }
        
        // For 'my' view, allow if user can manage all overtime OR own overtime OR is any employee with essentials access
        if ($view_type === 'my') {
            // Any user with essentials access can view their own overtime requests
            $can_manage_own_overtime = true;
        }

        if (request()->ajax()) {
            $query = EssentialsOvertimeRequest::with(['user', 'approver'])
                ->forBusiness($business_id)
                ->orderBy('created_at', 'desc');

            // Filter based on view type
            if ($view_type === 'my') {
                // Show only current user's overtime requests
                $query->where('user_id', auth()->user()->id);
                
                // Apply filters for 'my' view as well
                if (request()->filled('status')) {
                    $query->where('status', request('status'));
                }
                if (request()->filled('overtime_type')) {
                    $query->where('overtime_type', request('overtime_type'));
                }
            } else {
                // Admin view - show all requests with filters
                if (request()->filled('user_id')) {
                    $query->where('user_id', request('user_id'));
                }
                if (request()->filled('status')) {
                    $query->where('status', request('status'));
                }
                if (request()->filled('overtime_type')) {
                    $query->where('overtime_type', request('overtime_type'));
                }
            }

            if (request()->filled('date_range')) {
                $date_range = explode(' - ', request('date_range'));
                if (count($date_range) == 2) {
                    $query->whereBetween('overtime_date', [
                        \Carbon::createFromFormat('d/m/Y', $date_range[0])->format('Y-m-d'),
                        \Carbon::createFromFormat('d/m/Y', $date_range[1])->format('Y-m-d')
                    ]);
                }
            }

            return Datatables::of($query)
                ->addColumn('employee_name', function ($row) {
                    return $row->user->user_full_name ?? '';
                })
                ->addColumn('formatted_date', function ($row) {
                    return \Carbon::parse($row->overtime_date)->format('M d, Y');
                })
                ->addColumn('time_range', function ($row) {
                    return \Carbon::parse($row->start_time)->format('H:i') . ' - ' . 
                           \Carbon::parse($row->end_time)->format('H:i');
                })
                ->addColumn('hours_display', function ($row) {
                    // Convert decimal hours to HH:MM format
                    $hours = floor($row->hours_requested);
                    $minutes = round(($row->hours_requested - $hours) * 60);
                    return sprintf('%02d:%02d', $hours, $minutes);
                })
                ->addColumn('overtime_type_label', function ($row) {
                    return $row->getOvertimeTypeLabel();
                })
                ->addColumn('status_badge', function ($row) {
                    $status = $row->getStatusLabel();
                    return '<span class="label ' . $status['class'] . '">' . $status['label'] . '</span>';
                })
                ->addColumn('amount_display', function ($row) {
                    // Show approved amount for approved requests
                    if ($row->status == 'approved' && !empty($row->total_amount)) {
                        return '<span class="text-success">' . number_format($row->total_amount, 2) . '</span>';
                    }
                    
                    // Show estimated amount for pending/rejected requests if calculations are available
                    if ($row->hourly_rate && $row->multiplier_rate) {
                        $estimated_amount = $row->hours_requested * $row->hourly_rate * $row->multiplier_rate;
                        $label = $row->status == 'pending' ? 'warning' : 'muted';
                        $prefix = $row->status == 'pending' ? 'Est: ' : '';
                        return '<span class="text-' . $label . '">' . $prefix . number_format($estimated_amount, 2) . '</span>';
                    }
                    
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('action', function ($row) use ($can_manage_all_overtime, $view_type) {
                    $html = '';
                    
                    // Show approve/reject buttons only in admin view for pending requests
                    if ($view_type === 'all' && $can_manage_all_overtime && $row->canBeApproved()) {
                        $html .= '<button type="button" class="btn btn-xs btn-success approve-overtime" data-id="' . $row->id . '" title="Approve">
                                    <i class="fa fa-check"></i>
                                  </button> ';
                        $html .= '<button type="button" class="btn btn-xs btn-danger reject-overtime" data-id="' . $row->id . '" title="Reject">
                                    <i class="fa fa-times"></i>
                                  </button> ';
                    }
                    
                    $html .= '<button type="button" class="btn btn-xs btn-info view-overtime" data-id="' . $row->id . '" title="View Details">
                                <i class="fa fa-eye"></i>
                              </button> ';
                    
                    // Edit button logic
                    $can_edit = false;
                    if ($view_type === 'all' && $can_manage_all_overtime) {
                        // Admin in 'all' view can edit any request at any state
                        $can_edit = true;
                    } else {
                        // In 'my' view OR non-admin: can only edit own pending or draft requests
                        $is_own_request = $row->user_id == auth()->user()->id;
                        $is_editable = in_array($row->status, ['draft', 'pending']);
                        $can_edit = $is_own_request && $is_editable;
                    }
                    
                    if ($can_edit) {
                        $html .= '<a href="' . action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'edit'], [$row->id]) . '" class="btn btn-xs btn-warning" title="Edit">
                                    <i class="fa fa-edit"></i>
                                  </a> ';
                    }
                    
                    // Delete button logic
                    $can_delete = false;
                    if ($view_type === 'all' && $can_manage_all_overtime) {
                        // Admin in 'all' view can delete any request at any state
                        $can_delete = true;
                    } else {
                        // In 'my' view OR non-admin: can only delete own pending or draft requests
                        $is_own_request = $row->user_id == auth()->user()->id;
                        $is_deletable = in_array($row->status, ['draft', 'pending']);
                        $can_delete = $is_own_request && $is_deletable;
                    }
                    
                    if ($can_delete) {
                        $html .= '<button type="button" class="btn btn-xs btn-danger delete-overtime" data-id="' . $row->id . '" title="Delete">
                                    <i class="fa fa-trash"></i>
                                  </button>';
                    }
                    
                    return $html;
                })
                ->rawColumns(['status_badge', 'amount_display', 'action'])
                ->make(true);
        }

        // Get filter data for view
        $users = \App\User::where('business_id', $business_id)->user()->get();
        
        return view('essentials::overtime.index', compact(
            'users', 
            'view_type', 
            'can_manage_all_overtime', 
            'can_manage_own_overtime'
        ));
    }

    /**
     * Show the form for creating a new overtime request
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        // Allow anyone with essentials module access to create overtime requests
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $overtime_settings = $this->essentialsUtil->getOvertimeSettings($business_id);

        return view('essentials::overtime.create', compact('overtime_settings'));
    }

    /**
     * Store a newly created overtime request
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        // Allow anyone with essentials module access to create overtime requests
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            // Basic validation first
            $request->validate([
                'overtime_date' => 'required',
                'start_time' => 'required',
                'end_time' => 'required', 
                'reason' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            // Validate date format
            if (!$request->overtime_date || !\Carbon::hasFormat($request->overtime_date, 'd/m/Y')) {
                return back()->withErrors(['overtime_date' => 'Invalid date format. Please use dd/mm/yyyy format.'])->withInput();
            }

            // Validate date range
            $input_date = \Carbon::createFromFormat('d/m/Y', $request->overtime_date);
            $min_date = \Carbon::now()->subDays(30);
            $max_date = \Carbon::now();

            if ($input_date->lt($min_date)) {
                return back()->withErrors(['overtime_date' => 'Date must be within last 30 days. Minimum: ' . $min_date->format('d/m/Y')])->withInput();
            }

            if ($input_date->gt($max_date)) {
                return back()->withErrors(['overtime_date' => 'Date cannot be in future. Maximum: ' . $max_date->format('d/m/Y')])->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['overtime_date' => 'Date validation error: ' . $e->getMessage()])->withInput();
        }

        try {
            // Calculate hours - parse dd/mm/yyyy format explicitly
            $date_formatted = \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->format('Y-m-d');
            $start = \Carbon::parse($date_formatted . ' ' . $request->start_time);
            $end = \Carbon::parse($date_formatted . ' ' . $request->end_time);
            if ($end->lt($start)) {
                $end->addDay();
            }
            $total_minutes = $start->diffInMinutes($end);
            $hours = $total_minutes / 60;

            // Get overtime settings to check minimum duration
            $overtime_settings = $this->essentialsUtil->getOvertimeSettings($business_id);
            $minimum_minutes = $overtime_settings['overtime_minimum_minutes'] ?? 15;
            
            // Validate minimum overtime duration
            if ($total_minutes < $minimum_minutes) {
                return response()->json([
                    'success' => false,
                    'msg' => trans('essentials::lang.overtime_duration_too_short', ['minimum' => $minimum_minutes])
                ]);
            }

            // Validate maximum overtime duration (reasonable daily limit of 12 hours)
            if ($hours > 12) {
                return response()->json([
                    'success' => false,
                    'msg' => trans('essentials::lang.overtime_duration_too_long')
                ]);
            }

            // Check for overlapping overtime requests
            $user = auth()->user();
            $overlapping_request = \Modules\Essentials\Entities\EssentialsOvertimeRequest::where('business_id', $business_id)
                ->where('user_id', $user->id)
                ->where('overtime_date', $date_formatted)
                ->where('status', '!=', 'rejected')
                ->where(function ($query) use ($request) {
                    $start_time = $request->start_time;
                    $end_time = $request->end_time;
                    
                    // Check for any time overlap
                    $query->where(function ($q) use ($start_time, $end_time) {
                        $q->where('start_time', '<', $end_time)
                          ->where('end_time', '>', $start_time);
                    });
                })
                ->exists();

            if ($overlapping_request) {
                return response()->json([
                    'success' => false,
                    'msg' => trans('essentials::lang.overtime_request_overlap')
                ]);
            }

            // Determine overtime type
            $overtime_type = $this->essentialsUtil->getOvertimeType($date_formatted, $user, $business_id);
            
            // Calculate rates if auto-approved
            $hourly_rate = $this->essentialsUtil->calculateHourlyRate(
                $user->id, 
                $user->essentials_salary, 
                \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->month,
                \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->year,
                $business_id
            );
            
            $multiplier_map = [
                'workday' => $overtime_settings['workday_multiplier'],
                'weekend' => $overtime_settings['weekend_multiplier'],
                'holiday' => $overtime_settings['holiday_multiplier']
            ];
            $multiplier = $multiplier_map[$overtime_type] ?? 1.5;

            $overtime_request = EssentialsOvertimeRequest::create([
                'business_id' => $business_id,
                'user_id' => $user->id,
                'overtime_date' => $date_formatted,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_requested' => round($hours, 2),
                'overtime_type' => $overtime_type,
                'reason' => $request->reason,
                'description' => $request->description,
                'status' => $overtime_settings['approval_required'] ? 'pending' : 'approved',
                'approved_hours' => $overtime_settings['approval_required'] ? null : round($hours, 2),
                'multiplier_rate' => $multiplier,
                'hourly_rate' => $hourly_rate,
                'total_amount' => $hours * $hourly_rate * $multiplier, // Always calculate for display purposes
                'approved_by' => $overtime_settings['approval_required'] ? null : null,
                'approved_at' => $overtime_settings['approval_required'] ? null : now()
            ]);

            $output = [
                'success' => 1,
                'msg' => $overtime_settings['approval_required'] ? 
                    __('essentials::lang.overtime_request_submitted') : 
                    __('essentials::lang.overtime_request_auto_approved')
            ];

        } catch (\Exception $e) {
            \Log::emergency('Overtime submission error - File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => 'Error: ' . $e->getMessage() . ' (Check logs for details)'
            ];
        }

        // Redirect to the user's overtime requests view
        return redirect('/hrm/overtime?view=my')->with(['status' => $output]);
    }

    /**
     * Approve overtime request
     */
    public function approve(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('essentials.crud_all_overtime') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $overtime_request = EssentialsOvertimeRequest::forBusiness($business_id)->findOrFail($id);

        if (!$overtime_request->canBeApproved()) {
            return response()->json(['success' => false, 'msg' => 'This overtime request cannot be approved.']);
        }

        try {
            // Parse approved_hours from HH:MM format  
            $hours = (int)$request->input('approved_hours_hour', 0);
            $minutes = (int)$request->input('approved_hours_minute', 0);
            $approved_hours = $hours + ($minutes / 60);
            
            // If no hours specified, use requested hours
            if ($approved_hours == 0) {
                $approved_hours = $overtime_request->hours_requested;
            }

            $overtime_request->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
                'approved_hours' => $approved_hours,
                'total_amount' => $approved_hours * $overtime_request->hourly_rate * $overtime_request->multiplier_rate
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('essentials::lang.overtime_request_approved')
            ]);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => trans('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Reject overtime request
     */
    public function reject(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('essentials.crud_all_overtime') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $overtime_request = EssentialsOvertimeRequest::forBusiness($business_id)->findOrFail($id);

        if (!$overtime_request->canBeRejected()) {
            return response()->json(['success' => false, 'msg' => 'This overtime request cannot be rejected.']);
        }

        try {
            $overtime_request->update([
                'status' => 'rejected',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
                'rejection_reason' => $request->input('rejection_reason')
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('essentials::lang.overtime_request_rejected')
            ]);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => trans('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Get overtime request details
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $can_approve_overtime = auth()->user()->can('essentials.approve_overtime');

        $query = EssentialsOvertimeRequest::with(['user', 'approver'])->forBusiness($business_id);
        
        if (!$can_approve_overtime) {
            $query->where('user_id', auth()->user()->id);
        }
        
        $overtime_request = $query->findOrFail($id);

        // Calculate total amount if not already calculated
        $total_amount = null;
        if ($overtime_request->status === 'approved' && $overtime_request->approved_hours && $overtime_request->hourly_rate && $overtime_request->multiplier_rate) {
            $total_amount = $overtime_request->approved_hours * $overtime_request->hourly_rate * $overtime_request->multiplier_rate;
        } elseif ($overtime_request->hourly_rate && $overtime_request->multiplier_rate) {
            // Calculate estimated amount for pending requests
            $total_amount = $overtime_request->hours_requested * $overtime_request->hourly_rate * $overtime_request->multiplier_rate;
        }

        return response()->json([
            'overtime' => [
                'id' => $overtime_request->id,
                'overtime_date' => $overtime_request->overtime_date,
                'start_time' => $overtime_request->start_time,
                'end_time' => $overtime_request->end_time,
                'hours_requested' => $overtime_request->hours_requested,
                'approved_hours' => $overtime_request->approved_hours,
                'overtime_type' => $overtime_request->getOvertimeTypeLabel(),
                'reason' => $overtime_request->reason,
                'description' => $overtime_request->description,
                'status' => $overtime_request->status,
                'total_amount' => $total_amount,
                'multiplier_rate' => $overtime_request->multiplier_rate,
                'hourly_rate' => $overtime_request->hourly_rate,
                'approved_at' => $overtime_request->approved_at,
                'rejection_reason' => $overtime_request->rejection_reason,
                'user' => [
                    'id' => $overtime_request->user->id,
                    'user_full_name' => $overtime_request->user->user_full_name,
                    'first_name' => $overtime_request->user->first_name,
                    'last_name' => $overtime_request->user->last_name
                ],
                'approver' => $overtime_request->approver ? [
                    'id' => $overtime_request->approver->id,
                    'user_full_name' => $overtime_request->approver->user_full_name
                ] : null
            ]
        ]);
    }

    /**
     * Run overtime detection for a specific date
     */
    public function runDetection(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('essentials.crud_all_overtime') && !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'detection_date' => 'required|date'
        ]);

        try {
            $overtime_requests = $this->essentialsUtil->processOvertimeDetectionForDate(
                $request->detection_date, 
                $business_id
            );

            $message = count($overtime_requests) > 0 ? 
                __('essentials::lang.overtime_detection_completed', ['count' => count($overtime_requests)]) :
                __('essentials::lang.no_overtime_detected');

            return response()->json([
                'success' => true,
                'msg' => $message,
                'detected_count' => count($overtime_requests)
            ]);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => trans('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Show the form for editing overtime request
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $overtime_request = EssentialsOvertimeRequest::with(['user'])->forBusiness($business_id)->findOrFail($id);
        
        $can_manage_all_overtime = auth()->user()->can('essentials.crud_all_overtime') || auth()->user()->can('superadmin');
        
        // Check permissions: Admin can edit any request, Employee can only edit own draft/pending requests
        if (!$can_manage_all_overtime) {
            $is_own_request = $overtime_request->user_id == auth()->user()->id;
            $is_editable = in_array($overtime_request->status, ['draft', 'pending']);
            
            if (!$is_own_request || !$is_editable) {
                abort(403, 'You can only edit your own draft and pending overtime requests.');
            }
        }

        $overtime_settings = $this->essentialsUtil->getOvertimeSettings($business_id);

        return view('essentials::overtime.edit', compact('overtime_request', 'overtime_settings', 'can_manage_all_overtime'));
    }

    /**
     * Update overtime request
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $overtime_request = EssentialsOvertimeRequest::forBusiness($business_id)->findOrFail($id);
        
        $can_manage_all_overtime = auth()->user()->can('essentials.crud_all_overtime') || auth()->user()->can('superadmin');
        
        // Check permissions: Admin can update any request, Employee can only update own draft/pending requests
        if (!$can_manage_all_overtime) {
            $is_own_request = $overtime_request->user_id == auth()->user()->id;
            $is_editable = in_array($overtime_request->status, ['draft', 'pending']);
            
            if (!$is_own_request || !$is_editable) {
                return response()->json([
                    'success' => false,
                    'msg' => 'You can only edit your own draft and pending overtime requests.'
                ]);
            }
            
        }

        try {
            // Validation rules vary based on user permissions
            $status_options = $can_manage_all_overtime 
                ? 'required|in:draft,pending,approved,rejected' 
                : 'required|in:draft,pending';
                
            $request->validate([
                'overtime_date' => 'required|date',
                'start_time' => 'required',
                'end_time' => 'required',
                'status' => $status_options,
                'reason' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            // Non-admins cannot set status to approved (check after validation)
            if (!$can_manage_all_overtime && $request->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'msg' => 'You cannot approve your own overtime requests. Only admin can approve requests.'
                ]);
            }

            // Parse approved_hours from HH:MM format (admin only)
            $approved_hours = null;
            if ($can_manage_all_overtime && ($request->filled('approved_hours_hour') || $request->filled('approved_hours_minute'))) {
                $hours = (int)$request->input('approved_hours_hour', 0);
                $minutes = (int)$request->input('approved_hours_minute', 0);
                $approved_hours = $hours + ($minutes / 60);
            }

            // Convert date format
            $date_formatted = \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->format('Y-m-d');
            
            // Calculate hours
            $start = \Carbon::parse($date_formatted . ' ' . $request->start_time);
            $end = \Carbon::parse($date_formatted . ' ' . $request->end_time);
            if ($end->lt($start)) {
                $end->addDay();
            }
            $total_minutes = $start->diffInMinutes($end);
            $hours = $total_minutes / 60;

            // Determine overtime type
            $overtime_type = $this->essentialsUtil->getOvertimeType($date_formatted, $overtime_request->user, $business_id);
            
            // Calculate rates
            $hourly_rate = $this->essentialsUtil->calculateHourlyRate(
                $overtime_request->user_id, 
                $overtime_request->user->essentials_salary, 
                \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->month,
                \Carbon::createFromFormat('d/m/Y', $request->overtime_date)->year,
                $business_id
            );
            
            $overtime_settings = $this->essentialsUtil->getOvertimeSettings($business_id);
            $multiplier_map = [
                'workday' => $overtime_settings['workday_multiplier'],
                'weekend' => $overtime_settings['weekend_multiplier'],
                'holiday' => $overtime_settings['holiday_multiplier']
            ];
            $multiplier = $multiplier_map[$overtime_type] ?? 1.5;

            // Prepare update data
            $update_data = [
                'overtime_date' => $date_formatted,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_requested' => round($hours, 2),
                'overtime_type' => $overtime_type,
                'reason' => $request->reason,
                'description' => $request->description,
                'multiplier_rate' => $multiplier,
                'hourly_rate' => $hourly_rate,
                'total_amount' => $hours * $hourly_rate * $multiplier,
            ];

            // Admin-only fields
            if ($can_manage_all_overtime) {
                $update_data['status'] = $request->input('status', $overtime_request->status);
                $update_data['approved_hours'] = $approved_hours ? round($approved_hours, 2) : null;
                $update_data['total_amount'] = $approved_hours ? ($approved_hours * $hourly_rate * $multiplier) : ($hours * $hourly_rate * $multiplier);
                $update_data['approved_by'] = ($request->status === 'approved' || $request->status === 'rejected') ? auth()->user()->id : $overtime_request->approved_by;
                $update_data['approved_at'] = ($request->status === 'approved' || $request->status === 'rejected') ? now() : $overtime_request->approved_at;
                $update_data['rejection_reason'] = $request->status === 'rejected' ? $request->input('rejection_reason') : null;
            } else {
                // Non-admin users: always set status to pending when updating
                $update_data['status'] = 'pending';
            }

            // Update overtime request
            $overtime_request->update($update_data);

            return response()->json([
                'success' => true,
                'msg' => __('essentials::lang.overtime_request_updated')
            ]);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => trans('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Delete overtime request
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $overtime_request = EssentialsOvertimeRequest::forBusiness($business_id)->findOrFail($id);
        
        // Check permissions
        $can_manage_all_overtime = auth()->user()->can('essentials.crud_all_overtime') || auth()->user()->can('superadmin');
        $is_own_request = $overtime_request->user_id == auth()->user()->id;
        $is_deletable = in_array($overtime_request->status, ['draft', 'pending']);
        
        // Admin can delete any request at any state
        // Employee can only delete their own draft/pending requests
        if (!$can_manage_all_overtime && (!$is_own_request || !$is_deletable)) {
            return response()->json([
                'success' => false,
                'msg' => 'You can only delete your own draft and pending overtime requests.'
            ]);
        }

        try {
            // Check if overtime has been processed in payroll
            if ($overtime_request->status === 'approved' && $overtime_request->payroll_processed) {
                return response()->json([
                    'success' => false,
                    'msg' => __('essentials::lang.cannot_delete_processed_overtime')
                ]);
            }

            $overtime_request->delete();

            return response()->json([
                'success' => true,
                'msg' => __('essentials::lang.overtime_request_deleted')
            ]);

        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => trans('messages.something_went_wrong')
            ]);
        }
    }
}