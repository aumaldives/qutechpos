@extends('layouts.app')

@section('title', __('superadmin::lang.subscription'))

@section('content')

<section class="content-header">
    <h1>{{ __('superadmin::lang.select_location_quantity') }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-map-marker"></i>
                        {{ $package->name }} - @lang('superadmin::lang.choose_locations')
                    </h3>
                </div>
                
                <div class="box-body">
                    <form action="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], $package->id) }}" method="get" id="location-quantity-form">
                        <input type="hidden" name="location_quantity_confirmed" value="1">
                        
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> @lang('superadmin::lang.per_location_pricing_explanation')</h4>
                            <p>{{ __('superadmin::lang.per_location_package_description', ['package_name' => $package->name]) }}</p>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="location_quantity">@lang('superadmin::lang.how_many_locations'):</label>
                                    <input type="number" 
                                           name="location_quantity" 
                                           id="location_quantity" 
                                           class="form-control" 
                                           min="{{ $package->min_locations }}" 
                                           @if($package->max_locations > 0) max="{{ $package->max_locations }}" @endif
                                           value="{{ max($package->min_locations, 1) }}"
                                           required>
                                    <small class="help-block">
                                        @lang('superadmin::lang.location_quantity_range', [
                                            'min' => $package->min_locations,
                                            'max' => $package->max_locations > 0 ? $package->max_locations : __('superadmin::lang.unlimited')
                                        ])
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>@lang('superadmin::lang.pricing_breakdown'):</label>
                                    <div class="well well-sm">
                                        @php
                                            $exchange_rate = \Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate();
                                            $min_quantity = max($package->min_locations, 1);
                                            $usd_price_per_location = $package->price_per_location;
                                            $mvr_price_per_location = \Modules\Superadmin\Utils\CurrencyUtil::convertUsdToMvr($usd_price_per_location, $exchange_rate);
                                            $usd_total = $package->calculatePrice($min_quantity);
                                            $mvr_total = \Modules\Superadmin\Utils\CurrencyUtil::convertUsdToMvr($usd_total, $exchange_rate);
                                        @endphp
                                        <div class="row">
                                            <div class="col-xs-8">
                                                MVR <span id="mvr-price-per-location">{{ number_format($mvr_price_per_location, 2) }}</span> 
                                                × <span id="location-count-display">{{ $min_quantity }}</span> @lang('superadmin::lang.locations')
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <strong id="mvr-total-price">MVR {{ number_format($mvr_total, 2) }}</strong>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <small class="text-muted">
                                                    USD {{ number_format($usd_price_per_location, 2) }} × {{ $min_quantity }} = USD {{ number_format($usd_total, 2) }}
                                                </small>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <small class="text-muted">Rate: {{ $exchange_rate }}</small>
                                            </div>
                                        </div>
                                        <hr style="margin: 10px 0;">
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <strong>@lang('superadmin::lang.total_monthly'):</strong>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <strong class="text-primary" style="font-size: 1.2em;">
                                                    <span id="currency-symbol">MVR </span><span id="final-mvr-price">{{ number_format($mvr_total, 2) }}</span>
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fa fa-info-circle"></i>
                            @lang('superadmin::lang.location_selection_note')
                        </div>
                    </form>
                </div>
                
                <div class="box-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index']) }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i>
                                @lang('messages.back')
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            <button type="submit" form="location-quantity-form" class="btn btn-primary btn-lg">
                                <i class="fa fa-credit-card"></i>
                                @lang('superadmin::lang.proceed_to_payment')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var usdPricePerLocation = {{ $package->price_per_location }};
    var mvrPricePerLocation = {{ $mvr_price_per_location }};
    var exchangeRate = {{ $exchange_rate }};
    var minLocations = {{ $package->min_locations }};
    var maxLocations = {{ $package->max_locations }};
    
    // Update pricing when location quantity changes
    $('#location_quantity').on('input change', function() {
        var quantity = parseInt($(this).val()) || minLocations;
        
        // Ensure within bounds
        if (quantity < minLocations) {
            quantity = minLocations;
            $(this).val(quantity);
        }
        
        if (maxLocations > 0 && quantity > maxLocations) {
            quantity = maxLocations;
            $(this).val(quantity);
        }
        
        // Update display
        $('#location-count-display').text(quantity);
        
        // Calculate both USD and MVR prices
        var usdTotal = (usdPricePerLocation * quantity);
        var mvrPerLocation = (usdPricePerLocation * exchangeRate);
        var mvrTotal = (usdTotal * exchangeRate);
        
        // Update MVR displays (primary)
        $('#mvr-price-per-location').text(mvrPerLocation.toFixed(2));
        $('#mvr-total-price').text('MVR ' + mvrTotal.toFixed(2));
        $('#final-mvr-price').text(mvrTotal.toFixed(2));
        
        // Update USD displays (secondary/reference)
        $('.text-muted').html('USD ' + usdPricePerLocation.toFixed(2) + ' × ' + quantity + ' = USD ' + usdTotal.toFixed(2));
    });
    
    // Initial calculation
    $('#location_quantity').trigger('change');
});
</script>
@endsection