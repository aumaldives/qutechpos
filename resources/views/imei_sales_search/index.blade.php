@extends('layouts.app')
@section('title', __('lang_v1.imei_sales_search'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.imei_sales_search')
        <small>@lang('lang_v1.search_sales_by_imei')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    
    <!-- Quick Search Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.quick_imei_search')])
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('quick_imei_search', __('lang_v1.enter_imei') . ':') !!}
                            <div class="input-group">
                                {!! Form::text('quick_imei_search', null, ['class' => 'form-control', 'id' => 'quick_imei_search', 'placeholder' => __('lang_v1.enter_imei_to_search')]) !!}
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="button" id="quick_search_btn">
                                        <i class="fa fa-search"></i> @lang('messages.search')
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="quick_search_result" style="display: none;">
                            <div class="alert alert-success" id="quick_result_success" style="display: none;">
                                <h4><i class="icon fa fa-check"></i> @lang('lang_v1.imei_found')!</h4>
                                <div id="quick_result_content"></div>
                                <button class="btn btn-info btn-sm" id="view_transaction_btn" style="margin-top: 10px;">
                                    <i class="fa fa-eye"></i> @lang('messages.view') @lang('sale.sale')
                                </button>
                            </div>
                            <div class="alert alert-warning" id="quick_result_not_found" style="display: none;">
                                <h4><i class="icon fa fa-warning"></i> @lang('lang_v1.imei_not_found')</h4>
                                <p>@lang('lang_v1.no_sales_found_for_imei')</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Advanced Search Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('lang_v1.advanced_imei_search')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('imei_search', __('lang_v1.imei_number') . ':') !!}
                        {!! Form::text('imei_search', null, ['class' => 'form-control', 'id' => 'imei_search', 'placeholder' => __('lang_v1.search_by_imei')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.all')]) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', __('report.date_range') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('start_date', null, ['class' => 'form-control', 'id' => 'start_date', 'readonly', 'placeholder' => __('lang_v1.start_date')]) !!}
                            <span class="input-group-addon">@lang('lang_v1.to')</span>
                            {!! Form::text('end_date', null, ['class' => 'form-control', 'id' => 'end_date', 'readonly', 'placeholder' => __('lang_v1.end_date')]) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <br>
                        <button type="button" class="btn btn-primary" id="search_btn">
                            <i class="fa fa-search"></i> @lang('lang_v1.search_sales')
                        </button>
                        <button type="button" class="btn btn-default" id="clear_btn">
                            <i class="fa fa-refresh"></i> @lang('messages.clear')
                        </button>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Results Section -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.imei_sales_results')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="imei_sales_table">
                        <thead>
                            <tr>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('lang_v1.imei_number')</th>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('contact.customer')</th>
                                <th>@lang('purchase.business_location')</th>
                                <th>@lang('sale.total_amount')</th>
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
        
        // Initialize date pickers
        $('#start_date, #end_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });

        // Initialize DataTable
        var imei_sales_table = $('#imei_sales_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action([\App\Http\Controllers\ImeiSalesSearchController::class, 'searchByImei'])}}",
                data: function (d) {
                    d.imei_search = $('#imei_search').val();
                    d.location_id = $('#location_id').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columnDefs: [{
                "targets": [6],
                "orderable": false,
                "searchable": false
            }],
            columns: [
                { data: 'invoice_info', name: 'invoice_info' },
                { data: 'imei_info', name: 'imei_info' },
                { data: 'product_info', name: 'product_info' },
                { data: 'customer_info', name: 'customer_info' },
                { data: 'location_name', name: 'business_locations.name' },
                { data: 'total_amount', name: 'final_total' },
                { data: 'action', name: 'action' }
            ],
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#imei_sales_table'));
            }
        });

        // Search functionality
        $('#search_btn, #imei_search').on('click keyup', function(e) {
            if (e.type === 'click' || e.keyCode === 13) {
                imei_sales_table.ajax.reload();
            }
        });

        // Filter change events
        $('#location_id, #start_date, #end_date').change(function() {
            imei_sales_table.ajax.reload();
        });

        // Clear filters
        $('#clear_btn').click(function() {
            $('#imei_search').val('');
            $('#location_id').val('').trigger('change');
            $('#start_date').val('');
            $('#end_date').val('');
            imei_sales_table.ajax.reload();
        });

        // Quick search functionality
        $('#quick_search_btn, #quick_imei_search').on('click keyup', function(e) {
            if (e.type === 'click' || e.keyCode === 13) {
                var imei = $('#quick_imei_search').val().trim();
                if (imei === '') {
                    toastr.error('@lang("lang_v1.please_enter_imei")');
                    return;
                }

                // Show loading
                $('#quick_search_btn').html('<i class="fa fa-spinner fa-spin"></i> @lang("messages.loading")');
                $('#quick_search_result').hide();

                $.ajax({
                    url: "{{action([\App\Http\Controllers\ImeiSalesSearchController::class, 'quickSearch'])}}",
                    method: 'GET',
                    data: { imei: imei },
                    dataType: 'json',
                    success: function(response) {
                        $('#quick_search_btn').html('<i class="fa fa-search"></i> @lang("messages.search")');
                        
                        if (response.found) {
                            var data = response.data;
                            var content = '<strong>@lang("sale.invoice_no"):</strong> ' + data.invoice_no + '<br>' +
                                         '<strong>@lang("sale.date"):</strong> ' + data.transaction_date + '<br>' +
                                         '<strong>@lang("contact.customer"):</strong> ' + data.customer_name + '<br>' +
                                         '<strong>@lang("product.product_name"):</strong> ' + data.product_name + '<br>' +
                                         '<strong>@lang("sale.total_amount"):</strong> <span class="display_currency">' + data.total_amount + '</span>';
                            
                            $('#quick_result_content').html(content);
                            $('#view_transaction_btn').attr('onclick', 'showTransactionModal("' + data.view_url + '")');
                            $('#quick_result_success').show();
                            $('#quick_result_not_found').hide();
                        } else {
                            $('#quick_result_success').hide();
                            $('#quick_result_not_found').show();
                        }
                        
                        $('#quick_search_result').show();
                        __currency_convert_recursively($('#quick_search_result'));
                    },
                    error: function() {
                        $('#quick_search_btn').html('<i class="fa fa-search"></i> @lang("messages.search")');
                        toastr.error('@lang("messages.something_went_wrong")');
                    }
                });
            }
        });

        // Handle modal display
        $(document).on('click', '.btn-modal', function(e){
            e.preventDefault();
            var container = $('.product_modal');
            $.get($(this).data('href'), function(data) {
                $(container).html(data).modal('show');
            });
        });
    });

    // Function to show transaction modal
    function showTransactionModal(url) {
        var container = $('.product_modal');
        $.get(url, function(data) {
            $(container).html(data).modal('show');
        });
    }
    
    // Initialize top bar icon functionality
    $(document).ready(function() {
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
.input-group {
    width: 100%;
}
.alert {
    margin-bottom: 0;
}
#quick_search_result {
    margin-top: 10px;
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
.label-primary {
    background-color: #337ab7;
}
</style>
@endsection