@extends('layouts.app')
@section('title', __('lang_v1.warranty_management'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.warranty_management')
        <small>@lang('lang_v1.warranty_management')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('warranty_status', __('lang_v1.warranty_status') . ':') !!}
                {!! Form::select('warranty_status', [
                    '' => __('messages.all'),
                    'active' => __('lang_v1.active'),
                    'expires_soon' => __('lang_v1.expires_soon') . ' (30 ' . __('lang_v1.days') . ')',
                    'expired' => __('lang_v1.expired')
                ], null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sold_date_start', __('lang_v1.sold_date_from') . ':') !!}
                {!! Form::text('sold_date_start', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sold_date_end', __('lang_v1.sold_date_to') . ':') !!}
                {!! Form::text('sold_date_end', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.warranty_management')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="warranty_table">
                        <thead>
                            <tr>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('lang_v1.sold_date')</th>
                                <th>@lang('lang_v1.warranty_expiry')</th>
                                <th>@lang('lang_v1.imei')</th>
                                <th>@lang('contact.customer')</th>
                                <th>@lang('lang_v1.warranty_contact')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
<div class="modal fade product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        warranty_table = $('#warranty_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action([\App\Http\Controllers\WarrantyManagementController::class, 'getWarrantyData'])}}",
                data: function (d) {
                    if($('#location_id').length) {
                        d.location_id = $('#location_id').val();
                    }
                    if($('#warranty_status').length) {
                        d.warranty_status = $('#warranty_status').val();
                    }
                    if($('#sold_date_start').length) {
                        d.sold_date_start = $('#sold_date_start').val();
                    }
                    if($('#sold_date_end').length) {
                        d.sold_date_end = $('#sold_date_end').val();
                    }
                }
            },
            columnDefs: [{
                "targets": [6],
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'product_info', name: 'product_info', orderable: false, searchable: false },
                { data: 'sold_date', name: 'transactions.transaction_date' },
                { data: 'warranty_expiry', name: 'warranty_expiry', orderable: false, searchable: false },
                { data: 'imei_info', name: 'imei_info', orderable: false, searchable: false },
                { data: 'customer_info', name: 'customer_info', orderable: false, searchable: false },
                { data: 'warranty_contact', name: 'warranty_contact', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#warranty_table'));
            },
            createdRow: function( row, data, dataIndex ) {
                // Add classes or styling based on warranty status
                if (data.warranty_expiry && data.warranty_expiry.includes('label-danger')) {
                    $(row).addClass('warranty-expired');
                } else if (data.warranty_expiry && data.warranty_expiry.includes('label-warning')) {
                    $(row).addClass('warranty-expiring');
                }
            }
        });
        
        // Initialize date pickers
        $('#sold_date_start, #sold_date_end').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });
        
        // Filter functionality
        $('#location_id, #warranty_status').change(function() {
            warranty_table.ajax.reload();
        });
        
        $('#sold_date_start, #sold_date_end').change(function() {
            warranty_table.ajax.reload();
        });
        
        // Handle modal display
        $(document).on('click', '.btn-modal', 
            function(e){
                e.preventDefault();
                var container = $('.product_modal');
                $.get($(this).data('href'), function(data) {
                    $(container).html(data).modal('show');
                });
            }
        );
        
        // Initialize top bar icon functionality
        // Initialize Bootstrap popovers with proper calculator content
        $('[data-toggle="popover"]').popover({
            trigger: 'click',
            html: true,
            placement: 'bottom'
        });
        
        // Initialize Bootstrap tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Today's profit modal functionality
        $(document).on('click', '#view_todays_profit', function() {
            var loader = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            $('#todays_profit').html(loader);
            $('#todays_profit_modal').modal('show');
        });
        
        $('#todays_profit_modal').on('shown.bs.modal', function() {
            var start = $('#modal_today').val();
            var end = start;
            var location_id = '';
            if (typeof updateProfitLoss === 'function') {
                updateProfitLoss(start, end, location_id, $('#todays_profit'));
            }
        });
        
        // Calculator functions for the popover
        window.clearScreen = function() {
            document.calc.result.value = '';
        };
        
        window.calEnterVal = function(id) {
            var value = document.calc.result.value;
            if(id === '=') {
                try {
                    document.calc.result.value = eval(value);
                } catch(e) {
                    document.calc.result.value = 'Error';
                }
            } else if(id === 'AC' || id === 'CE') {
                document.calc.result.value = '';
            } else {
                document.calc.result.value += id === '*' ? '*' : id === '/' ? '/' : id === '%' ? '%' : id === '+' ? '+' : id === '-' ? '-' : id;
            }
        };
        
        window.calculate = function() {
            try {
                document.calc.result.value = eval(document.calc.result.value);
            } catch(e) {
                document.calc.result.value = 'Error';
            }
        };
    });
</script>
@endsection

@section('css')
<style>
.warranty-expired {
    background-color: #f8d7da !important;
}
.warranty-expiring {
    background-color: #fff3cd !important;
}
.label {
    display: inline;
    padding: 0.2em 0.6em 0.3em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25em;
}
.label-success {
    background-color: #5cb85c;
}
.label-warning {
    background-color: #f0ad4e;
}
.label-danger {
    background-color: #d9534f;
}
.label-info {
    background-color: #5bc0de;
}
</style>
@endsection