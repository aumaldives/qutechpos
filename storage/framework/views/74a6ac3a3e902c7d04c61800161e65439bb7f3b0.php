<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale(), false); ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="<?php echo e(csrf_token(), false); ?>">

    <title><?php echo app('translator')->get('lang_v1.login'); ?> - Qutech POS</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .floating-animation {
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0% { transform: translate(0, 0px); }
            50% { transform: translate(0, -20px); }
            100% { transform: translate(0, -0px); }
        }

        .slide-in {
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modern-input {
            background: rgba(255, 255, 255, 0.95);
            border: 2px solid rgba(229, 231, 235, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-input:focus {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(75, 85, 99, 0.6);
            box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.1);
            transform: translateY(-1px);
        }

        .modern-input::placeholder {
            color: rgba(107, 114, 128, 0.7);
            font-weight: 400;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group:hover .modern-input {
            border-color: rgba(107, 114, 128, 0.4);
            transform: translateY(-0.5px);
        }

        .input-icon {
            color: rgba(107, 114, 128, 0.6);
            transition: color 0.3s ease;
        }

        .modern-input:focus + .input-icon,
        .input-group:hover .input-icon {
            color: rgba(75, 85, 99, 0.8);
        }

        .modern-btn {
            background: linear-gradient(135deg, rgba(31, 41, 55, 1) 0%, rgba(17, 24, 39, 1) 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modern-btn:hover {
            background: linear-gradient(135deg, rgba(17, 24, 39, 1) 0%, rgba(0, 0, 0, 1) 100%);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px);
        }

        .modern-btn:active {
            transform: translateY(0px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://qutech.mv/storage/media/sVyNmqenNZG8JAoBGDwjgXOshBW7UhBUG0v4AayJ.png">
</head>

<body class="min-h-screen gradient-bg">
    <?php
        $user = \Auth::user();
        if($user){
            if (!$user->can('dashboard.data') && $user->can('sell.create')) {
                return redirect('/pos/create');
            }
            if ($user->user_type == 'user_customer') {
                return redirect('contact/contact-dashboard');
            }
            return redirect()->route('home');
        }
    ?>

    <!-- Language Selector -->
    <div class="absolute top-6 right-6 z-20">
        <select id="change_lang" onchange="changeLanguage(this.value)"
                class="bg-white/20 backdrop-blur-md text-white border border-white/30 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-white/50">
            <?php $__currentLoopData = config('constants.langs'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($key, false); ?>" class="text-gray-800"
                    <?php if( (empty(request()->lang) && config('app.locale') == $key)
                    || request()->lang == $key): ?>
                        selected
                    <?php endif; ?>
                >
                    <?php echo e($val['full_name'], false); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <!-- Main Container -->
    <div class="min-h-screen flex">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-gradient-to-br from-gray-600 via-gray-700 to-gray-800 opacity-30"></div>

            <!-- Floating Elements -->
            <div class="absolute top-20 left-20 w-32 h-32 bg-white/5 rounded-full blur-xl floating-animation"></div>
            <div class="absolute bottom-32 right-32 w-24 h-24 bg-white/5 rounded-full blur-xl floating-animation" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/3 w-16 h-16 bg-white/5 rounded-full blur-xl floating-animation" style="animation-delay: 4s;"></div>

            <div class="relative z-10 flex flex-col justify-center px-12 py-24">
                <!-- Logo & Brand -->
                <div class="text-center">
                    <img src="https://qutech.mv/storage/media/sVyNmqenNZG8JAoBGDwjgXOshBW7UhBUG0v4AayJ.png"
                         alt="Qutech POS" class="w-24 h-24 mx-auto mb-8 rounded-xl shadow-lg">
                    <h1 class="text-5xl font-bold text-white mb-6">Qutech POS</h1>
                    <p class="text-2xl text-gray-300 leading-relaxed">
                        Modern Point of Sale System
                    </p>
                    <p class="text-lg text-gray-400 mt-4">
                        Streamline your business operations with intelligent solutions
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md slide-in">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-8">
                    <img src="https://qutech.mv/storage/media/sVyNmqenNZG8JAoBGDwjgXOshBW7UhBUG0v4AayJ.png"
                         alt="Qutech POS" class="w-16 h-16 mx-auto mb-4 rounded-xl shadow-lg">
                    <h1 class="text-3xl font-bold text-white mb-2">Qutech POS</h1>
                    <p class="text-white/80">Modern Point of Sale System</p>
                </div>

                <!-- Login Card -->
                <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 p-8">
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h2>
                        <p class="text-gray-600">Sign in to access your dashboard</p>
                    </div>

                    <!-- Error Messages -->
                    <?php if($errors->any()): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-400"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-red-700">
                                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php echo e($error, false); ?><br>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Success Messages -->
                    <?php if(session('status')): ?>
                        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm text-green-700">
                                        <?php echo e(session('status.msg') ?? session('status'), false); ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(route('login'), false); ?>" id="login-form" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <!-- Username Field -->
                        <div class="input-group">
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-3">
                                <?php echo app('translator')->get('lang_v1.username'); ?>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-user input-icon"></i>
                                </div>
                                <input
                                    id="username"
                                    type="text"
                                    class="modern-input block w-full pl-12 pr-4 py-4 rounded-xl text-gray-900 focus:outline-none"
                                    name="username"
                                    value="<?php echo e(old('username'), false); ?>"
                                    required
                                    autofocus
                                    placeholder="Enter your username"
                                >
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="input-group">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-3">
                                <?php echo app('translator')->get('lang_v1.password'); ?>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                    <i class="fas fa-lock input-icon"></i>
                                </div>
                                <input
                                    id="password"
                                    type="password"
                                    class="modern-input block w-full pl-12 pr-12 py-4 rounded-xl text-gray-900 focus:outline-none"
                                    name="password"
                                    required
                                    placeholder="Enter your password"
                                >
                                <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center z-10 hover:bg-gray-50 rounded-r-xl transition-colors duration-200" onclick="togglePassword()">
                                    <i class="fas fa-eye input-icon hover:text-gray-800 transition duration-200" id="password-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    class="h-4 w-4 text-gray-600 focus:ring-gray-500 border-gray-300 rounded transition duration-200"
                                    <?php echo e(old('remember') ? 'checked' : '', false); ?>

                                >
                                <span class="ml-2 block text-sm text-gray-700"><?php echo app('translator')->get('lang_v1.remember_me'); ?></span>
                            </label>

                            <a href="<?php echo e(route('password.request'), false); ?>" class="text-sm text-gray-600 hover:text-gray-800 transition duration-200">
                                <?php echo app('translator')->get('lang_v1.forgot_your_password'); ?>
                            </a>
                        </div>

                        <!-- Login Button -->
                        <button
                            type="submit"
                            id="login-btn"
                            class="modern-btn w-full flex justify-center items-center py-4 px-6 rounded-xl text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mt-8"
                        >
                            <span id="login-text"><?php echo app('translator')->get('lang_v1.login'); ?></span>
                            <i id="login-spinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Language change function
        function changeLanguage(lang) {
            window.location = "<?php echo e(url('/'), false); ?>?lang=" + lang;
        }

        // Password toggle function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordEye = document.getElementById('password-eye');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordEye.className = 'fas fa-eye-slash text-gray-400 hover:text-gray-600 transition duration-200';
            } else {
                passwordInput.type = 'password';
                passwordEye.className = 'fas fa-eye text-gray-400 hover:text-gray-600 transition duration-200';
            }
        }

        // Form submission handling
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('login-btn');
            const loginText = document.getElementById('login-text');
            const loginSpinner = document.getElementById('login-spinner');

            loginBtn.disabled = true;
            loginBtn.classList.add('opacity-75', 'cursor-not-allowed');
            loginText.textContent = 'Signing in...';
            loginSpinner.classList.remove('hidden');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-50, .bg-green-50');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);

        // Enhanced input interactions
        document.querySelectorAll('.modern-input').forEach(function(input) {
            input.addEventListener('focus', function() {
                this.closest('.input-group').classList.add('focused');
            });

            input.addEventListener('blur', function() {
                this.closest('.input-group').classList.remove('focused');
            });
        });
    </script>
</body>
</html><?php /**PATH /var/www/html/resources/views/layouts/home.blade.php ENDPATH**/ ?>