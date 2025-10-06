<div class="pos-tab-content active">
	<div class="row">
		<div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('leave_ref_no_prefix',  __('essentials::lang.leave_ref_no_prefix') . ':') !!}
            	{!! Form::text('leave_ref_no_prefix', !empty($settings['leave_ref_no_prefix']) ? $settings['leave_ref_no_prefix'] : null, ['class' => 'form-control','placeholder' => __('essentials::lang.leave_ref_no_prefix')]); !!}
            </div>
        </div>
        <div class="col-xs-6">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('allow_exceeding_leave_limits', 1, !empty($settings['allow_exceeding_leave_limits']), ['class' => 'input-icheck']); !!}
                        @lang('essentials::lang.allow_exceeding_leave_limits')
                    </label>
                    @show_tooltip(__('essentials::lang.allow_exceeding_leave_limits_help'))
                </div>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="form-group">
                {!! Form::label('leave_instructions',  __('essentials::lang.leave_instructions') . ':') !!}
                {!! Form::textarea('leave_instructions', !empty($settings['leave_instructions']) ? $settings['leave_instructions'] : null, ['class' => 'form-control','placeholder' => __('essentials::lang.leave_instructions')]); !!}
            </div>
        </div>
	</div>
</div>