<?php $request = app('Illuminate\Http\Request'); ?>

<?php if($request->segment(1) == 'pos' && ($request->segment(2) == 'create' || $request->segment(3) == 'edit'
 || $request->segment(2) == 'payment')): ?>
    <?php
        $pos_layout = true;
    ?>
<?php else: ?>
    <?php
        $pos_layout = false;
    ?>
<?php endif; ?>

<?php
    $whitelist = ['127.0.0.1', '::1'];
?>

<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale(), false); ?>" dir="<?php echo e(in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) ? 'rtl' : 'ltr', false); ?>">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="<?php echo e(csrf_token(), false); ?>">

        <title><?php echo $__env->yieldContent('title'); ?> - <?php echo e(Session::get('business.name'), false); ?></title>
        
        <?php echo $__env->make('layouts.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php echo $__env->yieldContent('css'); ?>
    </head>

    <body class="<?php if($pos_layout): ?> hold-transition lockscreen <?php else: ?> hold-transition skin-<?php if(!empty(session('business.theme_color'))): ?><?php echo e(session('business.theme_color'), false); ?><?php else: ?><?php echo e('blue', false); ?><?php endif; ?> sidebar-mini <?php endif; ?>">
        <div class="wrapper thetop">
            <script type="text/javascript">
                if(localStorage.getItem("upos_sidebar_collapse") == 'true'){
                    var body = document.getElementsByTagName("body")[0];
                    body.className += " sidebar-collapse";
                }
            </script>
            <?php if(!$pos_layout): ?>
                <?php
                    $theme_color = session('business.theme_color', 'blue');
                    $is_light_theme = str_ends_with($theme_color, '-light');
                ?>
                <?php if($is_light_theme): ?>
                    <?php echo $__env->make('layouts.partials.header-light', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php else: ?>
                    <?php echo $__env->make('layouts.partials.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
                <?php echo $__env->make('layouts.partials.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php else: ?>
                <?php echo $__env->make('layouts.partials.header-pos', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>

            <?php if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)): ?>
                <input type="hidden" id="__is_localhost" value="true">
            <?php endif; ?>

            <!-- Content Wrapper. Contains page content -->
            <div class="<?php if(!$pos_layout): ?> content-wrapper <?php endif; ?>">
                <!-- empty div for vuejs -->
                <div id="app">
                    <?php echo $__env->yieldContent('vue'); ?>
                </div>
                <!-- Add currency related field-->
                <input type="hidden" id="__code" value="<?php echo e(session('currency')['code'], false); ?>">
                <input type="hidden" id="__symbol" value="<?php echo e(session('currency')['symbol'], false); ?>">
                <input type="hidden" id="__thousand" value="<?php echo e(session('currency')['thousand_separator'], false); ?>">
                <input type="hidden" id="__decimal" value="<?php echo e(session('currency')['decimal_separator'], false); ?>">
                <input type="hidden" id="__symbol_placement" value="<?php echo e(session('business.currency_symbol_placement'), false); ?>">
                <input type="hidden" id="__precision" value="<?php echo e(session('business.currency_precision', 2), false); ?>">
                <input type="hidden" id="__quantity_precision" value="<?php echo e(session('business.quantity_precision', 2), false); ?>">
                <!-- End of currency related field-->
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_export_buttons')): ?>
                    <input type="hidden" id="view_export_buttons">
                <?php endif; ?>
                <?php if(isMobile()): ?>
                    <input type="hidden" id="__is_mobile">
                <?php endif; ?>
                <?php if(session('status')): ?>
                    <input type="hidden" id="status_span" data-status="<?php echo e(session('status.success'), false); ?>" data-msg="<?php echo e(session('status.msg'), false); ?>">
                <?php endif; ?>
                <?php echo $__env->yieldContent('content'); ?>

                <div class='scrolltop no-print'>
                    <div class='scroll icon'><i class="fas fa-angle-up"></i></div>
                </div>

                <?php if(config('constants.iraqi_selling_price_adjustment')): ?>
                    <input type="hidden" id="iraqi_selling_price_adjustment">
                <?php endif; ?>

                <!-- This will be printed -->
                <section class="invoice print_section" id="receipt_section">
                </section>
                
            </div>
            <?php echo $__env->make('home.todays_profit_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <!-- /.content-wrapper -->

            <?php if(!$pos_layout): ?>
                <?php echo $__env->make('layouts.partials.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php else: ?>
                <?php echo $__env->make('layouts.partials.footer_pos', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>

            <audio id="success-audio">
              <source src="<?php echo e(asset('/audio/success.ogg?v=' . $asset_v), false); ?>" type="audio/ogg">
              <source src="<?php echo e(asset('/audio/success.mp3?v=' . $asset_v), false); ?>" type="audio/mpeg">
            </audio>
            <audio id="error-audio">
              <source src="<?php echo e(asset('/audio/error.ogg?v=' . $asset_v), false); ?>" type="audio/ogg">
              <source src="<?php echo e(asset('/audio/error.mp3?v=' . $asset_v), false); ?>" type="audio/mpeg">
            </audio>
            <audio id="warning-audio">
              <source src="<?php echo e(asset('/audio/warning.ogg?v=' . $asset_v), false); ?>" type="audio/ogg">
              <source src="<?php echo e(asset('/audio/warning.mp3?v=' . $asset_v), false); ?>" type="audio/mpeg">
            </audio>
        </div>

        <?php if(!empty($__additional_html)): ?>
            <?php echo $__additional_html; ?>

        <?php endif; ?>

        <?php echo $__env->make('layouts.partials.javascripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="modal fade view_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel"></div>

        <?php if(!empty($__additional_views) && is_array($__additional_views)): ?>
            <?php $__currentLoopData = $__additional_views; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $additional_view): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if ($__env->exists($additional_view)) echo $__env->make($additional_view, \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>

        <?php
        if (Auth::check()) {
        
        $user = Auth::user();
        $fullname = $user->first_name . ' ' . $user->last_name;
        $email = $user->email;
        $current_user = $user;
        $business = $user->business->name;

        }   
        ?>

        <script>
        /* if (window.location.href.indexOf('/pos/create') === -1) { */
        if (window.location.hostname !== 'isleposgit.site' && window.location.pathname !== '/pos/create') {

            window.intercomSettings = {
                api_base: "https://api-iam.intercom.io",
                app_id: "p8z4zc3w",
                name: '<?php echo e($fullname, false); ?>',
                email: '<?php echo e($email, false); ?>',
                created_at: "<?php echo strtotime($current_user->created_at) ?>",
                business_name: '<?php echo e($business, false); ?>'
            };
            (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/p8z4zc3w';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
        }
        </script>

        <!-- FeatureShift Announcements Script -->
        <script src="https://widget.featureshift.com/js/v1/announcements.js" data-featureshift-key="c6d712f0-8581-4789-8977-1b406a72dbec" defer></script>
        
        <!-- Custom FeatureShift popup positioning -->
        <style>
            /* Adjust FeatureShift popup position */
            .featureshift-widget-container,
            .featureshift-announcements-widget,
            [class*="featureshift"] {
                top: 80px !important; /* Move popup down from top */
                margin-top: 20px !important;
            }
            
            /* Alternative selector for FeatureShift popup */
            div[style*="position: fixed"][style*="z-index"] {
                top: 80px !important;
            }
            
            /* Position relative to viewport instead of absolute top */
            .featureshift-popup {
                transform: translateY(50px) !important;
            }
        </style>

    </body>

</html><?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>