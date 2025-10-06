<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@lang('lang_v1.login') - {{ config('app.name', 'IsleBooks POS') }}</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Modern Login CSS -->
    <link rel="stylesheet" href="{{ asset('css/modern-login.css?v=' . time()) }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('uploads/logo.png') }}">
</head>

<body class="modern-login">
    <div class="login-background"></div>
    
    <!-- Language Selector -->
    <div class="language-selector">
        <select id="change_lang" onchange="changeLanguage(this.value)">
            @foreach(config('constants.langs') as $key => $val)
                <option value="{{$key}}" 
                    @if( (empty(request()->lang) && config('app.locale') == $key) 
                    || request()->lang == $key) 
                        selected 
                    @endif
                >
                    {{$val['full_name']}}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Registration Link -->
    @inject('request', 'Illuminate\Http\Request')
    @if(config('constants.allow_registration') && !($request->segment(1) == 'business' && $request->segment(2) == 'register'))
        <div class="registration-link">
            <a href="{{ route('business.getRegister') }}@if(!empty(request()->lang)){{'?lang=' . request()->lang}} @endif">
                <i class="fas fa-user-plus"></i> @lang('business.register_now')
            </a>
        </div>
    @endif

    <div class="modern-login-container">
        <div class="login-card">
            <!-- Left Side - Branding -->
            <div class="login-branding">
                @if(file_exists(public_path('uploads/logo.png')))
                    <img src="{{ asset('uploads/logo.png') }}" alt="Logo" class="login-logo">
                @else
                    <div class="login-logo" style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 16px;">
                        <i class="fas fa-store" style="font-size: 3rem;"></i>
                    </div>
                @endif
                
                <h1 class="login-title">{{ config('app.name', 'IsleBooks POS') }}</h1>
                
                <p class="login-subtitle">
                    @if(!empty(config('constants.app_title')))
                        {{ config('constants.app_title') }}
                    @else
                        Modern Point of Sale System<br>
                        Streamline your business operations
                    @endif
                </p>
                
                <ul class="features-list">
                    <li>Comprehensive inventory management</li>
                    <li>Real-time sales tracking</li>
                    <li>Multi-location support</li>
                    <li>Advanced reporting & analytics</li>
                </ul>
            </div>

            <!-- Right Side - Login Form -->
            <div class="login-form-container">
                <div class="login-form-header">
                    <h2 class="login-form-title">@lang('lang_v1.login')</h2>
                    <p class="login-form-subtitle">Welcome back! Please sign in to your account</p>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                <!-- Success Messages -->
                @if (session('status'))
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf
                    @php
                        $username = old('username');
                        $password = null;
                        if(config('app.env') == 'demo'){
                            $username = 'admin';
                            $password = '123456';
                            $demo_types = array(
                                'all_in_one' => 'admin',
                                'super_market' => 'admin',
                                'pharmacy' => 'admin-pharmacy',
                                'electronics' => 'admin-electronics',
                                'services' => 'admin-services',
                                'restaurant' => 'admin-restaurant',
                                'superadmin' => 'superadmin',
                                'woocommerce' => 'woocommerce_user',
                                'essentials' => 'admin-essentials',
                                'manufacturing' => 'manufacturer-demo',
                            );
                            if( !empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types) ){
                                $username = $demo_types[$_GET['demo_type']];
                            }
                        }
                    @endphp

                    <!-- Username Field -->
                    <div class="modern-form-group">
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input 
                                id="username" 
                                type="text" 
                                class="modern-input" 
                                name="username" 
                                value="{{ $username }}" 
                                required 
                                autofocus 
                                placeholder="@lang('lang_v1.username')"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="modern-form-group">
                        <div class="input-with-icon">
                            <i class="fas fa-lock input-icon"></i>
                            <input 
                                id="password" 
                                type="password" 
                                class="modern-input" 
                                name="password" 
                                value="{{ $password }}" 
                                required 
                                placeholder="@lang('lang_v1.password')"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="password-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="modern-form-group">
                        <label class="modern-checkbox">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>@lang('lang_v1.remember_me')</span>
                        </label>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="modern-btn" id="login-btn">
                        <span id="login-text">@lang('lang_v1.login')</span>
                    </button>

                    <!-- Forgot Password -->
                    @if(config('app.env') != 'demo')
                        <div class="forgot-password">
                            <a href="{{ route('password.request') }}">
                                @lang('lang_v1.forgot_your_password')
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
        <!-- Demo Section (for demo environment) -->
        @if(config('app.env') == 'demo')
            <div class="demo-section" style="width: 100%; max-width: 1000px; margin-top: 30px;">
                <h3 class="demo-title">Demo Shops</h3>
                <p class="demo-subtitle">Demos are for example purpose only, this application can be used in many other similar businesses.</p>
                
                <div class="demo-buttons">
                    <a href="javascript:void(0)" class="demo-btn primary" onclick="demoLogin('{{$demo_types['all_in_one']}}', '{{$password}}')">
                        <i class="fas fa-star"></i> All In One
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn danger" onclick="demoLogin('{{$demo_types['pharmacy']}}', '{{$password}}')">
                        <i class="fas fa-medkit"></i> Pharmacy
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn warning" onclick="demoLogin('{{$demo_types['services']}}', '{{$password}}')">
                        <i class="fas fa-wrench"></i> Multi-Service Center
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn secondary" onclick="demoLogin('{{$demo_types['electronics']}}', '{{$password}}')">
                        <i class="fas fa-laptop"></i> Electronics & Mobile Shop
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn accent" onclick="demoLogin('{{$demo_types['super_market']}}', '{{$password}}')">
                        <i class="fas fa-shopping-cart"></i> Super Market
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn danger" onclick="demoLogin('{{$demo_types['restaurant']}}', '{{$password}}')">
                        <i class="fas fa-utensils"></i> Restaurant
                    </a>
                </div>

                <div style="text-align: center; margin: 20px 0; color: var(--text-secondary);">
                    <strong><i class="fas fa-plug"></i> Premium optional modules:</strong>
                </div>

                <div class="demo-buttons">
                    <a href="javascript:void(0)" class="demo-btn danger" onclick="demoLogin('{{$demo_types['superadmin']}}', '{{$password}}')">
                        <i class="fas fa-university"></i> SaaS / Superadmin
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn secondary" onclick="demoLogin('{{$demo_types['woocommerce']}}', '{{$password}}')">
                        <i class="fab fa-wordpress"></i> WooCommerce
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn accent" onclick="demoLogin('{{$demo_types['essentials']}}', '{{$password}}')">
                        <i class="fas fa-check-circle"></i> Essentials & HRM
                    </a>
                    
                    <a href="javascript:void(0)" class="demo-btn warning" onclick="demoLogin('{{$demo_types['manufacturing']}}', '{{$password}}')">
                        <i class="fas fa-industry"></i> Manufacturing Module
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Scripts -->
    <script>
        // Language change function
        function changeLanguage(lang) {
            window.location = "{{ route('login') }}?lang=" + lang;
        }

        // Password toggle function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordEye = document.getElementById('password-eye');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordEye.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordEye.className = 'fas fa-eye';
            }
        }

        // Demo login function
        function demoLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.getElementById('login-form').submit();
        }

        // Form submission handling
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('login-btn');
            const loginText = document.getElementById('login-text');
            
            loginBtn.classList.add('loading');
            loginText.textContent = 'Signing in...';
            loginBtn.disabled = true;
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.error-message, .success-message');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>
