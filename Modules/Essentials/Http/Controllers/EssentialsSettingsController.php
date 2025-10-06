<?php

namespace Modules\Essentials\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class EssentialsSettingsController extends Controller
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
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')) && ! $is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        if ($is_admin) {
            return view('essentials::settings.add')->with(compact('settings'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module')) && ! $is_admin) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['leave_ref_no_prefix', 'leave_instructions', 'payroll_ref_no_prefix', 'essentials_todos_prefix', 'grace_before_checkin', 'grace_after_checkin', 'grace_before_checkout', 'grace_after_checkout', 'hourly_rate_calculation_method', 'overtime_daily_threshold', 'overtime_workday_multiplier', 'overtime_weekend_multiplier', 'overtime_holiday_multiplier', 'overtime_minimum_minutes', 'absent_day_penalty_multiplier', 'late_penalty_multiplier']);
            $input['is_location_required'] = ! empty($request->input('is_location_required')) ? 1 : 0;
            $input['calculate_sales_target_commission_without_tax'] = ! empty($request->input('calculate_sales_target_commission_without_tax')) ? 1 : 0;
            $input['include_paid_leaves_in_hourly_rate'] = ! empty($request->input('include_paid_leaves_in_hourly_rate')) ? 1 : 0;
            $input['include_unpaid_leaves_in_hourly_rate'] = ! empty($request->input('include_unpaid_leaves_in_hourly_rate')) ? 1 : 0;
            $input['auto_calculate_hourly_in_payroll'] = ! empty($request->input('auto_calculate_hourly_in_payroll')) ? 1 : 0;
            $input['overtime_approval_required'] = ! empty($request->input('overtime_approval_required')) ? 1 : 0;
            $input['overtime_auto_detect'] = ! empty($request->input('overtime_auto_detect')) ? 1 : 0;
            $input['enable_absent_day_deductions'] = ! empty($request->input('enable_absent_day_deductions')) ? 1 : 0;
            $input['enable_late_penalty_deductions'] = ! empty($request->input('enable_late_penalty_deductions')) ? 1 : 0;

            $business = Business::find($business_id);
            $business->essentials_settings = json_encode($input);
            $business->save();

            $request->session()->put('business', $business);

            $output = ['success' => 1,
                'msg' => trans('lang_v1.updated_succesfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }
}
