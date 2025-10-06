<div class="pos-tab-content">
	<div class="row">
		<div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('payroll_ref_no_prefix',  __('essentials::lang.payroll_ref_no_prefix') . ':') !!}
                {!! Form::text('payroll_ref_no_prefix', !empty($settings['payroll_ref_no_prefix']) ? $settings['payroll_ref_no_prefix'] : null, ['class' => 'form-control','placeholder' => __('essentials::lang.payroll_ref_no_prefix')]); !!}
            </div>
        </div>
	</div>
	
	<div class="row">
		<div class="col-xs-12">
			<h4>@lang('essentials::lang.hourly_rate_calculation_settings')</h4>
		</div>
	</div>
	
	<div class="row">
		<div class="col-xs-6">
            <div class="form-group">
                {!! Form::label('hourly_rate_calculation_method', __('essentials::lang.hourly_rate_calculation_method') . ':') !!}
                {!! Form::select('hourly_rate_calculation_method', [
                    'exclude_shift_weekends_and_holidays' => __('essentials::lang.exclude_shift_weekends_and_holidays'),
                    'exclude_weekends_only' => __('essentials::lang.exclude_weekends_only'),
                    'exclude_holidays_only' => __('essentials::lang.exclude_holidays_only'),
                    'include_all_days' => __('essentials::lang.include_all_days')
                ], !empty($settings['hourly_rate_calculation_method']) ? $settings['hourly_rate_calculation_method'] : 'exclude_shift_weekends_and_holidays', ['class' => 'form-control select2']); !!}
                <small class="text-muted">@lang('essentials::lang.hourly_rate_calculation_method_help')</small>
            </div>
        </div>
        
	</div>
	
	<div class="row">
		<div class="col-xs-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('include_paid_leaves_in_hourly_rate', 1, !empty($settings['include_paid_leaves_in_hourly_rate']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.include_paid_leaves_in_hourly_rate')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.include_paid_leaves_in_hourly_rate_help')</small>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('include_unpaid_leaves_in_hourly_rate', 1, !empty($settings['include_unpaid_leaves_in_hourly_rate']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.include_unpaid_leaves_in_hourly_rate')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.include_unpaid_leaves_in_hourly_rate_help')</small>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('auto_calculate_hourly_in_payroll', 1, !empty($settings['auto_calculate_hourly_in_payroll']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.auto_calculate_hourly_in_payroll')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.auto_calculate_hourly_in_payroll_help')</small>
            </div>
        </div>
	</div>
	
	<div class="row">
		<div class="col-xs-12">
			<h4>@lang('essentials::lang.attendance_penalty_settings')</h4>
		</div>
	</div>
	
	<div class="row">
		<div class="col-xs-3">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('enable_absent_day_deductions', 1, !empty($settings['enable_absent_day_deductions']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.enable_absent_day_deductions')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.enable_absent_day_deductions_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('absent_day_penalty_multiplier', __('essentials::lang.absent_day_penalty_multiplier') . ':') !!}
                {!! Form::number('absent_day_penalty_multiplier', !empty($settings['absent_day_penalty_multiplier']) ? $settings['absent_day_penalty_multiplier'] : 1.0, ['class' => 'form-control', 'step' => '0.1', 'min' => '0.5', 'max' => '3.0']) !!}
                <small class="text-muted">@lang('essentials::lang.absent_day_penalty_multiplier_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('enable_late_penalty_deductions', 1, !empty($settings['enable_late_penalty_deductions']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.enable_late_penalty_deductions')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.enable_late_penalty_deductions_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('late_penalty_multiplier', __('essentials::lang.late_penalty_multiplier') . ':') !!}
                {!! Form::number('late_penalty_multiplier', !empty($settings['late_penalty_multiplier']) ? $settings['late_penalty_multiplier'] : 1.0, ['class' => 'form-control', 'step' => '0.1', 'min' => '0.5', 'max' => '3.0']) !!}
                <small class="text-muted">@lang('essentials::lang.late_penalty_multiplier_help')</small>
            </div>
        </div>
	</div>
	
	<div class="row">
		<div class="col-xs-12">
			<h4>@lang('essentials::lang.overtime_settings')</h4>
		</div>
	</div>
	
	<div class="row">
		<div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('overtime_daily_threshold', __('essentials::lang.overtime_daily_threshold') . ':') !!}
                {!! Form::number('overtime_daily_threshold', !empty($settings['overtime_daily_threshold']) ? $settings['overtime_daily_threshold'] : 8, ['class' => 'form-control', 'step' => '0.5', 'min' => '4', 'max' => '12']) !!}
                <small class="text-muted">@lang('essentials::lang.overtime_daily_threshold_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('overtime_workday_multiplier', __('essentials::lang.overtime_workday_multiplier') . ':') !!}
                {!! Form::number('overtime_workday_multiplier', !empty($settings['overtime_workday_multiplier']) ? $settings['overtime_workday_multiplier'] : 1.5, ['class' => 'form-control', 'step' => '0.1', 'min' => '1', 'max' => '5']) !!}
                <small class="text-muted">@lang('essentials::lang.overtime_workday_multiplier_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('overtime_weekend_multiplier', __('essentials::lang.overtime_weekend_multiplier') . ':') !!}
                {!! Form::number('overtime_weekend_multiplier', !empty($settings['overtime_weekend_multiplier']) ? $settings['overtime_weekend_multiplier'] : 2.0, ['class' => 'form-control', 'step' => '0.1', 'min' => '1', 'max' => '5']) !!}
                <small class="text-muted">@lang('essentials::lang.overtime_weekend_multiplier_help')</small>
            </div>
        </div>
        
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('overtime_holiday_multiplier', __('essentials::lang.overtime_holiday_multiplier') . ':') !!}
                {!! Form::number('overtime_holiday_multiplier', !empty($settings['overtime_holiday_multiplier']) ? $settings['overtime_holiday_multiplier'] : 2.5, ['class' => 'form-control', 'step' => '0.1', 'min' => '1', 'max' => '5']) !!}
                <small class="text-muted">@lang('essentials::lang.overtime_holiday_multiplier_help')</small>
            </div>
        </div>
	</div>
	
	<div class="row">
		<div class="col-xs-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('overtime_approval_required', 1, !empty($settings['overtime_approval_required']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.overtime_approval_required')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.overtime_approval_required_help')</small>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('overtime_auto_detect', 1, !empty($settings['overtime_auto_detect']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.overtime_auto_detect')
                    </label>
                </div>
                <small class="text-muted">@lang('essentials::lang.overtime_auto_detect_help')</small>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('overtime_minimum_minutes', __('essentials::lang.overtime_minimum_minutes') . ':') !!}
                <div class="input-group">
                    {!! Form::number('overtime_minimum_minutes', !empty($settings['overtime_minimum_minutes']) ? $settings['overtime_minimum_minutes'] : 15, ['class' => 'form-control', 'min' => 1, 'max' => 480, 'step' => 1]) !!}
                    <span class="input-group-addon">@lang('essentials::lang.minutes')</span>
                </div>
                <small class="text-muted">@lang('essentials::lang.overtime_minimum_minutes_help')</small>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('overtime_maximum_hours', __('essentials::lang.overtime_maximum_hours') . ':') !!}
                <div class="input-group">
                    {!! Form::number('overtime_maximum_hours', !empty($settings['overtime_maximum_hours']) ? $settings['overtime_maximum_hours'] : 24, ['class' => 'form-control', 'min' => 1, 'max' => 24, 'step' => 1]) !!}
                    <span class="input-group-addon">@lang('essentials::lang.hours')</span>
                </div>
                <small class="text-muted">@lang('essentials::lang.overtime_maximum_hours_help')</small>
            </div>
        </div>
	</div>
</div>