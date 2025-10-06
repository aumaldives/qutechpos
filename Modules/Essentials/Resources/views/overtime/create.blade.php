@extends('layouts.app')
@section('title', __('essentials::lang.add_overtime_request'))

@section('content')
@include('essentials::layouts.nav_hrm')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('essentials::lang.add_overtime_request')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('essentials::lang.overtime_request_details')</h3>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(session('status'))
                    @if(session('status.success'))
                        <div class="alert alert-success">
                            {{ session('status.msg') }}
                        </div>
                    @else
                        <div class="alert alert-danger">
                            {{ session('status.msg') }}
                        </div>
                    @endif
                @endif

                {!! Form::open(['url' => '/hrm/overtime', 'method' => 'POST', 'id' => 'overtime_request_form']) !!}
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('overtime_date', __('contact.date') . ':*') !!}
                                {!! Form::text('overtime_date', '', ['class' => 'form-control datepicker', 'required', 'placeholder' => __('contact.date')]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('start_time', __('essentials::lang.start_time') . ':*') !!}
                                {!! Form::time('start_time', '', ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('end_time', __('essentials::lang.end_time') . ':*') !!}
                                {!! Form::time('end_time', '', ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('reason', __('essentials::lang.reason') . ':*') !!}
                                {!! Form::select('reason', [
                                    'urgent_deadline' => __('essentials::lang.urgent_deadline'),
                                    'staff_shortage' => __('essentials::lang.staff_shortage'),
                                    'project_completion' => __('essentials::lang.project_completion'),
                                    'system_maintenance' => __('essentials::lang.system_maintenance'),
                                    'client_request' => __('essentials::lang.client_request'),
                                    'other' => __('essentials::lang.other')
                                ], '', ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="well well-sm" id="overtime_info">
                                    <strong>@lang('essentials::lang.overtime_calculation_info'):</strong>
                                    <ul class="list-unstyled" style="margin-top: 10px;">
                                        <li><i class="fa fa-clock-o text-primary"></i> @lang('essentials::lang.workday_multiplier'): {{$overtime_settings['workday_multiplier']}}x</li>
                                        <li><i class="fa fa-calendar text-info"></i> @lang('essentials::lang.weekend_multiplier'): {{$overtime_settings['weekend_multiplier']}}x</li>
                                        <li><i class="fa fa-star text-warning"></i> @lang('essentials::lang.holiday_multiplier'): {{$overtime_settings['holiday_multiplier']}}x</li>
                                    </ul>
                                    @if($overtime_settings['approval_required'])
                                        <div class="alert alert-info alert-sm" style="margin-top: 10px; margin-bottom: 0;">
                                            <i class="fa fa-info-circle"></i> @lang('essentials::lang.overtime_approval_required_info')
                                        </div>
                                    @else
                                        <div class="alert alert-success alert-sm" style="margin-top: 10px; margin-bottom: 0;">
                                            <i class="fa fa-check-circle"></i> @lang('essentials::lang.overtime_auto_approved_info')
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('description', __('essentials::lang.description') . ':') !!}
                                {!! Form::textarea('description', '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('essentials::lang.overtime_description_placeholder')]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row" id="overtime_preview" style="display: none;">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4><i class="fa fa-calculator"></i> @lang('essentials::lang.overtime_preview')</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.total_hours'):</strong>
                                        <span id="preview_hours">0</span> @lang('essentials::lang.hours_short')
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.overtime_type'):</strong>
                                        <span id="preview_type">-</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.hourly_rate'):</strong>
                                        <span id="preview_rate">-</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.estimated_amount'):</strong>
                                        <span id="preview_amount" class="text-success">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> @lang('essentials::lang.submit_overtime_request')
                    </button>
                    <a href="{{action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'index'])}}" 
                       class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> @lang('messages.back')
                    </a>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    $('#overtime_request_form').validate({
        rules: {
            overtime_date: "required",
            start_time: "required",
            end_time: "required",
            reason: "required"
        },
        messages: {
            overtime_date: "@lang('validation.required', ['attribute' => __('contact.date')])",
            start_time: "@lang('validation.required', ['attribute' => __('essentials::lang.start_time')])",
            end_time: "@lang('validation.required', ['attribute' => __('essentials::lang.end_time')])",
            reason: "@lang('validation.required', ['attribute' => __('essentials::lang.reason')])"
        }
    });

    // Calculate overtime preview when form fields change
    function calculateOvertimePreview() {
        var date = $('#overtime_date').val();
        var start_time = $('#start_time').val();
        var end_time = $('#end_time').val();
        
        if (date && start_time && end_time) {
            // Calculate hours
            var start = moment(date + ' ' + start_time, 'DD/MM/YYYY HH:mm');
            var end = moment(date + ' ' + end_time, 'DD/MM/YYYY HH:mm');
            
            if (end.isBefore(start)) {
                end.add(1, 'day'); // Next day
            }
            
            var hours = end.diff(start, 'hours', true);
            
            if (hours > 0) {
                $.ajax({
                    url: '/hrm/overtime/preview',
                    method: 'POST',
                    data: {
                        overtime_date: date,
                        start_time: start_time,
                        end_time: end_time,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(result) {
                        if (result.success) {
                            $('#preview_hours').text(result.hours);
                            $('#preview_type').text(result.overtime_type_label);
                            $('#preview_rate').text(result.hourly_rate_formatted);
                            $('#preview_amount').text(result.estimated_amount_formatted);
                            $('#overtime_preview').show();
                        }
                    },
                    error: function() {
                        $('#overtime_preview').hide();
                    }
                });
            } else {
                $('#overtime_preview').hide();
            }
        } else {
            $('#overtime_preview').hide();
        }
    }

    // Bind calculation to form field changes
    $('#overtime_date, #start_time, #end_time').change(calculateOvertimePreview);

    // Auto-fill current date
    $('#overtime_date').val(moment().format('DD/MM/YYYY'));
    
    // Initialize datepicker
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy'
    });

    // Custom reason handling
    $('#reason').change(function() {
        if ($(this).val() === 'other') {
            var customReason = prompt('@lang("essentials::lang.please_specify_reason")');
            if (customReason) {
                $(this).append('<option value="' + customReason + '" selected>' + customReason + '</option>');
            } else {
                $(this).val('').trigger('change');
            }
        }
    });
});
</script>
@endsection