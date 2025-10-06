@extends('layouts.app')
@section('title', 'Superadmin Subscription')

@section('content')
@include('superadmin::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'superadmin::lang.subscription' )
        <small>@lang( 'superadmin::lang.view_subscription' )</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">

    @include('superadmin::layouts.partials.currency')
    @component('components.filters', ['title' => __('report.filters')])
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('package_id', __('superadmin::lang.packages') . ':') !!}
            {!! Form::select('package_id', $packages, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('subscription_status', __('superadmin::lang.status') . ':') !!}
            {!! Form::select('subscription_status', $subscription_statuses, 'waiting', ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('created_at', __('lang_v1.created_at') . ':') !!}
            {!! Form::text('created_at', null, ['placeholder' => __('lang_v1.select_a_date_range'),
            'class' => 'form-control', 'readonly']); !!}
        </div>
    </div>
    @endcomponent
    <div class="box box-solid">
        <div class="box-body">
            @can('superadmin')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="superadmin_subscription_table">
                    <thead>
                        <tr>
                            <th>@lang( 'superadmin::lang.business_name' )</th>
                            <th>@lang( 'superadmin::lang.package_name' )</th>
                            <th>@lang( 'superadmin::lang.status' )</th>
                            <th>@lang( 'lang_v1.created_at' )</th>
                            <th>@lang( 'superadmin::lang.start_date' )</th>
                            <th>@lang( 'superadmin::lang.trial_end_date' )</th>
                            <th>@lang( 'superadmin::lang.end_date' )</th>
                            <th>@lang( 'superadmin::lang.price' )</th>
                            <th>@lang( 'superadmin::lang.paid_via' )</th>
                            <th>@lang( 'superadmin::lang.payment_transaction_id' )</th>
                            <th>Bank Transfer Info</th>
                            <th>@lang( 'superadmin::lang.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcan
        </div>

    </div>
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receiptModal" tabindex="-1" role="dialog" aria-labelledby="receiptModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="receiptModalLabel">
                        <i class="fa fa-receipt"></i> Payment Receipt
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Bank:</strong></label>
                                <span id="receipt-bank" class="form-control-static"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Currency:</strong></label>
                                <span id="receipt-currency" class="form-control-static"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><strong>Receipt:</strong></label>
                                <div id="receipt-image-container" style="text-align: center; border: 1px solid #ddd; padding: 20px; border-radius: 5px; background: #f9f9f9;">
                                    <img id="receipt-image" src="" alt="Receipt" style="max-width: 100%; max-height: 500px; border: 1px solid #ccc; border-radius: 5px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="downloadReceipt()">
                        <i class="fa fa-download"></i> Download Receipt
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $(document).ready(function() {

        $('#created_at').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#created_at').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                superadmin_subscription_table.ajax.reload();
            }
        );
        $('#created_at').on('cancel.daterangepicker', function(ev, picker) {
            $('#created_at').val('');
            superadmin_subscription_table.ajax.reload();
        });

        $('#package_id, #subscription_status').change(function() {
            superadmin_subscription_table.ajax.reload();
        });

        // superadmin_subscription_table  
        var superadmin_subscription_table = $('#superadmin_subscription_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/superadmin/superadmin-subscription',
                data: function(d) {
                    if ($('#package_id').length) {
                        d.package_id = $('#package_id').val();
                    }
                    if ($('#subscription_status').length) {
                        d.status = $('#subscription_status').val();
                    }

                    var start = '';
                    var end = '';
                    if ($('#created_at').val()) {
                        start = $('input#created_at')
                            .data('daterangepicker')
                            .startDate.format('YYYY-MM-DD');
                        end = $('input#created_at')
                            .data('daterangepicker')
                            .endDate.format('YYYY-MM-DD');
                    }
                    d.start_date = start;
                    d.end_date = end;

                    d = __datatable_ajax_callback(d);
                },
            },
            "fnDrawCallback": function(oSettings) {
                __currency_convert_recursively($('#superadmin_subscription_table'), true);
            }
        });


        // change_status button
        $(document).on('click', 'button.change_status', function() {
            $("div#statusModal").load($(this).data('href'), function() {
                $(this).modal('show');
                $("form#status_change_form").submit(function(e) {
                    e.preventDefault();
                    var url = $(this).attr("action");
                    var data = $(this).serialize();
                    $.ajax({
                        method: "POST",
                        dataType: "json",
                        data: data,
                        url: url,
                        success: function(result) {
                            if (result.success == true) {
                                $("div#statusModal").modal('hide');
                                toastr.success(result.msg);
                                superadmin_subscription_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                });
            });
        });

        $(document).on('shown.bs.modal', '.view_modal', function() {
            $('.edit-subscription-modal .datepicker').datepicker({
                autoclose: true,
                format: datepicker_date_format
            });
            $("form#edit_subscription_form").submit(function(e) {
                e.preventDefault();
                var url = $(this).attr("action");
                var data = $(this).serialize();
                $.ajax({
                    method: "POST",
                    dataType: "json",
                    data: data,
                    url: url,
                    success: function(result) {
                        if (result.success == true) {
                            $("div.view_modal").modal('hide');
                            toastr.success(result.msg);
                            superadmin_subscription_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });
        });

    });

    // Receipt viewing functions
    function viewReceipt(receiptPath, bank, currency) {
        if (!receiptPath || receiptPath === 'null' || receiptPath === '0') {
            toastr.error('No receipt available');
            return;
        }

        console.log('Viewing receipt:', receiptPath, 'Bank:', bank, 'Currency:', currency);

        // Set bank and currency info
        $('#receipt-bank').text(bank ? bank.toUpperCase() : 'N/A');
        $('#receipt-currency').text(currency ? currency.toUpperCase() : 'N/A');
        
        // Set receipt image
        var fullPath = '/storage/' + receiptPath;
        console.log('Full image path:', fullPath);
        
        // Reset image container
        $('#receipt-image-container').html('<img id="receipt-image" src="" alt="Receipt" style="max-width: 100%; max-height: 500px; border: 1px solid #ccc; border-radius: 5px;">');
        
        $('#receipt-image').attr('src', fullPath);
        $('#receipt-image').attr('data-download-url', fullPath);
        
        // Show modal
        $('#receiptModal').modal('show');
    }

    function downloadReceipt() {
        var downloadUrl = $('#receipt-image').attr('data-download-url');
        if (downloadUrl) {
            var link = document.createElement('a');
            link.download = 'payment-receipt';
            link.href = downloadUrl;
            link.click();
        }
    }

    // Handle image load error
    $('#receipt-image').on('error', function() {
        var img = $(this);
        var originalSrc = img.attr('src');
        console.log('Failed to load image:', originalSrc);
        
        // Try to load a fallback image
        if (originalSrc.indexOf('no-image.png') === -1) {
            img.attr('src', '/img/default-image.png');
        } else {
            img.hide();
            $('#receipt-image-container').html('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Receipt file not found or corrupted<br><small>Path: ' + originalSrc + '</small></div>');
        }
    });
</script>
@endsection