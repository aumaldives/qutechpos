<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@lang('lang_v1.reset_password') - {{ config('app.name', 'IsleBooks POS') }}</title>
    
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

    <div class="modern-login-container">
        <div class="login-card">
            <!-- Left Side - Branding -->
            <div class="login-branding">
                <div class="logo-container">
                    @if(file_exists(public_path('uploads/img/logo.png')))
                        <img src="{{ asset('uploads/img/logo.png') }}" alt="IsleBooks Logo" class="login-logo">
                    @elseif(file_exists(public_path('uploads/logo.png')))
                        <img src="{{ asset('uploads/logo.png') }}" alt="IsleBooks Logo" class="login-logo">
                    @else
                        <div class="login-logo-fallback">
                            <i class="fas fa-store-alt"></i>
                        </div>
                    @endif
                </div>
                
                <h1 class="login-title">{{ config('app.name', 'IsleBooks POS') }}</h1>
                
                <p class="login-tagline">Your Trusted POS Partner in Maldives</p>
                
                <p class="login-subtitle">
                    Password Recovery<br>
                    We'll help you get back to your account
                </p>
                
                <ul class="features-list">
                    <li><i class="fas fa-shield-alt"></i> Secure password recovery</li>
                    <li><i class="fas fa-envelope"></i> Email-based verification</li>
                    <li><i class="fas fa-clock"></i> Quick and easy process</li>
                    <li><i class="fas fa-user-lock"></i> Account security maintained</li>
                </ul>

                <div class="feature-callouts">
                    <a href="{{ route('login') }}" class="feature-callout">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Back to Login</span>
                    </a>
                    <a href="https://support.islebooks.mv" class="feature-callout">
                        <i class="fas fa-question-circle"></i>
                        <span>Need Help?</span>
                    </a>
                    <a href="https://pos.islebooks.mv/business/register?package=16" class="feature-callout">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                </div>
            </div>

            <!-- Right Side - Reset Form -->
            <div class="login-form-container">
                <div class="login-form-header">
                    <h2 class="login-form-title">@lang('lang_v1.reset_password')</h2>
                    <p class="login-form-subtitle">Enter your email address and we'll send you a secure link to reset your password</p>
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

                <form method="POST" action="{{ route('password.email') }}" id="reset-form">
                    @csrf

                    <!-- Email Field -->
                    <div class="modern-form-group">
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input 
                                id="email" 
                                type="email" 
                                class="modern-input" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                placeholder="@lang('lang_v1.email_address')"
                            >
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <button type="submit" class="modern-btn primary-btn" id="reset-btn">
                        <i class="fas fa-paper-plane"></i>
                        <span id="reset-text">@lang('lang_v1.send_password_reset_link')</span>
                    </button>

                    <!-- Secondary Actions -->
                    <div class="secondary-actions">
                        <a href="{{ route('login') }}" class="secondary-btn">
                            <i class="fas fa-arrow-left"></i>
                            Back to Login
                        </a>
                        
                        <a href="https://pos.islebooks.mv/business/register?package=16" class="secondary-btn register-btn">
                            <i class="fas fa-user-plus"></i>
                            Register New Business
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="navigation-links">
                        <a href="https://pos.islebooks.mv/pricing" class="nav-link-btn">
                            <i class="fas fa-tags"></i>
                            See Pricing Plans
                        </a>
                        
                        <a href="https://support.islebooks.mv" class="nav-link-btn">
                            <i class="fas fa-life-ring"></i>
                            Get Help & Support
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Form submission handling
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            const resetBtn = document.getElementById('reset-btn');
            const resetText = document.getElementById('reset-text');
            
            resetBtn.classList.add('loading');
            resetText.textContent = 'Sending email...';
            resetBtn.disabled = true;
        });

        // Auto-hide alerts after 8 seconds (longer for reset confirmations)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.error-message, .success-message');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 8000);

        // Focus enhancement
        document.querySelectorAll('.modern-input').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.parentNode.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.style.transform = 'translateY(0)';
            });
        });

        // Email validation feedback
        document.getElementById('email').addEventListener('input', function(e) {
            const email = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && emailRegex.test(email)) {
                e.target.style.borderColor = 'var(--islebooks-cyan)';
                e.target.style.boxShadow = '0 0 0 3px rgba(6, 182, 212, 0.1)';
            } else if (email) {
                e.target.style.borderColor = '#ef4444';
                e.target.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            } else {
                e.target.style.borderColor = 'var(--border-color)';
                e.target.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>