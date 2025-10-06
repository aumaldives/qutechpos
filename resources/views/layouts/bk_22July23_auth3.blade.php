<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">


    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'POS') }}</title>

    <!-- Fonts -->
    <!-- <link href="https://fonts.googleapis.com/css?family=Raleway:100,300,600" rel="stylesheet" type="text/css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet">


</head>

<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">

            <div class="col-md-6 borderClass left-section-border">

                <nav class="navbar navbar-expand-lg ">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#">

                            <img src="/uploads/img/logo.png" class="img-rounded" alt="Logo" width="150">
                            <!-- @if(file_exists(public_path('uploads/logo.png')))
                            @else
                            {{ config('app.name', '') }}
                            @endif -->
                        </a>

                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse navbar-align" id="navbarNav">
                            <ul class="navbar-nav ">
                                <li class="nav-item">
                                    <a class="nav-link active" aria-current="page" href="{{ action([\Modules\Superadmin\Http\Controllers\PricingController::class, 'index']) }}">Pricing</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('business.getRegister') }}">Register</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/repair-status">Repair Status</a>
                                </li>
                                
                                <li class="nav-item">
                                    <a class="nav-link" href="https://islebooks.mv/documentations/">Guides</a>
                                </li>

								
								<!--<li class="nav-item">
                                    <a class="nav-link" href="https://islebooks.mv/contact-us/">Contact Us</a>
                                </li>-->
								
                            </ul>
                        </div>
                    </div>
                </nav>

                @yield('content')

                <div class="download-section download-left-section d-none">
                    <!-- Microsoft Store Button -->
                    <div class="download-button" style="margin-right: 20px;">
                        <!-- Link to Microsoft Store -->
                        <a href="https://www.microsoft.com/store/apps/details?id=com.your-laravel-app" target="_blank">
                            <!-- Button Image -->
                            <img src="https://pos.islebooks.mv/uploads/custom/msft.svg" alt="Get it on Microsoft Store" onclick="window.open('https://www.microsoft.com/store/apps/details?id=com.your-laravel-app', '_blank')">
                        </a>
                    </div>

                    <!-- Google PlayStore Button -->
                    <div style="margin-right: 20px; margin-top: -15px;">
                        <!-- Link to Google PlayStore -->
                        <a href="https://play.google.com/store/apps/details?id=com.your-laravel-app" target="_blank">
                            <!-- Button Image -->
                            <img src="/uploads/custom/google-play-badge.png" alt="Get it on PlayStore" style="height: 57px !important;margin-bottom: 5px;" onclick="window.open('https://play.google.com/store/apps/details?id=com.your-laravel-app', '_blank')">
                        </a>
                    </div>

                    <!-- AppStore Button -->
                    <div>
                        <!-- Link to AppStore -->
                        <a href="https://apps.apple.com/us/app/your-laravel-app/id1234567890" target="_blank">
                            <!-- Button Image -->
                            <img src="https://pos.islebooks.mv/uploads/custom/appstore.svg" alt="Get it on AppStore" onclick="window.open('https://apps.apple.com/us/app/your-laravel-app/id1234567890', '_blank')">
                        </a>
                    </div>
                </div>

                <div class="col-md-12 row social-media-container mt-5" >
                <ul class="social-media" style="color:#fff;display: flex;line-height:40px">

                    <li>
                        <a rel="nofollow" href="https://facebook.com/islebooks.mv" target="_blank"><img src="/uploads/custom/social-media/facebook.png" alt=""></a>
                    </li>
                  
                    <li class="margin-left-social-media">
                        <a rel="nofollow" href="https://instagram.com/islebooks.mv" target="_blank"><img src="/uploads/custom/social-media/instagram.png" alt=""></a>
                    </li>

                    <li class="margin-left-social-media">
                        <a rel="nofollow" href="https://www.youtube.com/@islebooks" target="_blank"><img src="/uploads/custom/social-media/youtube.png" alt=""></a>
                    </li>
                   
                   <!--  <li>
                        <a rel="nofollow" href="#" target="_blank"><i class="fa fa-comments" target="_blank" aria-hidden="true"></i></a>
                    </li> -->
                </ul>
            </div>

            </div>

            <div class="col-md-6 borderClass bg-image right-section">
                <div class="box">
                    <h2 class="top-heading">
                        <span>Welcome</span>
                        <br />
                        
                    </h2>

                    <div class="boxSection">
                        <h3>IsleBooks POS</h3>
                        <h1>Revolutionizing Point Of Sales In Maldives</h1>
                        <p>
                        IsleBooks POS is a game-changing sales solution designed to streamline operations and propel businesses to success. With its intuitive interface and advanced features, it simplifies transactions, automates inventory management, and facilitates seamless payment processing. By consolidating data and providing real-time insights, IsleBooks POS empowers businesses to make informed decisions and optimize their strategies for maximum profitability.
                        </p>

                        <br>

                        <p>Moreover, IsleBooks POS goes beyond transactional efficiency by fostering stronger customer relationships. Its integrated customer relationship management features allow businesses to personalize experiences, track customer preferences, and deliver targeted promotions. With top-notch security measures in place, IsleBooks POS ensures the protection of sensitive information, giving businesses peace of mind. In summary, IsleBooks POS is the ultimate solution for businesses looking to streamline sales operations, enhance customer experiences, and achieve outstanding success in today's competitive market.</p>

                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>

    @include('layouts.partials.javascripts')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/174d96e2b4.js" crossorigin="anonymous"></script>
    @yield('javascript')

    <script type="text/javascript">
        $(document).ready(function() {
            $('#change_lang').change(function() {
                window.location = "{{ route('login') }}?lang=" + $(this).val();
            });

            $('a.demo-login').click(function(e) {
                e.preventDefault();
                $('#username').val($(this).data('admin'));
                $('#password').val("{{$password}}");
                $('form#login-form').submit();
            });
        })
    </script>

<!-- Start of LiveChat (www.livechat.com) code-->
<script>
  window.intercomSettings = {
    api_base: "https://api-iam.intercom.io",
    app_id: "p8z4zc3w",
    name: <?php echo json_encode($current_user->name) ?>, // Full name
    email: <?php echo json_encode($current_user->email) ?>, // Email address
    created_at: "<?php echo strtotime($current_user->created_at) ?>" // Signup date as a Unix timestamp
  };
</script>

<script>
// We pre-filled your app ID in the widget URL: 'https://widget.intercom.io/widget/p8z4zc3w'
(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/p8z4zc3w';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
</script>
<!-- End of LiveChat code -->

</body>

</html>