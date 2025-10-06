@extends('layouts.app')
@section('title', __('essentials::lang.overtime_requests'))

@section('content')
@include('essentials::layouts.nav_hrm')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('essentials::lang.overtime_requests')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">
                        @if($view_type === 'my')
                            @lang('essentials::lang.my_overtime_requests')
                        @else
                            @lang('essentials::lang.overtime_approvals')
                        @endif
                    </h3>
                    <div class="box-tools">
                        @if($view_type === 'all' && $can_manage_all_overtime)
                        <button type="button" class="btn btn-primary btn-sm" id="run_overtime_detection">
                            <i class="fa fa-search"></i> @lang('essentials::lang.run_overtime_detection')
                        </button>
                        @endif
                        @if($view_type === 'my' || $can_manage_all_overtime)
                        <a href="{{action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'create'])}}" 
                           class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> @lang('essentials::lang.add_overtime_request')
                        </a>
                        @endif
                    </div>
                </div>
                <div class="box-body">
                    @if($view_type === 'all' && $can_manage_all_overtime)
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('user_filter', __('essentials::lang.employee') . ':') !!}
                                {!! Form::select('user_filter', $users->pluck('user_full_name', 'id')->prepend(__('messages.all'), ''), '', ['class' => 'form-control select2', 'style' => 'width: 100%']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('status_filter', __('sale.status') . ':') !!}
                                {!! Form::select('status_filter', ['draft' => __('sale.draft'), 'pending' => __('essentials::lang.pending'), 'approved' => __('essentials::lang.approved'), 'rejected' => __('essentials::lang.rejected')], '', ['class' => 'form-control select2', 'style' => 'width: 100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('overtime_type_filter', __('essentials::lang.overtime_type') . ':') !!}
                                {!! Form::select('overtime_type_filter', ['workday' => __('essentials::lang.workday'), 'weekend' => __('essentials::lang.weekend'), 'holiday' => __('essentials::lang.holiday')], '', ['class' => 'form-control select2', 'style' => 'width: 100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('date_range_filter', __('reports.date_range') . ':') !!}
                                {!! Form::text('date_range_filter', '', ['class' => 'form-control', 'id' => 'date_range_filter', 'placeholder' => __('reports.select_date_range'), 'readonly']) !!}
                            </div>
                        </div>
                    </div>
                    @elseif($view_type === 'my')
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('my_status_filter', __('sale.status') . ':') !!}
                                {!! Form::select('my_status_filter', ['draft' => __('sale.draft'), 'pending' => __('essentials::lang.pending'), 'approved' => __('essentials::lang.approved'), 'rejected' => __('essentials::lang.rejected')], '', ['class' => 'form-control select2', 'style' => 'width: 100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('my_overtime_type_filter', __('essentials::lang.overtime_type') . ':') !!}
                                {!! Form::select('my_overtime_type_filter', ['workday' => __('essentials::lang.workday'), 'weekend' => __('essentials::lang.weekend'), 'holiday' => __('essentials::lang.holiday')], '', ['class' => 'form-control select2', 'style' => 'width: 100%', 'placeholder' => __('messages.all')]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('my_date_range_filter', __('reports.date_range') . ':') !!}
                                {!! Form::text('my_date_range_filter', '', ['class' => 'form-control', 'id' => 'my_date_range_filter', 'placeholder' => __('reports.select_date_range'), 'readonly']) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="overtime_requests_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    @if($view_type === 'all')
                                        <th>@lang('essentials::lang.employee')</th>
                                    @endif
                                    <th>@lang('lang_v1.date')</th>
                                    <th>@lang('essentials::lang.time_range')</th>
                                    <th>@lang('essentials::lang.hours')</th>
                                    <th>@lang('essentials::lang.overtime_type')</th>
                                    <th>@lang('essentials::lang.reason')</th>
                                    <th>@lang('sale.status')</th>
                                    <th>@lang('essentials::lang.amount')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Overtime Detection Modal -->
<div class="modal fade" id="overtime_detection_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('essentials::lang.run_overtime_detection')</h4>
            </div>
            {!! Form::open(['id' => 'overtime_detection_form', 'method' => 'POST']) !!}
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('detection_date', __('contact.date') . ':*') !!}
                    {!! Form::text('detection_date', '', ['class' => 'form-control datepicker', 'required']) !!}
                </div>
                <p class="text-muted">
                    <i class="fa fa-info-circle"></i> @lang('essentials::lang.overtime_detection_help')
                </p>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> @lang('essentials::lang.run_detection')
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<!-- Overtime Details Modal -->
<div class="modal fade" id="overtime_details_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('essentials::lang.overtime_request_details')</h4>
            </div>
            <div class="modal-body" id="overtime_details_content">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

<!-- Approval/Rejection Modals -->
<div class="modal fade" id="approve_overtime_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('essentials::lang.approve_overtime_request')</h4>
            </div>
            {!! Form::open(['id' => 'approve_overtime_form', 'method' => 'POST']) !!}
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('approved_hours_label', __('essentials::lang.approved_hours') . ':') !!}
                    <div style="display: inline-block; width: 100%;">
                        <div style="display: inline-block; width: 80px;">
                            {!! Form::select('approved_hours_hour', array_combine(range(0, 23), array_map(function($h) { return sprintf('%02d', $h); }, range(0, 23))), '', ['class' => 'form-control select2', 'id' => 'approved_hours_hour', 'placeholder' => 'Hours']) !!}
                        </div>
                        <span style="display: inline-block; margin: 0 10px; padding-top: 8px; font-weight: bold; font-size: 18px;">:</span>
                        <div style="display: inline-block; width: 80px;">
                            {!! Form::select('approved_hours_minute', [
                                '0' => '00',
                                '15' => '15', 
                                '30' => '30',
                                '45' => '45'
                            ], '', ['class' => 'form-control select2', 'id' => 'approved_hours_minute', 'placeholder' => 'Minutes']) !!}
                        </div>
                    </div>
                    <p class="help-block">@lang('essentials::lang.approved_hours_help')</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-check"></i> @lang('essentials::lang.approve')
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

<div class="modal fade" id="reject_overtime_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">@lang('essentials::lang.reject_overtime_request')</h4>
            </div>
            {!! Form::open(['id' => 'reject_overtime_form', 'method' => 'POST']) !!}
            <div class="modal-body">
                <div class="form-group">
                    {!! Form::label('rejection_reason', __('essentials::lang.rejection_reason') . ':*') !!}
                    {!! Form::textarea('rejection_reason', '', ['class' => 'form-control', 'rows' => '3', 'required']) !!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-times"></i> @lang('essentials::lang.reject')
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    var overtime_table = $('#overtime_requests_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'index'])}}",
            data: function (d) {
                d.view = '{{$view_type}}';
                @if($view_type === 'all')
                d.user_id = $('#user_filter').val();
                d.status = $('#status_filter').val();
                d.overtime_type = $('#overtime_type_filter').val();
                d.date_range = $('#date_range_filter').val();
                @elseif($view_type === 'my')
                d.status = $('#my_status_filter').val();
                d.overtime_type = $('#my_overtime_type_filter').val();
                d.date_range = $('#my_date_range_filter').val();
                @endif
            },
            error: function(xhr, status, error) {
                console.error('DataTables AJAX Error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            @if($view_type === 'all')
            { data: 'employee_name', name: 'user.user_full_name' },
            @endif
            { data: 'formatted_date', name: 'overtime_date' },
            { data: 'time_range', searchable: false, orderable: false },
            { data: 'hours_display', name: 'hours_requested', searchable: false },
            { data: 'overtime_type_label', name: 'overtime_type' },
            { data: 'reason', name: 'reason' },
            { data: 'status_badge', name: 'status' },
            { data: 'amount_display', searchable: false, orderable: false },
            { data: 'action', searchable: false, orderable: false }
        ],
        order: [[{{ $view_type === 'all' ? 1 : 0 }}, 'desc']] // Sort by date column initially
        // Note: Backend will override this to sort by created_at (latest submissions)
    });

    @if($view_type === 'all')
    // Filter change handlers (admin view only)
    $('#user_filter, #status_filter, #overtime_type_filter, #date_range_filter').change(function() {
        overtime_table.draw();
    });

    // Date range picker
    $('#date_range_filter').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    $('#date_range_filter').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        overtime_table.draw();
    });

    $('#date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        overtime_table.draw();
    });
    @elseif($view_type === 'my')
    // Filter change handlers (my view)
    $('#my_status_filter, #my_overtime_type_filter, #my_date_range_filter').change(function() {
        overtime_table.draw();
    });

    // Date range picker for my view
    $('#my_date_range_filter').daterangepicker({
        autoUpdateInput: false,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    $('#my_date_range_filter').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        overtime_table.draw();
    });

    $('#my_date_range_filter').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
        overtime_table.draw();
    });
    @endif

    @if($view_type === 'all' && $can_manage_all_overtime)
    // Run overtime detection
    $('#run_overtime_detection').click(function() {
        $('#overtime_detection_modal').modal('show');
    });

    $('#overtime_detection_form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "{{action([\Modules\Essentials\Http\Controllers\OvertimeController::class, 'runDetection'])}}",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                $('#overtime_detection_modal').modal('hide');
                if (result.success) {
                    toastr.success(result.msg);
                    overtime_table.draw();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });
    @endif

    // View overtime details
    $(document).on('click', '.view-overtime', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: "/hrm/overtime/" + id,
            method: 'GET',
            dataType: 'json',
            success: function(result) {
                var overtime = result.overtime;
                var html = '<div class="row">';
                html += '<div class="col-md-6">';
                html += '<strong>@lang("essentials::lang.employee"):</strong> ' + (overtime.user ? overtime.user.user_full_name : 'N/A') + '<br>';
                html += '<strong>@lang("contact.date"):</strong> ' + moment(overtime.overtime_date).format('MMM DD, YYYY') + '<br>';
                html += '<strong>@lang("essentials::lang.time_range"):</strong> ' + overtime.start_time + ' - ' + overtime.end_time + '<br>';
                html += '<strong>@lang("essentials::lang.hours"):</strong> ' + overtime.hours_requested;
                if (overtime.approved_hours && overtime.approved_hours != overtime.hours_requested) {
                    html += ' <small class="text-muted">(Approved: ' + overtime.approved_hours + ')</small>';
                }
                html += '<br>';
                html += '<strong>@lang("essentials::lang.overtime_type"):</strong> ' + overtime.overtime_type + '<br>';
                html += '</div>';
                html += '<div class="col-md-6">';
                html += '<strong>@lang("essentials::lang.reason"):</strong> ' + (overtime.reason || '') + '<br>';
                if (overtime.description) {
                    html += '<strong>@lang("essentials::lang.description"):</strong> ' + overtime.description + '<br>';
                }
                html += '<strong>@lang("sale.status"):</strong> <span class="label label-' + 
                        (overtime.status == 'approved' ? 'success' : overtime.status == 'rejected' ? 'danger' : 'warning') + '">' + 
                        overtime.status.charAt(0).toUpperCase() + overtime.status.slice(1) + '</span><br>';
                
                // Display amount information
                if (overtime.total_amount) {
                    var amountLabel = overtime.status == 'approved' ? '@lang("essentials::lang.amount")' : '@lang("essentials::lang.estimated_amount")';
                    html += '<strong>' + amountLabel + ':</strong> ' + parseFloat(overtime.total_amount).toFixed(2);
                    if (overtime.hourly_rate && overtime.multiplier_rate) {
                        html += ' <small class="text-muted">(' + overtime.hours_requested + 'h × ' + parseFloat(overtime.hourly_rate).toFixed(2) + ' × ' + overtime.multiplier_rate + 'x)</small>';
                    }
                    html += '<br>';
                } else if (overtime.hourly_rate && overtime.multiplier_rate) {
                    var estimated = overtime.hours_requested * overtime.hourly_rate * overtime.multiplier_rate;
                    html += '<strong>@lang("essentials::lang.estimated_amount"):</strong> ' + estimated.toFixed(2);
                    html += ' <small class="text-muted">(' + overtime.hours_requested + 'h × ' + parseFloat(overtime.hourly_rate).toFixed(2) + ' × ' + overtime.multiplier_rate + 'x)</small><br>';
                }
                html += '</div>';
                html += '</div>';
                
                if (overtime.approved_by || overtime.rejection_reason) {
                    html += '<hr><div class="row"><div class="col-md-12">';
                    if (overtime.approver) {
                        html += '<strong>' + (overtime.status == 'approved' ? '@lang("essentials::lang.approved_by")' : '@lang("essentials::lang.rejected_by")') + ':</strong> ' + overtime.approver.user_full_name + '<br>';
                        html += '<strong>@lang("contact.date"):</strong> ' + moment(overtime.approved_at).format('MMM DD, YYYY HH:mm') + '<br>';
                    }
                    if (overtime.rejection_reason) {
                        html += '<strong>@lang("essentials::lang.rejection_reason"):</strong> ' + overtime.rejection_reason + '<br>';
                    }
                    html += '</div></div>';
                }
                
                $('#overtime_details_content').html(html);
                $('#overtime_details_modal').modal('show');
            }
        });
    });

    @if($view_type === 'all' && $can_manage_all_overtime)
    // Approve overtime
    $(document).on('click', '.approve-overtime', function() {
        var id = $(this).data('id');
        $('#approve_overtime_form').attr('action', '/hrm/overtime/' + id + '/approve');
        
        // Fetch overtime details to prefill approved_hours field
        $.ajax({
            url: "/hrm/overtime/" + id,
            method: 'GET',
            dataType: 'json',
            success: function(result) {
                var overtime = result.overtime;
                // Convert decimal hours to HH:MM format
                var totalHours = overtime.hours_requested;
                var hours = Math.floor(totalHours);
                var minutes = Math.round((totalHours - hours) * 60);
                
                // Round minutes to nearest 15-minute interval
                if (minutes < 8) minutes = 0;
                else if (minutes < 23) minutes = 15;
                else if (minutes < 38) minutes = 30;
                else if (minutes < 53) minutes = 45;
                else {
                    hours += 1;
                    minutes = 0;
                }
                
                // Prefill dropdowns
                $('#approved_hours_hour').val(hours.toString()).trigger('change');
                $('#approved_hours_minute').val(minutes.toString()).trigger('change');
                $('#approve_overtime_modal').modal('show');
            },
            error: function() {
                // If AJAX fails, still show modal but without prefill
                $('#approved_hours_hour').val('').trigger('change');
                $('#approved_hours_minute').val('').trigger('change');
                $('#approve_overtime_modal').modal('show');
            }
        });
    });

    $('#approve_overtime_form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                $('#approve_overtime_modal').modal('hide');
                if (result.success) {
                    toastr.success(result.msg);
                    overtime_table.draw();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    // Reject overtime
    $(document).on('click', '.reject-overtime', function() {
        var id = $(this).data('id');
        $('#reject_overtime_form').attr('action', '/hrm/overtime/' + id + '/reject');
        $('#reject_overtime_modal').modal('show');
    });

    $('#reject_overtime_form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(result) {
                $('#reject_overtime_modal').modal('hide');
                if (result.success) {
                    toastr.success(result.msg);
                    overtime_table.draw();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });
    
    // Clear form when modal is hidden
    $('#approve_overtime_modal').on('hidden.bs.modal', function () {
        $('#approved_hours_hour').val('').trigger('change');
        $('#approved_hours_minute').val('').trigger('change');
    });
    
    $('#reject_overtime_modal').on('hidden.bs.modal', function () {
        $('#rejection_reason').val('');
    });
    @endif

    // Delete overtime request
    $(document).on('click', '.delete-overtime', function() {
        var id = $(this).data('id');
        var button = $(this);
        
        swal({
            title: '@lang("messages.sure")',
            text: '@lang("essentials::lang.delete_overtime_warning")',
            icon: 'warning',
            buttons: {
                cancel: '@lang("messages.cancel")',
                confirm: {
                    text: '@lang("messages.delete")',
                    value: true,
                    className: 'btn-danger'
                }
            },
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: '/hrm/overtime/' + id,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            overtime_table.draw();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function() {
                        toastr.error('@lang("messages.something_went_wrong")');
                    }
                });
            }
        });
    });
});
</script>
@endsection