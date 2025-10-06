<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('superadmin::lang.pricing') - IsleBooks POS</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/font-awesome.min.css') }}">
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --dark-color: #2c3e50;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px 0;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            margin-bottom: 40px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .pricing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }

        .pricing-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .pricing-header p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .currency-toggle {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 5px;
            display: inline-flex;
            margin: 30px auto 0;
            backdrop-filter: blur(10px);
        }

        .currency-toggle input[type="radio"] {
            display: none;
        }

        .currency-toggle label {
            padding: 12px 30px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-weight: 500;
        }

        .currency-toggle input[type="radio"]:checked + label {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .pricing-table {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 40px;
        }

        .pricing-table-header {
            background: var(--dark-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .pricing-table-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 600;
        }

        .pricing-table-content {
            overflow-x: auto;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: 300px repeat(auto-fit, minmax(200px, 1fr));
            gap: 0;
            min-width: 800px;
        }

        .feature-cell, .package-cell {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            min-height: 70px;
        }

        .feature-cell {
            background: var(--light-gray);
            font-weight: 600;
            color: var(--dark-color);
            border-right: 2px solid var(--border-color);
        }

        .package-cell {
            text-align: center;
            position: relative;
        }

        .package-header {
            background: var(--primary-color);
            color: white;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .package-header.popular {
            background: var(--success-color);
        }

        .package-header.flexible {
            background: var(--warning-color);
        }

        .package-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        .package-price {
            margin: 10px 0 0 0;
        }

        .package-price .amount {
            font-size: 2rem;
            font-weight: 700;
        }

        .package-price .period {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .per-location-price {
            font-size: 1.1rem;
            margin-top: 5px;
            color: #fff;
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: var(--danger-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .check-icon {
            color: var(--success-color);
            font-size: 1.2rem;
        }

        .cross-icon {
            color: #ccc;
            font-size: 1.2rem;
        }

        .value-cell {
            font-weight: 600;
            color: var(--dark-color);
        }

        .unlimited {
            color: var(--success-color);
            font-weight: 700;
        }

        .cta-cell {
            padding: 30px 20px;
            background: var(--light-gray);
        }

        .btn-pricing {
            width: 100%;
            padding: 15px 20px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.3);
        }

        .location-calculator {
            margin: 15px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .location-input {
            width: 80px;
            padding: 8px 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-align: center;
            font-weight: 600;
        }

        .location-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.2);
        }

        .calculated-price {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .pricing-grid {
                grid-template-columns: 1fr;
                min-width: auto;
            }
            
            .feature-cell {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            
            .pricing-header h1 {
                font-size: 2.5rem;
            }
        }

        .mvr-price {
            color: #f39c12;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <a href="/">
                        <img src="/uploads/img/logo.png" alt="IsleBooks POS" style="height: 40px;">
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <a href="/" class="btn btn-outline-primary" style="margin-right: 10px;">Home</a>
                    <a href="https://islebooks.mv/contact-us" target="_blank" class="btn btn-outline-primary">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <div class="pricing-container">
        <div class="pricing-header">
            <h1>Choose Your Perfect Plan</h1>
            <p>Flexible pricing for businesses of all sizes. All plans include our complete POS features.</p>
            
            <div class="currency-toggle">
                <input type="radio" id="usd" name="currency" checked>
                <input type="radio" id="mvr" name="currency">
                <label for="usd">USD Pricing</label>
                <label for="mvr">MVR Pricing</label>
            </div>
        </div>

        <div class="pricing-table">
            <div class="pricing-table-header">
                <h2>Compare All Plans</h2>
            </div>
            <div class="pricing-table-content">
                <div class="pricing-grid">
                    <!-- Feature column -->
                    <div class="feature-cell" style="background: var(--dark-color); color: white;">
                        <strong>Features</strong>
                    </div>
                    
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="package-header @if($package->name == 'Premier') popular @elseif($package->is_per_location_pricing) flexible @endif">
                            <h3 class="package-name">{{ $package->name }}</h3>
                            
                            @if($package->is_per_location_pricing)
                                <div class="per-location-price">
                                    <div class="usd-pricing">
                                        From <span class="amount">${{ $package->price_per_location }}</span>
                                        <div class="period">per location/month</div>
                                    </div>
                                    <div class="mvr-pricing" style="display: none;">
                                        From <span class="amount mvr-price">MVR {{ number_format($package->price_per_location * 15.42, 0) }}</span>
                                        <div class="period">per location/month</div>
                                    </div>
                                    
                                    <div class="location-calculator">
                                        <label style="font-size: 0.9rem; margin-bottom: 8px; display: block;">Locations needed:</label>
                                        <input type="number" 
                                               class="location-input" 
                                               data-package-id="{{ $package->id }}"
                                               data-price-per-location="{{ $package->price_per_location }}"
                                               min="{{ $package->min_locations }}" 
                                               @if($package->max_locations > 0) max="{{ $package->max_locations }}" @endif
                                               value="{{ $package->min_locations }}"
                                               onchange="updatePerLocationPrice(this)">
                                        <div class="calculated-price">
                                            <span class="total-usd">${{ $package->calculatePrice($package->min_locations) }}</span>
                                            <span class="total-mvr mvr-price" style="display: none;">MVR {{ number_format($package->calculatePrice($package->min_locations) * 15.42, 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="package-price">
                                    <div class="usd-pricing">
                                        @if($package->price != 0)
                                            <span class="amount">${{ $package->price }}</span>
                                            <div class="period">per {{ $package->interval_count }} {{ $package->interval }}</div>
                                        @else
                                            <span class="amount">FREE</span>
                                            <div class="period">{{ $package->interval_count }} {{ $package->interval }}</div>
                                        @endif
                                    </div>
                                    <div class="mvr-pricing" style="display: none;">
                                        @if($package->price != 0)
                                            <span class="amount mvr-price">MVR {{ number_format($package->price * 15.42, 0) }}</span>
                                            <div class="period">per {{ $package->interval_count }} {{ $package->interval }}</div>
                                        @else
                                            <span class="amount">FREE</span>
                                            <div class="period">{{ $package->interval_count }} {{ $package->interval }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            @if($package->name == 'Premier')
                                <div class="popular-badge">Most Popular</div>
                            @elseif($package->is_per_location_pricing)
                                <div class="popular-badge" style="background: var(--warning-color);">Flexible</div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Feature rows -->
                    <div class="feature-cell">Business Locations</div>
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="package-cell value-cell">
                            @if($package->is_per_location_pricing)
                                <span class="unlimited">Custom</span><br>
                                <small>{{ $package->min_locations }}-{{ $package->max_locations > 0 ? $package->max_locations : '∞' }} locations</small>
                            @elseif($package->location_count == 0)
                                <span class="unlimited">Unlimited</span>
                            @else
                                <strong>{{ $package->location_count }}</strong>
                            @endif
                        </div>
                    @endforeach

                    <div class="feature-cell">Users</div>
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="package-cell value-cell">
                            @if($package->user_count == 0)
                                <span class="unlimited">Unlimited</span>
                            @else
                                <strong>{{ number_format($package->user_count) }}</strong>
                            @endif
                        </div>
                    @endforeach

                    <div class="feature-cell">Products</div>
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="package-cell value-cell">
                            @if($package->product_count == 0)
                                <span class="unlimited">Unlimited</span>
                            @else
                                <strong>{{ number_format($package->product_count) }}</strong>
                            @endif
                        </div>
                    @endforeach

                    <div class="feature-cell">Invoices</div>
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="package-cell value-cell">
                            @if($package->invoice_count == 0)
                                <span class="unlimited">Unlimited</span>
                            @else
                                <strong>{{ number_format($package->invoice_count) }}</strong>
                            @endif
                        </div>
                    @endforeach

                    <!-- Module Features -->
                    @if(isset($permission_formatted))
                        @foreach($permission_formatted as $permission_key => $permission_label)
                            @if(!in_array($permission_key, ['plasticbag_module', 'ageingreport_module']))
                                <div class="feature-cell">{{ $permission_label }}</div>
                                @foreach($packages as $package)
                                    @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                                        @continue
                                    @endif
                                    @if($package->restrict_date !== null)
                                        @continue  
                                    @endif
                                    @if($package->id == "16")
                                        @continue
                                    @endif
                                    
                                    <div class="package-cell">
                                        @if(isset($package->custom_permissions[$permission_key]) && $package->custom_permissions[$permission_key])
                                            <i class="fa fa-check check-icon"></i>
                                        @else
                                            <i class="fa fa-times cross-icon"></i>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    @endif

                    <!-- CTA Row -->
                    <div class="cta-cell"></div>
                    @foreach($packages as $package)
                        @if($package->is_private == 1 && !auth()->user()->can('superadmin'))
                            @continue
                        @endif
                        @if($package->restrict_date !== null)
                            @continue  
                        @endif
                        @if($package->id == "16")
                            @continue
                        @endif
                        
                        <div class="cta-cell">
                            @if($package->is_per_location_pricing)
                                <a href="javascript:void(0)" 
                                   class="btn-pricing btn-warning"
                                   onclick="subscribeToPerLocation({{ $package->id }}, this)">
                                    Get Started
                                </a>
                            @else
                                <a href="/subscription/{{ $package->id }}/pay" 
                                   class="btn-pricing @if($package->name == 'Premier') btn-success @else btn-primary @endif">
                                    @if($package->price == 0)
                                        Start Free Trial
                                    @else
                                        Get Started
                                    @endif
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Free Trial CTA -->
        <div class="text-center" style="margin-top: 40px;">
            <a href="/business/register?package=16" class="btn-pricing btn-success" style="display: inline-block; width: auto; padding: 20px 40px; font-size: 1.2rem;">
                <i class="fa fa-rocket"></i> Start Your Free Trial Now
            </a>
        </div>
    </div>

    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script>
        // Currency toggle functionality
        $('input[name="currency"]').change(function() {
            var currency = $(this).attr('id');
            if (currency === 'usd') {
                $('.usd-pricing').show();
                $('.mvr-pricing').hide();
            } else {
                $('.usd-pricing').hide();
                $('.mvr-pricing').show();
            }
        });

        // Per-location price calculator
        function updatePerLocationPrice(input) {
            var quantity = parseInt($(input).val());
            var pricePerLocation = parseFloat($(input).data('price-per-location'));
            var packageId = $(input).data('package-id');
            
            var totalUsd = pricePerLocation * quantity;
            var totalMvr = totalUsd * 15.42;
            
            $(input).closest('.package-header').find('.total-usd').text('$' + totalUsd.toFixed(2));
            $(input).closest('.package-header').find('.total-mvr').text('MVR ' + Math.round(totalMvr));
        }

        // Handle per-location package subscription
        function subscribeToPerLocation(packageId, button) {
            var quantity = $('[data-package-id="' + packageId + '"]').val();
            window.location.href = '/subscription/' + packageId + '/pay?location_quantity=' + quantity + '&location_quantity_confirmed=1';
        }

        // Initialize per-location calculations
        $(document).ready(function() {
            $('.location-input').each(function() {
                updatePerLocationPrice(this);
            });
        });
    </script>
</body>
</html>