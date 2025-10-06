@extends('layouts.app')
@section('title', __('essentials::lang.edit_overtime_request'))

@section('content')
@include('essentials::layouts.nav_hrm')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('essentials::lang.edit_overtime_request')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('essentials::lang.overtime_request_details')</h3>
                    <div class="box-tools pull-right">
                        <span class="label label-{{$overtime_request->status == 'approved' ? 'success' : ($overtime_request->status == 'rejected' ? 'danger' : 'warning')}}">
                            {{ucfirst($overtime_request->status)}}
                        </span>
                    </div>
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

                {!! Form::open(['url' => action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'update'], [$overtime_request->id]), 'method' => 'PUT', 'id' => 'edit_overtime_form']) !!}
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('employee', __('essentials::lang.employee') . ':') !!}
                                <div class="form-control-static">
                                    <strong>{{$overtime_request->user->user_full_name}}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('overtime_date', __('contact.date') . ':*') !!}
                                {!! Form::text('overtime_date', \Carbon::parse($overtime_request->overtime_date)->format('d/m/Y'), ['class' => 'form-control datepicker', 'required', 'placeholder' => __('contact.date')]) !!}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('start_time', __('essentials::lang.start_time') . ':*') !!}
                                {!! Form::time('start_time', $overtime_request->start_time, ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('end_time', __('essentials::lang.end_time') . ':*') !!}
                                {!! Form::time('end_time', $overtime_request->end_time, ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                        @if($can_manage_all_overtime)
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('status', __('sale.status') . ':*') !!}
                                {!! Form::select('status', [
                                    'draft' => __('sale.draft'),
                                    'pending' => __('essentials::lang.pending'),
                                    'approved' => __('essentials::lang.approved'), 
                                    'rejected' => __('essentials::lang.rejected')
                                ], $overtime_request->status, ['class' => 'form-control select2', 'required']) !!}
                            </div>
                        </div>
                        @else
                        {!! Form::hidden('status', 'pending') !!}
                        @endif
                        @if($can_manage_all_overtime)
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('approved_hours_label', __('essentials::lang.approved_hours') . ':') !!}
                                <div class="row">
                                    <div class="col-md-6">
                                        @php
                                            $approved_hours = $overtime_request->approved_hours ?: $overtime_request->hours_requested;
                                            $hours = floor($approved_hours);
                                            $minutes = round(($approved_hours - $hours) * 60);
                                        @endphp
                                        {!! Form::select('approved_hours_hour', array_combine(range(0, 23), range(0, 23)), $hours, ['class' => 'form-control', 'placeholder' => 'HH']) !!}
                                    </div>
                                    <div class="col-md-6">
                                        {!! Form::select('approved_hours_minute', [
                                            0 => '00', 15 => '15', 30 => '30', 45 => '45'
                                        ], $minutes >= 45 ? 45 : ($minutes >= 30 ? 30 : ($minutes >= 15 ? 15 : 0)), ['class' => 'form-control', 'placeholder' => 'MM']) !!}
                                    </div>
                                </div>
                                <p class="help-block">@lang('essentials::lang.approved_hours_help')</p>
                            </div>
                        </div>
                        @endif
                        @if(!$can_manage_all_overtime)
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('current_status', __('sale.status') . ':') !!}
                                <div class="form-control-static">
                                    <span class="label label-{{$overtime_request->status == 'approved' ? 'success' : ($overtime_request->status == 'rejected' ? 'danger' : 'warning')}}">
                                        {{ucfirst($overtime_request->status)}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif
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
                                ], $overtime_request->reason, ['class' => 'form-control select2', 'required']) !!}
                            </div>
                        </div>
                        @if($can_manage_all_overtime)
                        <div class="col-md-6">
                            <div class="form-group" id="rejection_reason_group" style="display: none;">
                                {!! Form::label('rejection_reason', __('essentials::lang.rejection_reason') . ':') !!}
                                {!! Form::textarea('rejection_reason', $overtime_request->rejection_reason, ['class' => 'form-control', 'rows' => '3']) !!}
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('description', __('essentials::lang.description') . ':') !!}
                                {!! Form::textarea('description', $overtime_request->description, ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('essentials::lang.overtime_description_placeholder')]) !!}
                            </div>
                        </div>
                    </div>

                    <!-- Current Details Summary -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h4><i class="fa fa-info-circle"></i> @lang('essentials::lang.current_overtime_details')</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.requested_hours'):</strong>
                                        {{$overtime_request->hours_requested}} @lang('essentials::lang.hours_short')
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.overtime_type'):</strong>
                                        {{$overtime_request->getOvertimeTypeLabel()}}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.hourly_rate'):</strong>
                                        {{number_format($overtime_request->hourly_rate, 2)}}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>@lang('essentials::lang.total_amount'):</strong>
                                        <span class="text-success">{{number_format($overtime_request->total_amount, 2)}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> @lang('messages.update')
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
    // Initialize datepicker
    $('.datepicker').datepicker({
        autoclose: true,
        format: 'dd/mm/yyyy'
    });

    @if($can_manage_all_overtime)
    // Show/hide rejection reason based on status (admin only)
    function toggleRejectionReason() {
        if ($('#status').val() === 'rejected') {
            $('#rejection_reason_group').show();
            $('#rejection_reason').attr('required', true);
        } else {
            $('#rejection_reason_group').hide();
            $('#rejection_reason').attr('required', false);
        }
    }

    $('#status').change(toggleRejectionReason);
    toggleRejectionReason(); // Initial check
    @endif

    // Handle form submission
    $('#edit_overtime_form').submit(function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: $(this).attr('action'),
            method: $(this).attr('method'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                submitBtn.prop('disabled', false);
                if (result.success) {
                    toastr.success(result.msg);
                    window.location.href = '{{action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'index'])}}';
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function() {
                submitBtn.prop('disabled', false);
                toastr.error('@lang("messages.something_went_wrong")');
            }
        });
    });

    // Custom reason handling
    $('#reason').change(function() {
        if ($(this).val() === 'other') {
            var customReason = prompt('@lang("essentials::lang.please_specify_reason")');
            if (customReason) {
                $(this).append('<option value="' + customReason + '" selected>' + customReason + '</option>');
            } else {
                $(this).val('{{$overtime_request->reason}}').trigger('change');
            }
        }
    });
});
</script>
@endsection