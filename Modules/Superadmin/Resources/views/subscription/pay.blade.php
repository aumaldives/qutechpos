@extends($layout)

@section('title', __('superadmin::lang.subscription'))

@section('content')

<!-- Main content -->
<section class="content">

	@include('superadmin::layouts.partials.currency')

	<div class="row justify-content-center">
		<div class="col-lg-10">
			<!-- Package Summary Card -->
			<div class="modern-package-card">
				<div class="package-header">
					<div class="package-icon">
						<i class="fas fa-crown"></i>
					</div>
					<div class="package-title">
						<h2>{{ $package->name }}</h2>
						<p>{{ $package->description ?? __('superadmin::lang.subscription_package') }}</p>
					</div>
					<div class="package-price">
						@if($package->is_per_location_pricing)
							@php
								$selected_quantity = request()->input('location_quantity', $package->min_locations);
								$calculated_price = $package->calculatePrice($selected_quantity);
							@endphp
							<div class="price-amount">
								<span class="display_currency" data-currency_symbol="true">{{ $calculated_price }}</span>
							</div>
							<div class="price-breakdown">
								<small>{{ $package->price_per_location }} × {{ $selected_quantity }} @lang('superadmin::lang.locations')</small>
							</div>
							<div class="price-period">
								/ {{ $package->interval_count }} {{ ucfirst($package->interval) }}
							</div>
						@else
							<div class="price-amount">
								<span class="display_currency" data-currency_symbol="true">{{ $package->price }}</span>
							</div>
							<div class="price-period">
								/ {{ $package->interval_count }} {{ ucfirst($package->interval) }}
							</div>
						@endif
						<div class="auto-renewal">
							<i class="fas fa-sync-alt"></i> {{ __('superadmin::lang.auto_renewal') }}
						</div>
					</div>
				</div>
				
				<div class="package-features">
					<div class="features-grid">
						<div class="feature-item">
							<div class="feature-icon">
								<i class="fas fa-map-marker-alt"></i>
							</div>
							<div class="feature-content">
								<div class="feature-label">{{ __('business.business_locations') }}</div>
								<div class="feature-value">
									@if($package->is_per_location_pricing)
										@php $selected_quantity = request()->input('location_quantity', $package->min_locations); @endphp
										<span class="limited-badge">{{ $selected_quantity }}</span>
										<small class="text-muted">({{ __('superadmin::lang.selected') }})</small>
									@elseif($package->location_count == 0)
										<span class="unlimited-badge">{{ __('superadmin::lang.unlimited') }}</span>
									@else
										<span class="limited-badge">{{ $package->location_count }}</span>
									@endif
								</div>
							</div>
						</div>
						
						<div class="feature-item">
							<div class="feature-icon">
								<i class="fas fa-users"></i>
							</div>
							<div class="feature-content">
								<div class="feature-label">{{ __('superadmin::lang.users') }}</div>
								<div class="feature-value">
									@if($package->user_count == 0)
										<span class="unlimited-badge">{{ __('superadmin::lang.unlimited') }}</span>
									@else
										<span class="limited-badge">{{ $package->user_count }}</span>
									@endif
								</div>
							</div>
						</div>
						
						<div class="feature-item">
							<div class="feature-icon">
								<i class="fas fa-box"></i>
							</div>
							<div class="feature-content">
								<div class="feature-label">{{ __('superadmin::lang.products') }}</div>
								<div class="feature-value">
									@if($package->product_count == 0)
										<span class="unlimited-badge">{{ __('superadmin::lang.unlimited') }}</span>
									@else
										<span class="limited-badge">{{ $package->product_count }}</span>
									@endif
								</div>
							</div>
						</div>
						
						<div class="feature-item">
							<div class="feature-icon">
								<i class="fas fa-file-invoice"></i>
							</div>
							<div class="feature-content">
								<div class="feature-label">{{ __('superadmin::lang.invoices') }}</div>
								<div class="feature-value">
									@if($package->invoice_count == 0)
										<span class="unlimited-badge">{{ __('superadmin::lang.unlimited') }}</span>
									@else
										<span class="limited-badge">{{ $package->invoice_count }}</span>
									@endif
								</div>
							</div>
						</div>
					</div>
					
					@if($package->trial_days != 0)
						<div class="trial-banner">
							<div class="trial-icon">
								<i class="fas fa-gift"></i>
							</div>
							<div class="trial-content">
								<strong>{{ __('superadmin::lang.trial_period') }}:</strong> 
								{{ $package->trial_days }} {{ __('superadmin::lang.trial_days') }} Free Trial
							</div>
						</div>
					@endif
				</div>
			</div>

			<!-- Payment Method Selection -->
			<div class="card card-info">
				<div class="card-header">
					<h3 class="card-title">
						<i class="fa fa-credit-card"></i> 
						{{ __('superadmin::lang.choose_payment_method') }}
					</h3>
				</div>
				<div class="card-body">
					<div class="payment-methods">
						@foreach($gateways as $k => $v)
							@php
								$method_info = [
									'stripe' => [
										'icon' => 'fa-credit-card',
										'title' => __('superadmin::lang.credit_debit_card'),
										'description' => __('superadmin::lang.pay_with_card_description'),
										'logos' => ['visa', 'mastercard', 'amex', 'discover']
									],
									'offline' => [
										'icon' => 'fa-university',
										'title' => __('superadmin::lang.bank_transfer'),
										'description' => __('superadmin::lang.pay_with_bank_description'),
										'logos' => ['bank']
									],
									'paypal' => [
										'icon' => 'fa-paypal',
										'title' => 'PayPal',
										'description' => __('superadmin::lang.pay_with_paypal_description'),
										'logos' => ['paypal']
									],
									'razorpay' => [
										'icon' => 'fa-credit-card-alt',
										'title' => 'Razorpay',
										'description' => __('superadmin::lang.pay_with_razorpay_description'),
										'logos' => ['razorpay']
									]
								];
								$info = $method_info[$k] ?? ['icon' => 'fa-credit-card', 'title' => $v, 'description' => '', 'logos' => []];
							@endphp
							
							<div class="payment-method-option" data-method="{{ $k }}">
								<div class="method-header">
									<div class="method-radio">
										<input type="radio" name="payment_method" value="{{ $k }}" id="method_{{ $k }}">
										<label for="method_{{ $k }}"></label>
									</div>
									<div class="method-info">
										<div class="method-icon">
											<i class="fa {{ $info['icon'] }}"></i>
										</div>
										<div class="method-details">
											<h5>{{ $info['title'] }}</h5>
											<p>{{ $info['description'] }}</p>
										</div>
									</div>
									<div class="method-logos">
										@if($k == 'stripe')
											<!-- Visa -->
											<div class="card-logo">
												<svg width="40" height="24" viewBox="0 0 40 24" xmlns="http://www.w3.org/2000/svg">
													<rect width="40" height="24" rx="4" fill="#1A1F71"/>
													<text x="8" y="16" font-family="Arial, sans-serif" font-size="8" font-weight="bold" fill="white">VISA</text>
												</svg>
											</div>
											<!-- Mastercard -->
											<div class="card-logo">
												<svg width="40" height="24" viewBox="0 0 40 24" xmlns="http://www.w3.org/2000/svg">
													<rect width="40" height="24" rx="4" fill="#000"/>
													<circle cx="15" cy="12" r="6" fill="#EB001B"/>
													<circle cx="25" cy="12" r="6" fill="#FF5F00"/>
													<path d="M20 7.5c1.2 1.3 2 3.1 2 5s-.8 3.7-2 5c-1.2-1.3-2-3.1-2-5s.8-3.7 2-5z" fill="#FF0000"/>
												</svg>
											</div>
											<!-- American Express -->
											<div class="card-logo">
												<svg width="40" height="24" viewBox="0 0 40 24" xmlns="http://www.w3.org/2000/svg">
													<rect width="40" height="24" rx="4" fill="#006FCF"/>
													<text x="8" y="16" font-family="Arial, sans-serif" font-size="7" font-weight="bold" fill="white">AMEX</text>
												</svg>
											</div>
										@elseif($k == 'paypal')
											<div class="card-logo paypal-logo">
												<svg width="40" height="24" viewBox="0 0 40 24" xmlns="http://www.w3.org/2000/svg">
													<rect width="40" height="24" rx="4" fill="#0070BA"/>
													<text x="6" y="16" font-family="Arial, sans-serif" font-size="7" font-weight="bold" fill="white">PayPal</text>
												</svg>
											</div>
										@elseif($k == 'offline')
											<!-- Bank Transfer Icons -->
											<div class="bank-logo">
												<img src="/img/bml.png" alt="BML">
											</div>
											<div class="bank-logo">
												<img src="/img/MIB.png" alt="MIB">
											</div>
										@endif
									</div>
								</div>
								
								<!-- Payment Form Container (Hidden initially) -->
								<div class="method-form" id="form_{{ $k }}" style="display: none;">
									@php 
										$view = 'superadmin::subscription.partials.pay_'.$k;
									@endphp
									@includeIf($view)
								</div>
							</div>
						@endforeach
					</div>

					@if(empty($gateways))
						<div class="alert alert-warning text-center">
							<i class="fa fa-exclamation-triangle"></i>
							{{ __('superadmin::lang.no_payment_methods_available') }}
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</section>

<style>
/* Modern Package Card Styles */
.modern-package-card {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border-radius: 20px;
	overflow: hidden;
	box-shadow: 0 10px 30px rgba(0,0,0,0.2);
	margin-bottom: 2rem;
	color: white;
	position: relative;
}

.modern-package-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
	pointer-events: none;
}

.package-header {
	display: flex;
	align-items: center;
	padding: 2rem;
	background: rgba(255,255,255,0.1);
	backdrop-filter: blur(10px);
	position: relative;
	z-index: 2;
}

.package-icon {
	margin-right: 1.5rem;
	font-size: 2.5rem;
	color: #ffd700;
}

.package-title {
	flex: 1;
}

.package-title h2 {
	margin: 0;
	font-size: 2rem;
	font-weight: 700;
	text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.package-title p {
	margin: 0.5rem 0 0 0;
	opacity: 0.9;
	font-size: 1.1rem;
}

.package-price {
	text-align: right;
	min-width: 200px;
}

.price-amount {
	font-size: 2.5rem;
	font-weight: 800;
	line-height: 1;
	text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.price-period {
	font-size: 1.1rem;
	opacity: 0.9;
	margin-bottom: 0.5rem;
}

.auto-renewal {
	background: rgba(255,255,255,0.2);
	padding: 0.5rem 1rem;
	border-radius: 20px;
	font-size: 0.9rem;
	display: inline-block;
	backdrop-filter: blur(5px);
}

.package-features {
	padding: 2rem;
	background: rgba(255,255,255,0.95);
	color: #2c3e50;
	position: relative;
	z-index: 2;
}

.features-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 1.5rem;
	margin-bottom: 1.5rem;
}

.feature-item {
	display: flex;
	align-items: center;
	background: white;
	padding: 1.5rem;
	border-radius: 12px;
	box-shadow: 0 4px 15px rgba(0,0,0,0.1);
	transition: all 0.3s ease;
	border-left: 4px solid #667eea;
}

.feature-item:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.feature-icon {
	margin-right: 1rem;
	font-size: 1.8rem;
	color: #667eea;
	width: 50px;
	text-align: center;
}

.feature-content {
	flex: 1;
}

.feature-label {
	font-weight: 600;
	color: #2c3e50;
	margin-bottom: 0.5rem;
	font-size: 1rem;
}

.feature-value {
	font-size: 1.1rem;
}

.unlimited-badge {
	background: linear-gradient(135deg, #27ae60, #2ecc71);
	color: white;
	padding: 0.4rem 1rem;
	border-radius: 20px;
	font-weight: 600;
	font-size: 0.9rem;
	display: inline-block;
	box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
}

.limited-badge {
	background: linear-gradient(135deg, #3498db, #2980b9);
	color: white;
	padding: 0.4rem 1rem;
	border-radius: 20px;
	font-weight: 600;
	font-size: 0.9rem;
	display: inline-block;
	box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
}

.trial-banner {
	background: linear-gradient(135deg, #f39c12, #e67e22);
	padding: 1rem 1.5rem;
	border-radius: 12px;
	display: flex;
	align-items: center;
	color: white;
	box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
}

.trial-icon {
	margin-right: 1rem;
	font-size: 1.5rem;
}

.trial-content {
	font-size: 1.1rem;
	font-weight: 500;
}

@media (max-width: 768px) {
	.package-header {
		flex-direction: column;
		text-align: center;
		padding: 1.5rem;
	}
	
	.package-icon {
		margin-right: 0;
		margin-bottom: 1rem;
	}
	
	.package-price {
		margin-top: 1rem;
		text-align: center;
		min-width: auto;
	}
	
	.features-grid {
		grid-template-columns: 1fr;
		gap: 1rem;
	}
	
	.feature-item {
		padding: 1rem;
	}
}

.pricing-card {
	background: #f8f9fc;
	padding: 20px;
	border-radius: 8px;
	border: 2px solid #e3e6f0;
}

.payment-methods {
	space-y: 15px;
}

.payment-method-option {
	border: 1px solid #e3e6f0;
	border-radius: 8px;
	margin-bottom: 15px;
	transition: all 0.3s ease;
	background: #fff;
}

.payment-method-option:hover {
	box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	border-color: #5a5c69;
}

.payment-method-option.active {
	border-color: #007bff;
	box-shadow: 0 2px 8px rgba(0,123,255,0.2);
}

.method-header {
	display: flex;
	align-items: center;
	padding: 20px;
	cursor: pointer;
	position: relative;
	overflow: visible;
}

.method-radio {
	margin-right: 15px;
}

.method-radio input[type="radio"] {
	display: none;
}

.method-radio label {
	width: 20px;
	height: 20px;
	border: 2px solid #ddd;
	border-radius: 50%;
	display: block;
	cursor: pointer;
	position: relative;
}

.method-radio input[type="radio"]:checked + label {
	border-color: #007bff;
}

.method-radio input[type="radio"]:checked + label::after {
	content: '';
	width: 10px;
	height: 10px;
	background: #007bff;
	border-radius: 50%;
	position: absolute;
	top: 3px;
	left: 3px;
}

.method-info {
	display: flex;
	align-items: center;
	flex: 1;
}

.method-icon {
	margin-right: 15px;
	font-size: 24px;
	color: #5a5c69;
	width: 40px;
	text-align: center;
}

.method-details h5 {
	margin: 0;
	font-weight: 600;
	color: #5a5c69;
}

.method-details p {
	margin: 5px 0 0 0;
	color: #858796;
	font-size: 14px;
}

.method-logos {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 8px 0;
	min-height: 40px;
}

/* Different alignment for different payment methods */
.payment-method-option[data-method="stripe"] .method-logos,
.payment-method-option[data-method="paypal"] .method-logos {
	justify-content: flex-end;
}

.payment-method-option[data-method="offline"] .method-logos {
	justify-content: center;
	flex-wrap: wrap;
}

.card-logo {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	border-radius: 4px;
	overflow: hidden;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	transition: all 0.2s ease;
	background: white;
	border: 1px solid #e3e6f0;
	position: relative;
}

.card-logo:hover {
	transform: translateY(-1px);
	box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.card-logo svg {
	display: block;
	width: 40px;
	height: 24px;
	flex-shrink: 0;
}

.paypal-logo {
	padding: 4px 8px;
	background: #0070BA;
	color: white;
	border: none;
	min-width: 50px;
	height: 26px;
}

.bank-logo {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	background: white;
	border: 1px solid #e3e6f0;
	border-radius: 4px;
	padding: 6px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	transition: all 0.2s ease;
	width: 32px;
	height: 32px;
}

.bank-logo:hover {
	transform: translateY(-1px);
	box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.bank-logo img {
	max-height: 26px;
	max-width: 26px;
	object-fit: contain;
}

.method-form {
	border-top: 1px solid #e3e6f0;
	padding: 20px;
	background: #f8f9fc;
}

@media (max-width: 768px) {
	.method-header {
		flex-direction: column;
		align-items: flex-start;
		padding: 15px;
	}
	
	.method-info {
		width: 100%;
		margin-bottom: 10px;
	}
	
	.method-logos {
		width: 100%;
		justify-content: center;
		gap: 8px;
		padding: 6px 0;
		flex-wrap: wrap;
	}
	
	.card-logo svg {
		width: 35px;
		height: 21px;
	}
	
	.paypal-logo {
		min-width: 45px;
		height: 22px;
		padding: 3px 6px;
	}
	
	.bank-logo {
		padding: 4px;
		width: 28px;
		height: 28px;
	}
	
	.bank-logo img {
		max-height: 22px;
		max-width: 22px;
	}
	
	.stripe-payment-container {
		padding: 0 10px;
	}
	
	.pricing-card h2 {
		font-size: 1.5rem;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Payment method selection
	const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
	const methodOptions = document.querySelectorAll('.payment-method-option');
	
	paymentMethods.forEach(function(radio) {
		radio.addEventListener('change', function() {
			// Hide all forms
			document.querySelectorAll('.method-form').forEach(function(form) {
				form.style.display = 'none';
			});
			
			// Remove active class from all options
			methodOptions.forEach(function(option) {
				option.classList.remove('active');
			});
			
			if (this.checked) {
				// Show selected form
				const selectedForm = document.getElementById('form_' + this.value);
				if (selectedForm) {
					selectedForm.style.display = 'block';
				}
				
				// Add active class to selected option
				const selectedOption = this.closest('.payment-method-option');
				if (selectedOption) {
					selectedOption.classList.add('active');
				}
			}
		});
	});
	
	// Click on method header to select
	methodOptions.forEach(function(option) {
		const header = option.querySelector('.method-header');
		header.addEventListener('click', function() {
			const radio = option.querySelector('input[name="payment_method"]');
			if (radio) {
				radio.checked = true;
				radio.dispatchEvent(new Event('change'));
			}
		});
	});
});
</script>

@endsection