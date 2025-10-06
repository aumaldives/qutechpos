<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('superadmin::lang.pricing') - IsleBooks POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            margin-bottom: 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-links a {
            margin-left: 20px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .pricing-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .pricing-header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }
        
        .pricing-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .pricing-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 30px;
        }
        
        .free-trial-banner {
            background: rgba(46, 204, 113, 0.2);
            border: 2px solid rgba(46, 204, 113, 0.5);
            border-radius: 50px;
            padding: 15px 30px;
            display: inline-block;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .free-trial-banner:hover {
            background: rgba(46, 204, 113, 0.3);
            transform: translateY(-2px);
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin: 40px 0;
            align-items: stretch;
        }
        
        .pricing-card {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .pricing-card.popular {
            border: 3px solid #2ecc71;
            transform: scale(1.05);
        }
        
        .pricing-card.flexible {
            border: 3px solid #f39c12;
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
            position: relative;
        }
        
        .card-header.popular {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .card-header.flexible {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #e74c3c;
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .plan-period {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .per-location-info {
            background: rgba(255,255,255,0.2);
            margin: 15px 0;
            padding: 15px;
            border-radius: 10px;
        }
        
        .location-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .location-btn {
            background: rgba(255,255,255,0.3);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .location-btn:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .location-input {
            width: 60px;
            height: 36px;
            text-align: center;
            border: 2px solid white;
            border-radius: 8px;
            background: white;
            color: #333;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .calculated-total {
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 10px;
        }
        
        .card-body {
            padding: 30px 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .features-list {
            list-style: none;
            flex-grow: 1;
        }
        
        .features-list li {
            padding: 8px 0;
            display: flex;
            align-items: flex-start;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        
        .feature-icon {
            margin-right: 10px;
            margin-top: 2px;
            min-width: 16px;
        }
        
        .check-icon {
            color: #2ecc71;
        }
        
        .plus-icon {
            color: #f39c12;
        }
        
        .plus-header {
            background: rgba(46, 204, 113, 0.1);
            padding: 12px 15px;
            margin: -10px -15px 15px;
            border-radius: 8px;
            border-left: 4px solid #2ecc71;
        }
        
        .plus-header-text {
            color: #27ae60;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .card-footer {
            padding: 25px;
            margin-top: auto;
        }
        
        .cta-button {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.4);
        }
        
        .price-calculator {
            background: rgba(0,0,0,0.1);
            padding: 15px;
            margin: 15px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .plan-toggle, .currency-toggle {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 4px;
            display: inline-flex;
            margin: 10px;
        }
        
        .plan-toggle input, .currency-toggle input {
            display: none;
        }
        
        .plan-toggle label, .currency-toggle label {
            padding: 12px 24px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
            font-weight: 500;
        }
        
        .plan-toggle input:checked + label, .currency-toggle input:checked + label {
            background: white;
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .pricing-header h1 {
                font-size: 2rem;
            }
            
            .pricing-card.popular {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="logo">
                <a href="/">
                    <img src="/uploads/img/logo.png" alt="IsleBooks POS" style="height: 40px;">
                </a>
            </div>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="https://islebooks.mv/contact-us" target="_blank">Contact Us</a>
            </div>
        </div>
    </div>

    <div class="pricing-container">
        <div class="pricing-header">
            <h1>Choose Your Perfect Plan</h1>
            <p>Flexible pricing for businesses of all sizes. Start with a free trial or choose the plan that fits your needs.</p>
            
            <div class="plan-toggle">
                <input type="radio" id="monthly" name="billing" checked>
                <input type="radio" id="annual" name="billing">
                <label for="monthly">Monthly</label>
                <label for="annual">Annual <small style="background: #e74c3c; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 5px;">Save 20%</small></label>
            </div>
            
            <div class="currency-toggle">
                <input type="radio" id="usd" name="currency" checked>
                <input type="radio" id="mvr" name="currency">
                <label for="usd">USD</label>
                <label for="mvr">MVR</label>
            </div>
            
            <br><br>
            <a href="/business/register?package=16" class="free-trial-banner">
                <i class="fas fa-rocket"></i> Start Your 7-Day Free Trial
            </a>
        </div>

        <div class="pricing-grid">
            @php
                // Get visible packages and sort by price
                $visible_packages = collect($packages)
                    ->filter(function($pkg) {
                        return $pkg->is_private != 1 && 
                               $pkg->restrict_date === null && 
                               $pkg->id != 16;
                    })
                    ->sortBy(function($pkg) {
                        return $pkg->is_per_location_pricing ? $pkg->price_per_location : $pkg->price;
                    })
                    ->values();
            @endphp

            @foreach($visible_packages as $index => $package)
                @php
                    $previous_package = $index > 0 ? $visible_packages[$index - 1] : null;
                    $is_popular = str_contains(strtolower($package->name), 'premier');
                    $is_flexible = $package->is_per_location_pricing;
                @endphp

                <div class="pricing-card @if($is_popular) popular @elseif($is_flexible) flexible @endif">
                    @if($is_popular)
                        <div class="popular-badge">Most Popular</div>
                    @elseif($is_flexible)
                        <div class="popular-badge" style="background: #f39c12;">Flexible</div>
                    @endif

                    <div class="card-header @if($is_popular) popular @elseif($is_flexible) flexible @endif">
                        <div class="plan-name">{{ $package->name }}</div>
                        
                        @if($package->is_per_location_pricing)
                            <div class="per-location-info">
                                <div class="usd-pricing">
                                    <div class="plan-price">${{ $package->price_per_location }}</div>
                                    <div class="plan-period">per location/month</div>
                                </div>
                                <div class="mvr-pricing" style="display: none;">
                                    <div class="plan-price">MVR {{ number_format($package->price_per_location * 15.42, 0) }}</div>
                                    <div class="plan-period">per location/month</div>
                                </div>
                                
                                <div class="price-calculator">
                                    <div style="margin-bottom: 10px; font-size: 0.9rem;">Choose locations:</div>
                                    <div class="location-selector">
                                        <button type="button" class="location-btn" onclick="adjustLocation({{ $package->id }}, -1)">−</button>
                                        <input type="number" 
                                               class="location-input" 
                                               id="location-input-{{ $package->id }}"
                                               data-package-id="{{ $package->id }}"
                                               data-price-per-location="{{ $package->price_per_location }}"
                                               min="{{ $package->min_locations }}"
                                               @if($package->max_locations > 0) max="{{ $package->max_locations }}" @else max="999" @endif
                                               value="{{ $package->min_locations }}"
                                               onchange="updatePrice({{ $package->id }})"
                                               oninput="updatePrice({{ $package->id }})">
                                        <button type="button" class="location-btn" onclick="adjustLocation({{ $package->id }}, 1)">+</button>
                                    </div>
                                    <div class="calculated-total">
                                        <span class="total-usd">${{ $package->calculatePrice($package->min_locations) }}</span>
                                        <span class="total-mvr" style="display: none;">MVR {{ number_format($package->calculatePrice($package->min_locations) * 15.42, 0) }}</span>
                                        <span style="font-size: 0.8rem; opacity: 0.8;">/month</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="usd-pricing">
                                @if($package->price != 0)
                                    <div class="plan-price">${{ $package->price }}</div>
                                    <div class="plan-period">per {{ $package->interval_count }} {{ $package->interval }}</div>
                                @else
                                    <div class="plan-price">FREE</div>
                                    <div class="plan-period">{{ $package->interval_count }} {{ $package->interval }} trial</div>
                                @endif
                            </div>
                            <div class="mvr-pricing" style="display: none;">
                                @if($package->price != 0)
                                    <div class="plan-price">MVR {{ number_format($package->price * 15.42, 0) }}</div>
                                    <div class="plan-period">per {{ $package->interval_count }} {{ $package->interval }}</div>
                                @else
                                    <div class="plan-price">FREE</div>
                                    <div class="plan-period">{{ $package->interval_count }} {{ $package->interval }} trial</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="card-body">
                        <ul class="features-list">
                            @if($previous_package)
                                <li class="plus-header">
                                    <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                    <span class="plus-header-text">Everything in {{ $previous_package->name }} PLUS:</span>
                                </li>
                                
                                @php
                                    $prev = $previous_package;
                                    $curr = $package;
                                @endphp
                                
                                <!-- Show incremental improvements -->
                                @if($curr->location_count > $prev->location_count || ($curr->location_count == 0 && $prev->location_count > 0))
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>
                                            @if($curr->location_count == 0)
                                                Unlimited locations
                                            @else
                                                {{ $curr->location_count }} locations
                                            @endif
                                        </strong></span>
                                    </li>
                                @endif
                                
                                @if($curr->user_count > $prev->user_count || ($curr->user_count == 0 && $prev->user_count > 0))
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>
                                            @if($curr->user_count == 0)
                                                Unlimited users
                                            @else
                                                {{ number_format($curr->user_count) }} users
                                            @endif
                                        </strong></span>
                                    </li>
                                @endif
                                
                                @if($curr->product_count > $prev->product_count || ($curr->product_count == 0 && $prev->product_count > 0))
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>
                                            @if($curr->product_count == 0)
                                                Unlimited products
                                            @else
                                                {{ number_format($curr->product_count) }} products
                                            @endif
                                        </strong></span>
                                    </li>
                                @endif
                                
                                @if($curr->is_per_location_pricing && !$prev->is_per_location_pricing)
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>Flexible per-location pricing</strong></span>
                                    </li>
                                @endif
                                
                                <!-- Module differences -->
                                @if(!empty($curr->custom_permissions) && !empty($prev->custom_permissions))
                                    @php
                                        $new_modules = array_diff_key(
                                            array_filter($curr->custom_permissions), 
                                            array_filter($prev->custom_permissions)
                                        );
                                    @endphp
                                    @foreach($new_modules as $module => $enabled)
                                        @if($enabled && isset($permission_formatted[$module]) && !in_array($module, ['plasticbag_module', 'ageingreport_module']))
                                            <li>
                                                <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                                <span><strong>{{ $permission_formatted[$module] ?? $module }}</strong></span>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                                
                                @if($curr->price >= 40 && $prev->price < 40)
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>Priority support</strong></span>
                                    </li>
                                    <li>
                                        <i class="fas fa-plus-circle feature-icon plus-icon"></i>
                                        <span><strong>Advanced analytics</strong></span>
                                    </li>
                                @endif
                                
                            @else
                                <!-- Base plan - show all features -->
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span><strong>{{ $package->location_count == 0 ? 'Unlimited' : $package->location_count }} business locations</strong></span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span><strong>{{ $package->user_count == 0 ? 'Unlimited' : $package->user_count }} users</strong></span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span><strong>{{ $package->product_count == 0 ? 'Unlimited' : number_format($package->product_count) }} products</strong></span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span><strong>{{ $package->invoice_count == 0 ? 'Unlimited' : number_format($package->invoice_count) }} invoices</strong></span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Point of sale system</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Inventory management</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Customer management</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Payment collection</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Sales reporting</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Barcode support</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Receipt printing</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle feature-icon check-icon"></i>
                                    <span>Tax management</span>
                                </li>
                                
                                @if(!empty($package->custom_permissions))
                                    @foreach($package->custom_permissions as $permission => $value)
                                        @if($value && isset($permission_formatted[$permission]) && !in_array($permission, ['plasticbag_module', 'ageingreport_module']))
                                            <li>
                                                <i class="fas fa-check-circle feature-icon check-icon"></i>
                                                <span>{{ $permission_formatted[$permission] ?? $permission }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                            
                            @if($package->trial_days != 0)
                                <li>
                                    <i class="fas fa-gift feature-icon" style="color: #e74c3c;"></i>
                                    <span><strong style="color: #e74c3c;">{{ $package->trial_days }}-day free trial</strong></span>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div class="card-footer">
                        @if($package->enable_custom_link == 1)
                            <a href="{{ $package->custom_link }}" class="cta-button btn-primary">
                                {{ $package->custom_link_text }}
                            </a>
                        @elseif($package->is_per_location_pricing)
                            <a href="javascript:void(0)" class="cta-button btn-warning" onclick="subscribeToPerLocation({{ $package->id }})">
                                Get Started
                            </a>
                        @else
                            <a href="/business/register?package={{ $package->id }}" class="cta-button @if($is_popular) btn-success @else btn-primary @endif">
                                @if($package->price != 0)
                                    Register & Subscribe
                                @else
                                    Start Free Trial
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        // Currency toggle
        document.querySelectorAll('input[name="currency"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const isUSD = this.id === 'usd';
                document.querySelectorAll('.usd-pricing').forEach(el => el.style.display = isUSD ? 'block' : 'none');
                document.querySelectorAll('.mvr-pricing').forEach(el => el.style.display = isUSD ? 'none' : 'block');
            });
        });

        // Per-location pricing
        function updatePrice(packageId) {
            const input = document.getElementById(`location-input-${packageId}`);
            const quantity = parseInt(input.value) || 1;
            const pricePerLocation = parseFloat(input.dataset.pricePerLocation);
            
            const min = parseInt(input.min) || 1;
            const max = parseInt(input.max) || 999;
            
            if (quantity < min) {
                input.value = min;
                return updatePrice(packageId);
            }
            if (quantity > max) {
                input.value = max;
                return updatePrice(packageId);
            }
            
            const totalUSD = pricePerLocation * quantity;
            const totalMVR = totalUSD * 15.42;
            
            document.querySelector(`#location-input-${packageId}`).closest('.card-header').querySelector('.total-usd').textContent = `$${totalUSD.toFixed(2)}`;
            document.querySelector(`#location-input-${packageId}`).closest('.card-header').querySelector('.total-mvr').textContent = `MVR ${Math.round(totalMVR)}`;
        }

        function adjustLocation(packageId, change) {
            const input = document.getElementById(`location-input-${packageId}`);
            const newValue = parseInt(input.value) + change;
            const min = parseInt(input.min) || 1;
            const max = parseInt(input.max) || 999;
            
            if (newValue >= min && newValue <= max) {
                input.value = newValue;
                updatePrice(packageId);
            }
        }

        function subscribeToPerLocation(packageId) {
            const quantity = document.getElementById(`location-input-${packageId}`).value;
            const pricePerLocation = document.getElementById(`location-input-${packageId}`).dataset.pricePerLocation;
            const total = (pricePerLocation * quantity).toFixed(2);
            
            if (confirm(`Subscribe to ${quantity} location(s) for $${total}/month?`)) {
                window.location.href = `/subscription/${packageId}/pay?location_quantity=${quantity}&location_quantity_confirmed=1`;
            }
        }

        // Initialize per-location calculations
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.location-input').forEach(input => {
                updatePrice(input.dataset.packageId);
            });
        });
    </script>
</body>
</html>