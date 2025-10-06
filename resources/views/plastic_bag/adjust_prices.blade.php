@extends('layouts.app')
@section('title', __('Adjust Plastic Bag Prices'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Adjust Plastic Bag Prices')
        <small>@lang('Update selling prices for plastic bag types')</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">@lang('Adjust Plastic Bag Prices')</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Plastic Bag Price Adjustment')])
        {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'updatePrices']), 'method' => 'post', 'id' => 'price_adjustment_form']) !!}
        
        <div class="row">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('Plastic Bag Type')</th>
                                <th>@lang('Current Price')</th>
                                <th>@lang('New Price') *</th>
                                <th>@lang('Current Stock')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plastic_bag_types as $type)
                                <tr>
                                    <td>
                                        <strong>{{ $type->name }}</strong>
                                        @if($type->description)
                                            <br><small class="text-muted">{{ $type->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="display_currency" data-currency_symbol="true">{{ $type->price }}</span>
                                    </td>
                                    <td>
                                        {!! Form::text('prices['.$type->id.']', $type->price, ['class' => 'form-control input_number price_input', 'required', 'placeholder' => __('New Price'), 'data-type-id' => $type->id]) !!}
                                    </td>
                                    <td>
                                        {{ number_format($type->stock_quantity, 0) }} bags
                                        @if($type->alert_quantity && $type->stock_quantity <= $type->alert_quantity)
                                            <span class="label label-danger">Low Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            
                            @if(count($plastic_bag_types) == 0)
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        @lang('No plastic bag types found. Please add some types first.')
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(count($plastic_bag_types) > 0)
        <div class="row">
            <div class="col-sm-12">
                <hr>
                <div class="form-group">
                    <h4>@lang('Bulk Price Adjustment')</h4>
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('adjustment_type', __('Adjustment Type') . ':') !!}
                                {!! Form::select('adjustment_type', ['percentage' => 'Percentage', 'fixed' => 'Fixed Amount'], null, ['class' => 'form-control', 'id' => 'adjustment_type', 'placeholder' => 'Select Type']) !!}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('adjustment_value', __('Adjustment Value') . ':') !!}
                                {!! Form::text('adjustment_value', null, ['class' => 'form-control input_number', 'id' => 'adjustment_value', 'placeholder' => 'Value']) !!}
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('', '&nbsp;') !!}
                                <br>
                                <button type="button" class="btn btn-info" id="apply_bulk_adjustment">
                                    @lang('Apply to All')
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                                {!! Form::label('', '&nbsp;') !!}
                                <br>
                                <button type="button" class="btn btn-default" id="reset_prices">
                                    @lang('Reset Prices')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary pull-right" id="save_prices_btn">
                    @lang('Save Changes')
                </button>
            </div>
        </div>
        @endif

        {!! Form::close() !!}
    @endcomponent

</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        var original_prices = {};
        
        // Store original prices
        $('.price_input').each(function() {
            var type_id = $(this).data('type-id');
            original_prices[type_id] = parseFloat($(this).val());
        });

        // Apply bulk adjustment
        $('#apply_bulk_adjustment').on('click', function() {
            var adjustment_type = $('#adjustment_type').val();
            var adjustment_value = parseFloat($('#adjustment_value').val());
            
            if (!adjustment_type || !adjustment_value) {
                toastr.error('Please select adjustment type and enter value');
                return;
            }

            $('.price_input').each(function() {
                var current_price = parseFloat($(this).val());
                var new_price = 0;
                
                if (adjustment_type == 'percentage') {
                    new_price = current_price + (current_price * adjustment_value / 100);
                } else {
                    new_price = current_price + adjustment_value;
                }
                
                if (new_price < 0) new_price = 0;
                $(this).val(__number_f(new_price, false, false));
            });
            
            toastr.success('Bulk adjustment applied successfully');
        });

        // Reset prices to original values
        $('#reset_prices').on('click', function() {
            $('.price_input').each(function() {
                var type_id = $(this).data('type-id');
                $(this).val(__number_f(original_prices[type_id], false, false));
            });
            
            toastr.info('Prices reset to original values');
        });

        // Form submission
        $(document).on('submit', 'form#price_adjustment_form', function(e) {
            e.preventDefault();
            
            var form_data = $(this).serialize();
            $('#save_prices_btn').attr('disabled', true);
            
            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: form_data,
                success: function(result) {
                    if (result.success == 1) {
                        toastr.success(result.msg);
                        // Update original prices with new values
                        $('.price_input').each(function() {
                            var type_id = $(this).data('type-id');
                            original_prices[type_id] = parseFloat($(this).val());
                        });
                    } else {
                        toastr.error(result.msg);
                    }
                    $('#save_prices_btn').attr('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.log('Error:', xhr.responseText);
                    if(xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var errorMsg = '';
                        $.each(errors, function(key, value) {
                            errorMsg += value[0] + '<br>';
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                    $('#save_prices_btn').attr('disabled', false);
                }
            });
        });
    });
</script>
@endsection