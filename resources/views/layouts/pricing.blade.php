<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'POS') }}</title> 


        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <script>
        window.FontAwesomeConfig = { autoReplaceSvg: false, searchPseudoElements: false };
        </script>
        <script src="https://kit.fontawesome.com/271b9f073a.js" crossorigin="anonymous"></script>

        <link rel="stylesheet" href="{{ asset('css/pricing.css?v=' . config('app.asset_version', 1)) }}">


</head>

<body class="hold-transition">
    @if (session('status'))
        <input type="hidden" id="status_span" data-status="{{ session('status.success') }}" data-msg="{{ session('status.msg') }}">
    @endif

    @yield('content')

    @include('layouts.partials.javascripts')
    <!-- Scripts -->
    <script src="{{ asset('js/login.js?v=' . $asset_v) }}"></script>
    @yield('javascript')

    <script>
        $('.modal').remove();
        
    </script>
 
</body>

</html>