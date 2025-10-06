@extends('layouts.pricing')
@section('title', __('superadmin::lang.pricing'))

@section('content')


<div class="pricing_table_container">

    <div class="navbar">
        <div class="logo">
            <a href="/">
                <img src="/uploads/img/logo.png" class="img-rounded" alt="Logo" width="150">
            </a>
        </div>
        <ul class="menu">
            <li><a href="/">Home</a></li>
            <li><a href="https://islebooks.mv/contact-us" target="_blank">Contact Us</a></li>
        
        </ul>
    </div>

    <div class="heading">
        <h2>Our <b>Pricing</b> Plans</h2>
        <p class="text">A plan for every business.<br><b>All plans offer all advanced features of our POS software</b></p>

        <div class="row switch-container">
            <div class="switch-wrapper">
                <input id="monthly" type="radio" name="switch" checked>
                <input id="yearly" type="radio" name="switch">
                <label for="monthly">Monthly</label>
                <label for="yearly">Yearly</label>
                <span class="highlighter"></span>
            </div>
        </div>

        <div class="row">
            <button type="button" class="btn-free-trial w-50">
                <a href="/business/register?package=16" class="btn btn-block btn-success">Start your free trial now</a>
            </button>
        </div>

        </h2>

    </div>
    @include('superadmin::subscription.partials.new_packages', ['action_type' => 'register'])
</div>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {

        $(".plan.package-months").show();

        $("input[name='switch']").change(function() {
            var filter = $(this).attr("id");
            if (filter === "monthly") {
                $(".plan").hide();
                $(".plan.package-months").show();
            } else {
                $(".plan").hide();
                $(".plan.package-years").show();
            }
        });

    });

    // Update package price for per-location packages
    function updatePackagePrice(packageId) {
        var input = $('#location-input-' + packageId);
        var quantity = parseInt(input.val()) || 1;
        var pricePerLocation = parseFloat(input.data('price-per-location'));
        
        // Validate range
        var min = parseInt(input.attr('min')) || 1;
        var max = parseInt(input.attr('max')) || 10;
        
        if (quantity < min) {
            quantity = min;
            input.val(quantity);
        } else if (quantity > max) {
            quantity = max;
            input.val(quantity);
        }
        
        var totalUsd = pricePerLocation * quantity;
        var totalMvr = totalUsd * 15.4;
        
        // Update display elements
        $('#total-display-' + packageId + ' .total-price').text(totalUsd.toFixed(2));
        $('#total-display-' + packageId + ' .total-mvr').text(Math.round(totalMvr));
    }

    // Adjust location quantity with +/- buttons
    function adjustLocation(packageId, change) {
        var input = $('#location-input-' + packageId);
        var currentValue = parseInt(input.val()) || 1;
        var newValue = currentValue + change;
        var min = parseInt(input.data('min')) || 1;
        var max = parseInt(input.data('max')) || 999;
        
        if (newValue >= min && newValue <= max) {
            input.val(newValue);
            updatePackagePrice(packageId);
        }
    }

    // Handle per-location package subscription
    function subscribeToPerLocation(packageId) {
        var quantity = $('#location-input-' + packageId).val() || 1;
        
        // Confirm the selection
        var pricePerLocation = $('#location-input-' + packageId).data('price-per-location');
        var totalPrice = (pricePerLocation * quantity).toFixed(2);
        
        if (confirm('Subscribe to ' + quantity + ' location(s) for $' + totalPrice + '/month?')) {
            window.location.href = '/subscription/' + packageId + '/pay?location_quantity=' + quantity + '&location_quantity_confirmed=1';
        }
    }
</script>

@endsection