@php
$code = strtolower($system_currency->code);
@endphp

<div class="col-md-12">
    <div class="stripe-payment-wrapper">
        <div class="row">
            <!-- Payment Form -->
            <div class="col-lg-8">
                <div class="payment-form-section">
                    <form id="stripe-payment-form" method="POST" action="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'], [$package->id]) }}">
                        {{ csrf_field() }}
                        <input type="hidden" name="gateway" value="{{ $k }}">
                        <input type="hidden" name="payment_method_id" id="payment-method-id">
                        
                        <div class="card payment-card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i class="fa fa-lock text-success"></i> 
                                    {{ __('superadmin::lang.secure_payment') }}
                                </h4>
                            </div>
                            <div class="card-body">
                                <!-- Customer Information Section -->
                                <div class="customer-info-section">
                                    <h6 class="section-title">{{ __('superadmin::lang.billing_information') }}</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="customer-name" class="form-label">
                                                    {{ __('superadmin::lang.full_name') }} 
                                                    <span class="required">*</span>
                                                </label>
                                                <input type="text" id="customer-name" class="form-control form-input" 
                                                       value="{{ trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) }}" 
                                                       placeholder="{{ __('superadmin::lang.enter_full_name') }}" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="customer-email" class="form-label">
                                                    {{ __('superadmin::lang.email_address') }} 
                                                    <span class="required">*</span>
                                                </label>
                                                <input type="email" id="customer-email" class="form-control form-input" 
                                                       value="{{ $user['email'] ?? '' }}" 
                                                       placeholder="{{ __('superadmin::lang.enter_email_address') }}" 
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Information Section -->
                                <div class="card-info-section">
                                    <h6 class="section-title">{{ __('superadmin::lang.card_information') }}</h6>
                                    <div class="form-group">
                                        <div id="card-element" class="stripe-card-element">
                                            <!-- Stripe Elements will create form elements here -->
                                        </div>
                                        <div id="card-errors" role="alert" class="error-message"></div>
                                    </div>
                                </div>

                                <!-- Recurring Subscription Notice -->
                                <div class="subscription-notice">
                                    <div class="notice-content">
                                        <div class="notice-icon">
                                            <i class="fa fa-sync-alt text-info"></i>
                                        </div>
                                        <div class="notice-text">
                                            <strong>{{ __('superadmin::lang.auto_renewal_enabled') }}</strong>
                                            <p class="mb-0">{{ __('superadmin::lang.auto_renewal_description') }}</p>
                                        </div>
                                        <div class="notice-toggle">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="auto-renewal" checked>
                                                <label class="custom-control-label" for="auto-renewal"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="submit-section">
                            <button type="submit" id="stripe-submit-btn" class="btn btn-subscribe" disabled>
                                <span id="btn-text">
                                    <i class="fa fa-shield-alt"></i>
                                    {{ __('superadmin::lang.subscribe_now') }}
                                </span>
                                <span id="btn-loading" class="btn-loading">
                                    <i class="fa fa-spinner fa-spin"></i>
                                    {{ __('superadmin::lang.processing_payment') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Payment Summary Sidebar -->
            <div class="col-lg-4">
                <div class="payment-summary-sidebar">
                    <div class="summary-card">
                        <div class="summary-header">
                            <h5 class="mb-0">
                                <i class="fa fa-receipt"></i> 
                                {{ __('superadmin::lang.order_summary') }}
                            </h5>
                        </div>
                        <div class="summary-body">
                            <div class="package-details">
                                <h6 class="package-name">{{ $package->name }}</h6>
                                <p class="package-description">{{ $package->description ?? __('superadmin::lang.subscription_package') }}</p>
                                
                                <div class="package-features">
                                    <div class="feature-item">
                                        <i class="fa fa-map-marker-alt text-success"></i>
                                        <span>{{ $package->location_count == 0 ? __('superadmin::lang.unlimited') : $package->location_count }} {{ __('superadmin::lang.locations') }}</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fa fa-users text-info"></i>
                                        <span>{{ $package->user_count == 0 ? __('superadmin::lang.unlimited') : $package->user_count }} {{ __('superadmin::lang.users') }}</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fa fa-cube text-warning"></i>
                                        <span>{{ $package->product_count == 0 ? __('superadmin::lang.unlimited') : $package->product_count }} {{ __('superadmin::lang.products') }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="price-breakdown">
                                <div class="price-row">
                                    <span class="price-label">{{ __('superadmin::lang.subscription_price') }}</span>
                                    <span class="price-value">
                                        <span class="display_currency" data-currency_symbol="true">{{ $package->price }}</span>
                                    </span>
                                </div>
                                <div class="price-row billing-cycle">
                                    <span class="price-label">{{ __('superadmin::lang.billing_cycle') }}</span>
                                    <span class="price-value">
                                        @php
                                            $intervalText = __('lang_v1.' . $package->interval);
                                            // Fix pluralization: if count is 1, remove 's' from end if present
                                            if ($package->interval_count == 1 && substr($intervalText, -1) === 's') {
                                                $intervalText = substr($intervalText, 0, -1);
                                            }
                                        @endphp
                                        {{ $package->interval_count }} {{ $intervalText }}
                                    </span>
                                </div>
                                @if($package->trial_days > 0)
                                <div class="price-row trial-info">
                                    <span class="price-label">
                                        <i class="fa fa-gift text-success"></i>
                                        {{ __('superadmin::lang.free_trial') }}
                                    </span>
                                    <span class="price-value">{{ $package->trial_days }} {{ __('superadmin::lang.days') }}</span>
                                </div>
                                @endif
                                <hr class="price-divider">
                                <div class="price-row total-row">
                                    <span class="price-label">{{ __('superadmin::lang.total_today') }}</span>
                                    <span class="price-value total-price">
                                        <span class="display_currency" data-currency_symbol="true">{{ $package->trial_days > 0 ? '0.00' : $package->price }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stripe Branding -->
                    <div class="stripe-branding-section">
                        <div class="branding-content">
                            <span class="powered-text">{{ __('superadmin::lang.powered_by') }}</span>
                            <div class="stripe-logo">
                                <svg width="50" height="21" viewBox="0 0 50 21" xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#635BFF" d="M6.17 9.02c0-.46.38-.84.84-.84s.84.38.84.84v8.12c0 .46-.38.84-.84.84s-.84-.38-.84-.84V9.02zm2.83 0c0-.46.38-.84.84-.84s.84.38.84.84v8.12c0 .46-.38.84-.84.84s-.84-.38-.84-.84V9.02zm2.83 0c0-.46.38-.84.84-.84s.84.38.84.84v8.12c0 .46-.38.84-.84.84s-.84-.38-.84-.84V9.02zm2.83 0c0-.46.38-.84.84-.84s.84.38.84.84v8.12c0 .46-.38.84-.84.84s-.84-.38-.84-.84V9.02z"/>
                                    <text x="18" y="12" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif" font-size="9" font-weight="500" fill="#635BFF">stripe</text>
                                </svg>
                            </div>
                        </div>
                        <p class="security-text">
                            <i class="fa fa-shield-alt text-success"></i>
                            {{ __('superadmin::lang.payment_security_notice') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Main Layout */
.stripe-payment-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.payment-form-section {
    padding-right: 20px;
}

.payment-summary-sidebar {
    padding-left: 20px;
    position: sticky;
    top: 20px;
}

/* Payment Card Styling */
.payment-card {
    border: 1px solid #e3e6f0;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-radius: 0.75rem;
    margin-bottom: 24px;
    margin-top: 60px;
}

.payment-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.75rem 0.75rem 0 0;
    padding: 20px;
}

.payment-card .card-body {
    padding: 24px;
}

/* Section Styling */
.customer-info-section,
.card-info-section {
    margin-bottom: 24px;
}

.section-title {
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e3e6f0;
}

/* Form Input Styling */
.form-label {
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 8px;
}

.required {
    color: #e74a3b;
}

.form-input {
    border: 2px solid #d1d3e2;
    border-radius: 0.5rem;
    padding: 12px 16px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-input:focus {
    border-color: #5a5c69;
    box-shadow: 0 0 0 0.2rem rgba(90, 92, 105, 0.15);
}

/* Stripe Card Element */
.stripe-card-element {
    background-color: white;
    padding: 16px;
    border: 2px solid #d1d3e2;
    border-radius: 0.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
}

.stripe-card-element:focus-within {
    border-color: #5a5c69;
    box-shadow: 0 0 0 0.2rem rgba(90, 92, 105, 0.15);
}

.stripe-card-element.StripeElement--invalid {
    border-color: #e74a3b;
}

.stripe-card-element.StripeElement--complete {
    border-color: #1cc88a;
}

.error-message {
    color: #e74a3b;
    font-size: 14px;
    margin-top: 8px;
    font-weight: 500;
}

/* Subscription Notice */
.subscription-notice {
    background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
    border: 1px solid #d1d3e2;
    border-radius: 0.5rem;
    padding: 20px;
    margin-top: 20px;
}

.notice-content {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.notice-icon {
    font-size: 24px;
    flex-shrink: 0;
    margin-top: 2px;
}

.notice-text {
    flex: 1;
    padding-right: 16px;
}

.notice-text strong {
    color: #5a5c69;
    display: block;
    margin-bottom: 6px;
    font-size: 16px;
}

.notice-text p {
    color: #858796;
    font-size: 14px;
    line-height: 1.4;
    margin: 0;
}

.notice-toggle {
    flex-shrink: 0;
    padding-left: 16px;
    margin-top: 4px;
}

.custom-control.custom-switch {
    min-height: 1.5rem;
    padding-left: 2.5rem;
}

.custom-control.custom-switch .custom-control-input:checked ~ .custom-control-label::after {
    background-color: #1cc88a;
    border-color: #1cc88a;
}

.custom-control.custom-switch .custom-control-label::before {
    background-color: #e3e6f0;
    border: 1px solid #d1d3e2;
}

.custom-control.custom-switch .custom-control-label::after {
    background-color: #fff;
    border: 1px solid #d1d3e2;
}

/* Submit Button */
.submit-section {
    text-align: center;
    padding: 24px 0;
}

.btn-subscribe {
    background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);
    border: none;
    color: white;
    padding: 16px 48px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 250px;
}

.btn-subscribe:hover {
    background: linear-gradient(135deg, #17a673 0%, #138f61 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
}

.btn-subscribe:disabled {
    background: #e3e6f0;
    color: #858796;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-loading {
    display: none;
}

/* Summary Sidebar */
.summary-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 24px;
    margin-top: 60px;
    overflow: hidden;
}

.summary-header {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    padding: 20px;
}

.summary-body {
    padding: 24px;
}

.package-details {
    margin-bottom: 24px;
}

.package-name {
    font-weight: 700;
    color: #5a5c69;
    margin-bottom: 8px;
}

.package-description {
    color: #858796;
    font-size: 14px;
    margin-bottom: 16px;
}

.package-features {
    margin-bottom: 20px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #5a5c69;
}

.feature-item i {
    width: 20px;
    text-align: center;
}

/* Price Breakdown */
.price-breakdown {
    background: #f8f9fc;
    padding: 20px;
    border-radius: 0.5rem;
    border: 1px solid #e3e6f0;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.price-row:last-child {
    margin-bottom: 0;
}

.price-label {
    color: #5a5c69;
    font-weight: 500;
}

.price-value {
    color: #5a5c69;
    font-weight: 600;
}

.total-row {
    font-size: 18px;
    font-weight: 700;
}

.total-price {
    color: #1cc88a;
}

.price-divider {
    border-color: #d1d3e2;
    margin: 16px 0;
}

.trial-info .price-label {
    color: #28a745;
}

/* Stripe Branding */
.stripe-branding-section {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 0.75rem;
    padding: 20px;
    text-align: center;
}

.branding-content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 12px;
}

.powered-text {
    color: #858796;
    font-size: 12px;
    font-weight: 500;
}

.security-text {
    color: #858796;
    font-size: 12px;
    margin: 0;
}


/* Responsive Design */
@media (max-width: 991px) {
    .payment-form-section,
    .payment-summary-sidebar {
        padding: 0;
    }
    
    .payment-summary-sidebar {
        margin-top: 32px;
        position: static;
    }
    
    /* Stack elements vertically on tablet and below */
    .stripe-payment-wrapper .row {
        flex-direction: column;
    }
    
    .col-lg-8,
    .col-lg-4 {
        max-width: 100%;
        flex: 0 0 100%;
    }
    
    /* Reverse order so summary appears above form on mobile */
    .col-lg-4 {
        order: -1;
        margin-bottom: 24px;
    }
    
    .col-lg-8 {
        order: 1;
    }
}

@media (max-width: 768px) {
    .stripe-payment-wrapper {
        padding: 0 10px;
    }
    
    .payment-card .card-body {
        padding: 16px;
    }
    
    .summary-body {
        padding: 16px;
    }
    
    .btn-subscribe {
        width: 100%;
        padding: 16px 24px;
        font-size: 16px;
    }
    
    
    .notice-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
        align-items: center;
    }
    
    .notice-text {
        padding-right: 0;
        text-align: center;
    }
    
    .notice-toggle {
        padding-left: 0;
        margin-top: 0;
    }
}

@media (max-width: 576px) {
    .branding-content {
        flex-direction: column;
        gap: 4px;
    }
    
    .feature-item {
        font-size: 13px;
    }
    
    .price-row {
        font-size: 14px;
    }
    
    .total-row {
        font-size: 16px;
    }
}
</style>

<script src="https://js.stripe.com/v3/"></script>
<script>
// Initialize Stripe
var stripe = Stripe('{{ env("STRIPE_PUB_KEY") }}');
var elements = stripe.elements({
    appearance: {
        theme: 'stripe',
        variables: {
            colorPrimary: '#0570de',
            colorBackground: '#ffffff',
            colorText: '#30313d',
            colorDanger: '#df1b41',
            fontFamily: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            spacingUnit: '4px',
            borderRadius: '8px'
        }
    }
});

// Create card element with improved styling
var cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#424770',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
        invalid: {
            color: '#9e2146',
        },
    },
    hidePostalCode: true
});

// Mount card element
cardElement.mount('#card-element');

// Handle real-time validation errors from the card Element
cardElement.on('change', function(event) {
    var displayError = document.getElementById('card-errors');
    var submitButton = document.getElementById('stripe-submit-btn');
    
    if (event.error) {
        displayError.textContent = event.error.message;
        displayError.style.display = 'block';
        submitButton.disabled = true;
    } else {
        displayError.textContent = '';
        displayError.style.display = 'none';
        submitButton.disabled = !event.complete;
    }
});

// Handle auto-renewal toggle
document.getElementById('auto-renewal').addEventListener('change', function() {
    var autoRenewal = this.checked;
    var form = document.getElementById('stripe-payment-form');
    
    // Add hidden input for auto_renewal preference
    var existingInput = form.querySelector('input[name="auto_renewal"]');
    if (existingInput) {
        existingInput.value = autoRenewal ? '1' : '0';
    } else {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'auto_renewal';
        input.value = autoRenewal ? '1' : '0';
        form.appendChild(input);
    }
});

// Handle form submission
var form = document.getElementById('stripe-payment-form');
form.addEventListener('submit', function(event) {
    event.preventDefault();
    
    var submitButton = document.getElementById('stripe-submit-btn');
    var btnText = document.getElementById('btn-text');
    var btnLoading = document.getElementById('btn-loading');
    var errorElement = document.getElementById('card-errors');
    
    // Disable submit button and show loading state
    submitButton.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'inline';
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    
    // Get customer information
    var customerName = document.getElementById('customer-name').value;
    var customerEmail = document.getElementById('customer-email').value;
    
    // Validate required fields
    if (!customerName.trim()) {
        showError('{{ __("superadmin::lang.name_required") }}');
        return;
    }
    
    if (!customerEmail.trim()) {
        showError('{{ __("superadmin::lang.email_required") }}');
        return;
    }
    
    // Create payment method
    stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
        billing_details: {
            name: customerName,
            email: customerEmail,
        },
    }).then(function(result) {
        if (result.error) {
            showError(result.error.message);
        } else {
            // Payment method created successfully
            document.getElementById('payment-method-id').value = result.paymentMethod.id;
            
            // Add customer info as hidden fields
            var nameInput = document.createElement('input');
            nameInput.type = 'hidden';
            nameInput.name = 'customer_name';
            nameInput.value = customerName;
            form.appendChild(nameInput);
            
            var emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'customer_email';
            emailInput.value = customerEmail;
            form.appendChild(emailInput);
            
            // Submit the form
            form.submit();
        }
    });
    
    function showError(message) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        submitButton.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
});

// Auto-resize card element on mobile
function adjustCardElement() {
    if (window.innerWidth <= 768) {
        cardElement.update({
            style: {
                base: {
                    fontSize: '16px',
                    lineHeight: '24px'
                }
            }
        });
    }
}

window.addEventListener('resize', adjustCardElement);
adjustCardElement();

// Initialize auto-renewal toggle
document.addEventListener('DOMContentLoaded', function() {
    var autoRenewalToggle = document.getElementById('auto-renewal');
    if (autoRenewalToggle) {
        autoRenewalToggle.dispatchEvent(new Event('change'));
    }
});
</script>