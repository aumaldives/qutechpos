@extends('layouts.app')

@php
    $action_url = action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'store']);
    $title = __( 'essentials::lang.add_payroll' );
    $subtitle = __( 'essentials::lang.add_payroll' );
    $submit_btn_text = __( 'messages.save' );
    $group_name = __('essentials::lang.payroll_for_month', ['date' => $month_name . ' ' . $year]);
    if ($action == 'edit') {
        $action_url = action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'getUpdatePayrollGroup']);
        $title = __( 'essentials::lang.edit_payroll' );
        $subtitle = __( 'essentials::lang.edit_payroll' );
        $submit_btn_text = __( 'messages.update' );
    }
@endphp

@section('title', $title)

@section('content')
@include('essentials::layouts.nav_hrm')
<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>{{$subtitle}}</h1>
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => $action_url, 'method' => 'post', 'id' => 'add_payroll_form' ]) !!}
    {!! Form::hidden('transaction_date', $transaction_date); !!}
    @if($action == 'edit')
        {!! Form::hidden('payroll_group_id', $payroll_group->id); !!}
    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h3>
                                {!! $group_name !!}
                            </h3>
                            <small>
                                <b>@lang('business.location')</b> :
                                @if(!empty($location))
                                    {{$location->name}}
                                    {!! Form::hidden('location_id', $location->id); !!}
                                @else
                                    {{__('report.all_locations')}}
                                    {!! Form::hidden('location_id', ''); !!}
                                @endif
                            </small>
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('payroll_group_name', __( 'essentials::lang.payroll_group_name' ) . ':*') !!}
                            {!! Form::text('payroll_group_name', !empty($payroll_group) ? $payroll_group->name : strip_tags($group_name), ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.payroll_group_name' ), 'required']); !!}
                        </div>
                        <div class="col-md-4">
                            {!! Form::label('payroll_group_status', __( 'sale.status' ) . ':*') !!}
                            @show_tooltip(__('essentials::lang.group_status_tooltip'))
                            {!! Form::select('payroll_group_status', ['draft' => __('sale.draft'), 'final' => __('sale.final')], !empty($payroll_group) ? $payroll_group->status : null, ['class' => 'form-control select2', 'required', 'style' => 'width: 100%;', 'placeholder' => __( 'messages.please_select' )]); !!}
                            <p class="help-block text-muted">@lang('essentials::lang.payroll_cant_be_deleted_if_final')</p>
                        </div>
                    </div>
                    
                    @if(!empty($payroll_summary))
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-info collapsed-box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-chart-bar"></i> @lang('essentials::lang.payroll_summary')</h3>
                                    <div class="box-tools pull-right">
                                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                                    </div>
                                </div>
                                <div class="box-body" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <div class="info-box bg-green">
                                                <span class="info-box-icon"><i class="fa fa-users"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">@lang('essentials::lang.total_employees')</span>
                                                    <span class="info-box-number">{{$payroll_summary['total_employees']}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <div class="info-box bg-blue">
                                                <span class="info-box-icon"><i class="fa fa-calculator"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">@lang('essentials::lang.shift_aware_calculations')</span>
                                                    <span class="info-box-number">{{$payroll_summary['shift_aware_calculations']}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <div class="info-box bg-yellow">
                                                <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">@lang('essentials::lang.attendance_warnings')</span>
                                                    <span class="info-box-number">{{$payroll_summary['attendance_warnings']}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-xs-12">
                                            <div class="info-box bg-red">
                                                <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">@lang('essentials::lang.overtime_employees')</span>
                                                    <span class="info-box-number">{{$payroll_summary['overtime_employees']}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h4>@lang('essentials::lang.attendance_efficiency_breakdown')</h4>
                                            <ul class="list-unstyled">
                                                <li><span class="text-success"><i class="fa fa-circle"></i></span> @lang('essentials::lang.high_performers'): {{$payroll_summary['efficiency_stats']['high_performers']}} (≥95%)</li>
                                                <li><span class="text-warning"><i class="fa fa-circle"></i></span> @lang('essentials::lang.good_performers'): {{$payroll_summary['efficiency_stats']['good_performers']}} (80-95%)</li>
                                                <li><span class="text-danger"><i class="fa fa-circle"></i></span> @lang('essentials::lang.needs_improvement'): {{$payroll_summary['efficiency_stats']['needs_improvement']}} (<80%)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-8">
                                            @if(!empty($recommendations))
                                                <h4>@lang('essentials::lang.payroll_recommendations')</h4>
                                                @foreach($recommendations as $employee_id => $employee_rec)
                                                    <div class="alert alert-info">
                                                        <strong>{{$employee_rec['employee_name']}}</strong>
                                                        @foreach($employee_rec['recommendations'] as $rec)
                                                            <br><small><i class="fa fa-lightbulb-o"></i> {{$rec['title']}}: {{$rec['description']}}</small>
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <br>
                    <table class="table" id="payroll_table">
                        <tr>
                            <th>
                                @lang('essentials::lang.employee')
                            </th>
                            <th>
                                @lang('essentials::lang.salary')
                            </th>
                            <th>
                                @lang('essentials::lang.allowances')
                            </th>
                            <th>
                                @lang('essentials::lang.deductions')
                            </th>
                            <th>
                                @lang('essentials::lang.gross_amount')
                            </th>
                        </tr>
                        @foreach($payrolls as $employee => $payroll)
                            @php
                                if ($action != 'edit') {
                                    // Use shift-aware calculations if hourly rate is available
                                    if (!empty($payroll['hourly_rate']) && !empty($payroll['total_work_duration'])) {
                                        $amount_per_unit_duration = $payroll['hourly_rate'];
                                        $total_work_duration = $payroll['total_work_duration'];
                                        $duration_unit = __('essentials::lang.hours');
                                        $total = $total_work_duration * $amount_per_unit_duration;
                                    } else {
                                        // Fallback to traditional calculation
                                        $amount_per_unit_duration = (double)$payroll['essentials_salary'];
                                        $total_work_duration = 1;
                                        $duration_unit = __('lang_v1.month');
                                        if ($payroll['essentials_pay_period'] == 'week') {
                                            $total_work_duration = 4;
                                            $duration_unit = __('essentials::lang.week');
                                        } elseif ($payroll['essentials_pay_period'] == 'day') {
                                            $total_work_duration = \Carbon::parse($transaction_date)->daysInMonth;
                                            $duration_unit = __('lang_v1.day');
                                        }
                                        $total = $total_work_duration * $amount_per_unit_duration;
                                    }
                                } else {
                                    $amount_per_unit_duration = $payroll['essentials_amount_per_unit_duration'];
                                    $total_work_duration = $payroll['essentials_duration'];
                                    $duration_unit = $payroll['essentials_duration_unit'];
                                    $total = $total_work_duration * $amount_per_unit_duration;
                                }
                            @endphp
                            <tr data-id="{{$employee}}">
                                <input type="hidden" name="payrolls[{{$employee}}][expense_for]" value="{{$employee}}">
                                @if($action == 'edit')
                                    {!! Form::hidden('payrolls['.$employee.'][transaction_id]', $payroll['transaction_id']); !!}
                                @endif
                                <td>
                                    {{$payroll['name']}}
                                    <br><br>
                                    <b>{{__('essentials::lang.leaves')}} :</b>
                                    {{
                                        __('essentials::lang.total_leaves_days', ['total_leaves' => $payroll['total_leaves']])
                                    }}
                                    <br><br>
                                    <b>{{__('essentials::lang.work_duration')}} :</b> 
                                    {{
                                        __('essentials::lang.work_duration_hour', ['duration' => $payroll['total_work_duration']])
                                    }}
                                    @if(!empty($payroll['scheduled_working_hours']))
                                        <br><small class="text-muted">@lang('essentials::lang.scheduled_hours'): {{$payroll['scheduled_working_hours']}} hrs</small>
                                    @endif
                                    <br><br>
                                    <b>
                                        {{__('essentials::lang.attendance')}}:
                                    </b>
                                    {{$payroll['total_days_worked']}} @lang('lang_v1.days')
                                    @if(!empty($payroll['scheduled_working_days']))
                                        <br><small class="text-muted">@lang('essentials::lang.scheduled_days'): {{$payroll['scheduled_working_days']}} days</small>
                                    @endif
                                    @if(!empty($payroll['hourly_rate']))
                                        <br><br>
                                        <b>{{__('essentials::lang.hourly_rate')}}:</b>
                                        <span class="text-success">@format_currency($payroll['hourly_rate'])</span>
                                        <br><small class="text-muted">@lang('essentials::lang.attendance_vs_scheduled'): {{$payroll['attendance_vs_scheduled']}}</small>
                                        
                                        @if(!empty($payroll['attendance_efficiency']))
                                            <br><b>@lang('essentials::lang.attendance_efficiency'):</b>
                                            @if($payroll['attendance_efficiency'] >= 95)
                                                <span class="text-success">{{$payroll['attendance_efficiency']}}%</span>
                                            @elseif($payroll['attendance_efficiency'] >= 80)
                                                <span class="text-warning">{{$payroll['attendance_efficiency']}}%</span>
                                            @else
                                                <span class="text-danger">{{$payroll['attendance_efficiency']}}%</span>
                                            @endif
                                        @endif
                                        
                                        @if(!empty($payroll['has_attendance_warning']))
                                            <br><div class="alert alert-warning alert-sm" style="margin-top: 5px; padding: 5px;">
                                                <small><i class="fa fa-warning"></i> @lang('essentials::lang.payroll_validation_warning')</small>
                                                @if(!empty($payroll['variance_hours']))
                                                    <br><small>
                                                        @if($payroll['attendance_status'] == 'overtime')
                                                            <span class="text-info"><i class="fa fa-clock-o"></i> +{{number_format($payroll['variance_hours'], 1)}} hrs ({{$payroll['variance_percentage']}}% overtime)</span>
                                                        @elseif($payroll['attendance_status'] == 'undertime')
                                                            <span class="text-danger"><i class="fa fa-minus-circle"></i> {{number_format($payroll['variance_hours'], 1)}} hrs ({{$payroll['variance_percentage']}}% undertime)</span>
                                                        @endif
                                                    </small>
                                                @endif
                                            </div>
                                        @endif
                                    @endif
                                    
                                    @if(!empty($payroll['overtime_details']))
                                        <br><br>
                                        <b>{{__('essentials::lang.overtime_summary')}}:</b>
                                        <div class="well well-sm" style="margin-top: 5px;">
                                            <small>
                                                <i class="fa fa-clock-o text-info"></i> {{$payroll['overtime_details']['total_hours']}} @lang('essentials::lang.hours_short') 
                                                (<span class="text-success">@format_currency($payroll['overtime_details']['total_amount'])</span>)
                                                <br>
                                                @foreach($payroll['overtime_details']['breakdown'] as $breakdown)
                                                    <span class="label label-{{$breakdown['type'] == 'holiday' ? 'warning' : ($breakdown['type'] == 'weekend' ? 'info' : 'default')}}">
                                                        {{date('M j', strtotime($breakdown['date']))}}: {{$breakdown['hours']}}h × {{$breakdown['multiplier']}}
                                                    </span> 
                                                @endforeach
                                            </small>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($payroll['absent_day_details']) && $payroll['absent_day_details']['total_deduction'] > 0)
                                        <br><br>
                                        <b>{{__('essentials::lang.absent_day_deduction')}}:</b>
                                        <div class="well well-sm bg-danger" style="margin-top: 5px;">
                                            <small>
                                                <i class="fa fa-minus-circle text-danger"></i> 
                                                <span class="text-danger">@format_currency($payroll['absent_day_details']['total_deduction'])</span>
                                                <br>
                                                <strong>@lang('essentials::lang.absent_days'):</strong> {{$payroll['absent_day_details']['absent_days']}} days
                                                <br>
                                                <strong>@lang('essentials::lang.calculation'):</strong> {{$payroll['absent_day_details']['absent_days']}} days × {{$payroll['absent_day_details']['shift_hours_per_day']}} hrs × @format_currency($payroll['absent_day_details']['hourly_rate']) × {{$payroll['absent_day_details']['penalty_multiplier']}}
                                                @if($payroll['absent_day_details']['paid_leaves_excluded'] > 0)
                                                    <br><small class="text-muted"><i class="fa fa-info-circle"></i> @lang('essentials::lang.paid_leaves_excluded'): {{$payroll['absent_day_details']['paid_leaves_excluded']}} days</small>
                                                @endif
                                            </small>
                                        </div>
                                    @endif
                                    
                                    @if(!empty($payroll['late_penalty_details']) && $payroll['late_penalty_details']['total_deduction'] > 0)
                                        <br><br>
                                        <b>{{__('essentials::lang.late_penalty_deduction')}}:</b>
                                        <div class="well well-sm bg-warning" style="margin-top: 5px;">
                                            <small>
                                                <i class="fa fa-clock-o text-warning"></i> 
                                                <span class="text-danger">@format_currency($payroll['late_penalty_details']['total_deduction'])</span>
                                                <br>
                                                <strong>@lang('essentials::lang.late_minutes'):</strong> {{$payroll['late_penalty_details']['total_late_minutes']}} minutes ({{number_format($payroll['late_penalty_details']['late_hours'], 2)}} hrs)
                                                <br>
                                                <strong>@lang('essentials::lang.calculation'):</strong> {{number_format($payroll['late_penalty_details']['late_hours'], 2)}} hrs × @format_currency($payroll['late_penalty_details']['hourly_rate']) × {{$payroll['late_penalty_details']['penalty_multiplier']}}
                                                <br><small class="text-muted"><i class="fa fa-info-circle"></i> @lang('essentials::lang.grace_period'): {{$payroll['late_penalty_details']['grace_period_minutes']}} minutes</small>
                                            </small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {!! Form::label('essentials_duration_'.$employee, __( 'essentials::lang.total_work_duration' ) . ':*') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_duration]', $total_work_duration, ['class' => 'form-control input_number essentials_duration', 'placeholder' => __( 'essentials::lang.total_work_duration' ), 'required', 'data-id' => $employee, 'id' => 'essentials_duration_'.$employee]); !!}
                                    <br>

                                    {!! Form::label('essentials_duration_unit_'.$employee, __( 'essentials::lang.duration_unit' ) . ':') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_duration_unit]', $duration_unit, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.duration_unit' ), 'data-id' => $employee, 'id' => 'essentials_duration_unit_'.$employee]); !!}

                                    <br>
                                    
                                    {!! Form::label('essentials_amount_per_unit_duration_'.$employee, __( 'essentials::lang.amount_per_unit_duartion' ) . ':*') !!}
                                    {!! Form::text('payrolls['.$employee.'][essentials_amount_per_unit_duration]', $amount_per_unit_duration, ['class' => 'form-control input_number essentials_amount_per_unit_duration', 'placeholder' => __( 'essentials::lang.amount_per_unit_duartion' ), 'required', 'data-id' => $employee, 'id' => 'essentials_amount_per_unit_duration_'.$employee]); !!}
                                        
                                    <br>
                                    {!! Form::label('total_'.$employee, __( 'sale.total' ) . ':') !!}
                                    {!! Form::text('payrolls['.$employee.'][total]', $total, ['class' => 'form-control input_number total', 'placeholder' => __( 'sale.total' ), 'data-id' => $employee, 'id' => 'total_'.$employee]); !!}
                                </td>
                                <td>
                                    @component('components.widget')
                                        <table class="table table-condenced allowance_table" id="allowance_table_{{$employee}}" data-id="{{$employee}}">
                                            <thead>
                                                <tr>
                                                    <th class="col-md-5">@lang('essentials::lang.description')</th>
                                                    <th class="col-md-3">@lang('essentials::lang.amount_type')</th>
                                                    <th class="col-md-3">@lang('sale.amount')</th>
                                                    <th class="col-md-1">&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total_allowances = 0;
                                                @endphp
                                                @if(!empty($payroll['allowances']))
                                                    @foreach($payroll['allowances']['allowance_names'] as $key => $value)
                                                        @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => $loop->index == 0 ? true : false, 'type' => 'allowance', 'name' => $value, 'value' => $payroll['allowances']['allowance_amounts'][$key], 'amount_type' => $payroll['allowances']['allowance_types'][$key],
                                                        'percent' => $payroll['allowances']['allowance_percents'][$key] ])

                                                        @php
                                                            $total_allowances += $payroll['allowances']['allowance_amounts'][$key];
                                                        @endphp
                                                    @endforeach
                                                @else
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'allowance'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'allowance'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'allowance'])
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2">@lang('sale.total')</th>
                                                    <td><span id="total_allowances_{{$employee}}" class="display_currency" data-currency_symbol="true">{{$total_allowances}}</span></td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endcomponent
                                </td>
                                <td>
                                    @component('components.widget')
                                        <table class="table table-condenced deductions_table" id="deductions_table_{{$employee}}" data-id="{{$employee}}">
                                            <thead>
                                                <tr>
                                                    <th class="col-md-5">@lang('essentials::lang.description')</th>
                                                    <th class="col-md-3">@lang('essentials::lang.amount_type')</th>
                                                    <th class="col-md-3">@lang('sale.amount')</th>
                                                    <th class="col-md-1">&nbsp;</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $total_deductions = 0;
                                                @endphp
                                                @if(!empty($payroll['deductions']))
                                                    @foreach($payroll['deductions']['deduction_names'] as $key => $value)
                                                        @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => $loop->index == 0 ? true : false, 'type' => 'deduction', 'name' => $value, 'value' => $payroll['deductions']['deduction_amounts'][$key], 
                                                        'amount_type' => $payroll['deductions']['deduction_types'][$key], 'percent' => $payroll['deductions']['deduction_percents'][$key]])

                                                        @php
                                                            $total_deductions += $payroll['deductions']['deduction_amounts'][$key];
                                                        @endphp
                                                    @endforeach
                                                @else
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['add_button' => true, 'type' => 'deduction'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'deduction'])
                                                    @include('essentials::payroll.allowance_and_deduction_row', ['type' => 'deduction'])
                                                @endif
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="2">@lang('sale.total')</th>
                                                    <td><span id="total_deductions_{{$employee}}" class="display_currency" data-currency_symbol="true">{{$total_deductions}}</span></td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endcomponent
                                </td>
                                <td>
                                    <strong>
                                        <span id="gross_amount_text_{{$employee}}">0</span>
                                    </strong>
                                    <br>
                                    {!! Form::hidden('payrolls['.$employee.'][final_total]', 0, ['id' => 'gross_amount_'.$employee, 'class' => 'gross_amount']); !!}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5">
                                    <div class="form-group">
                                        {!! Form::label('note_'.$employee, __( 'brand.note' ) . ':') !!}
                                        {!! Form::textarea('payrolls['.$employee.'][staff_note]', $payroll['staff_note'] ?? null, ['class' => 'form-control', 'placeholder' => __( 'sale.total' ), 'id' => 'note_'.$employee, 'rows' => 3]); !!}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            {!! Form::hidden('total_gross_amount', 0, ['id' => 'total_gross_amount']); !!}
            <button type="submit" class="btn btn-primary pull-right m-8" id="submit_user_button">
                {{$submit_btn_text}}
            </button>
            <div class="form-group pull-right m-8 mt-15">
                <label>
                    {!! Form::checkbox('notify_employee', 1, 0 , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'essentials::lang.notify_employee' ) }}
                </label>
            </div>
        </div>
    </div>
{!! Form::close() !!}
@stop
@section('javascript')
@includeIf('essentials::payroll.form_script')
@endsection
