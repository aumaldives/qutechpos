@extends('layouts.app')
@section('title', __('superadmin::lang.payment_authentication'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fa fa-shield"></i> 
                    {{ __('superadmin::lang.secure_authentication_required') }}
                </h4>
            </div>
            <div class="card-body text-center">
                <div id="authentication-message">
                    <div class="mb-4">
                        <i class="fa fa-credit-card fa-3x text-primary"></i>
                    </div>
                    <h5>{{ __('superadmin::lang.authenticating_payment') }}</h5>
                    <p class="text-muted">
                        {{ __('superadmin::lang.3ds_authentication_message') }}
                    </p>
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{{ __('lang_v1.loading') }}...</span>
                    </div>
                </div>

                <div id="success-message" style="display: none;">
                    <div class="mb-4">
                        <i class="fa fa-check-circle fa-3x text-success"></i>
                    </div>
                    <h5 class="text-success">{{ __('superadmin::lang.payment_authenticated_successfully') }}</h5>
                    <p class="text-muted">
                        {{ __('superadmin::lang.redirecting_to_subscription') }}
                    </p>
                </div>

                <div id="error-message" style="display: none;">
                    <div class="mb-4">
                        <i class="fa fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>
                    <h5 class="text-danger">{{ __('superadmin::lang.authentication_failed') }}</h5>
                    <p class="text-muted" id="error-text"></p>
                    <a href="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index']) }}" 
                       class="btn btn-primary">
                        {{ __('lang_v1.back_to_subscriptions') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Package Summary -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('superadmin::lang.subscription_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-8">
                        <h6>{{ $package->name }}</h6>
                        <p class="text-muted mb-0">{{ $package->description ?? __('superadmin::lang.subscription_package') }}</p>
                    </div>
                    <div class="col-sm-4 text-right">
                        <h5 class="mb-0">
                            <span class="display_currency" data-currency_symbol="true">{{ $package->price }}</span>
                        </h5>
                        <small class="text-muted">
                            / {{ $package->interval_count }} {{ __('lang_v1.' . $package->interval) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://js.stripe.com/v3/"></script>
<script>
    // Initialize Stripe
    var stripe = Stripe('{{ env("STRIPE_PUB_KEY") }}');
    
    // Confirm the payment on page load
    document.addEventListener('DOMContentLoaded', function() {
        var clientSecret = '{{ $payment_intent_client_secret }}';
        var subscriptionId = '{{ $subscription_id }}';
        var returnUrl = '{{ $return_url }}';
        
        // Confirm the payment with 3D Secure if required
        stripe.confirmCardPayment(clientSecret).then(function(result) {
            if (result.error) {
                // Payment failed
                showError(result.error.message);
            } else {
                // Payment succeeded
                showSuccess();
                
                // Redirect to confirmation page
                setTimeout(function() {
                    window.location.href = returnUrl + '?subscription_id=' + subscriptionId;
                }, 2000);
            }
        });
    });
    
    function showSuccess() {
        document.getElementById('authentication-message').style.display = 'none';
        document.getElementById('success-message').style.display = 'block';
        document.getElementById('error-message').style.display = 'none';
    }
    
    function showError(message) {
        document.getElementById('authentication-message').style.display = 'none';
        document.getElementById('success-message').style.display = 'none';
        document.getElementById('error-message').style.display = 'block';
        document.getElementById('error-text').textContent = message;
    }
</script>
@endsection