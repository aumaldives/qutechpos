<?php

namespace Modules\Essentials\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Essentials\Entities\EssentialsLeaveType;
use Yajra\DataTables\Facades\DataTables;

class EssentialsLeaveTypeController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $leave_types = EssentialsLeaveType::where('business_id', $business_id)
                        ->select(['leave_type', 'max_leave_count', 'leave_count_interval', 'is_paid', 'id']);

            return Datatables::of($leave_types)
                ->addColumn('action', function ($row) {
                    $edit_url = action('\Modules\Essentials\Http\Controllers\EssentialsLeaveTypeController@edit', [$row->id]);
                    return '<button data-href="' . $edit_url . '" class="btn btn-xs btn-primary btn-modal" data-container=".view_modal"><i class="fa fa-edit"></i> Edit</button>';
                })
                ->editColumn('is_paid', function ($row) {
                    return $row->is_paid ? __('essentials::lang.paid_leave') : __('essentials::lang.unpaid_leave');
                })
                ->editColumn('leave_count_interval', function ($row) {
                    switch ($row->leave_count_interval) {
                        case 'join_date_anniversary':
                            return __('essentials::lang.join_date_anniversary');
                        case 'month':
                            return __('essentials::lang.current_month');
                        case 'year':
                            return __('essentials::lang.current_fy');
                        default:
                            return __('lang_v1.none');
                    }
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->escapeColumns([])
                ->make(false);
        }

        return view('essentials::leave_type.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, 'Unauthorized action.');
        }

        return view('essentials::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['leave_type', 'max_leave_count', 'leave_count_interval', 'is_paid']);

            $input['business_id'] = $business_id;
            $input['is_paid'] = !empty($request->input('is_paid')) ? 1 : 0;

            EssentialsLeaveType::create($input);
            $output = ['success' => true,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('essentials::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, 'Unauthorized action.');
        }

        $leave_type = EssentialsLeaveType::where('business_id', $business_id)
                                        ->find($id);

        return view('essentials::leave_type.edit')->with(compact('leave_type'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');
        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('essentials.crud_leave_type')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['leave_type', 'max_leave_count',
                'leave_count_interval', 'is_paid']);

            $input['business_id'] = $business_id;
            $input['is_paid'] = !empty($request->input('is_paid')) ? 1 : 0;

            EssentialsLeaveType::where('business_id', $business_id)
                            ->where('id', $id)
                            ->update($input);

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy()
    {
    }
}
