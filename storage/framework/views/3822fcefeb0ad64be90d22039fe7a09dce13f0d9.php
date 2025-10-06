<?php $request = app('Illuminate\Http\Request'); ?>
<!-- Light Glass Header -->
<header class="main-header no-print" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
    <a href="<?php echo e(route('home'), false); ?>" class="logo" style="background: transparent; transition: all 0.3s ease;">
      <span class="logo-lg" style="color: #ffffff; font-weight: 600; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
        <?php echo e(Session::get('business.name'), false); ?> 
        <i class="fa-duotone fa-wifi" style="--fa-primary-color: #10b981; --fa-secondary-color: #6ee7b7; margin-left: 8px;" id="online_indicator" title="Online"></i>
      </span> 
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation" style="background: transparent;">
      <!-- Light Glass Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" style="color: #ffffff; font-size: 18px; padding: 10px 12px; transition: all 0.3s ease; border-radius: 6px; margin: 4px; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); min-width: 42px; text-align: center;">
        <i class="fa fa-bars" style="color: #ffffff; font-size: 16px;"></i>
        <span class="sr-only">Toggle navigation</span>
      </a>

      <!-- Wrap floating elements in proper container -->
      <div class="navbar-left-items" style="display: inline-flex; align-items: center; gap: 8px; margin-left: 8px;">
        <?php if(Module::has('Superadmin')): ?>
          <div class="subscription-info-desktop">
            <?php if ($__env->exists('superadmin::layouts.partials.active_subscription')) echo $__env->make('superadmin::layouts.partials.active_subscription', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
          </div>
        <?php endif; ?>

        <?php if(!empty(session('previous_user_id')) && !empty(session('previous_username'))): ?>
            <a href="<?php echo e(route('sign-in-as-user', session('previous_user_id')), false); ?>" class="btn btn-flat m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <i class="fa fa-arrow-left" style="color: #ffffff; font-size: 14px;"></i> <span style="font-size: 14px;"><?php echo app('translator')->get('lang_v1.back_to_username', ['username' => session('previous_username')] ); ?></span>
            </a>
        <?php endif; ?>
      </div>

      <!-- Modern Navbar Right Menu -->
      <div class="navbar-custom-menu">

        <?php if(Module::has('Essentials')): ?>
          <?php if ($__env->exists('essentials::layouts.partials.header_part')) echo $__env->make('essentials::layouts.partials.header_part', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; ?>

        <div class="btn-group" style="position: static;">
          <button id="header_shortcut_dropdown" type="button" class="btn dropdown-toggle btn-flat pull-left m-8 btn-sm mt-10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-dropdown-anchor="parent" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-magic" style="color: #ffffff; font-size: 16px;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-right" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); backdrop-filter: blur(16px); background: rgba(0, 0, 0, 0.8); color: #ffffff; top: 100% !important; bottom: auto !important;">
            <?php if(config('app.env') != 'demo'): ?>
              <li><a href="<?php echo e(route('calendar'), false); ?>" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-calendar" style="color: #60a5fa; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;"><?php echo app('translator')->get('lang_v1.calendar'); ?></span>
              </a></li>
            <?php endif; ?>
            <?php if(Module::has('Essentials')): ?>
              <li><a href="#" class="btn-modal" data-href="<?php echo e(action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create']), false); ?>" data-container="#task_modal" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-tasks" style="color: #34d399; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;"><?php echo app('translator')->get( 'essentials::lang.add_to_do' ); ?></span>
              </a></li>
            <?php endif; ?>
            <?php if(auth()->user()->hasRole('Admin#' . auth()->user()->business_id)): ?>
              <li><a id="start_tour" href="#" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-map-o" style="color: #06b6d4; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;"><?php echo app('translator')->get('lang_v1.application_tour'); ?></span>
              </a></li>
            <?php endif; ?>
          </ul>
        </div>

        <style>
        /* Force proper dropdown positioning in header */
        .main-header .btn-group {
            position: relative !important;
        }
        .main-header .dropdown {
            position: relative !important;
        }
        .main-header .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: auto !important;
            margin-top: 5px !important;
            transform: none !important;
        }
        /* Right-aligned dropdowns for user and notifications */
        .main-header .user-menu .dropdown-menu,
        .main-header .notifications-menu .dropdown-menu {
            left: auto !important;
            right: 0 !important;
        }
        </style>
        <button id="btnCalculator" title="<?php echo app('translator')->get('lang_v1.calculator'); ?>" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 popover-default hidden-xs" data-toggle="popover" data-trigger="click" data-content='<?php echo $__env->make("layouts.partials.calculator", \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>' data-html="true" data-placement="bottom" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-calculator" style="color: #ffffff; font-size: 16px;" aria-hidden="true"></i>
        </button>
        
        <?php if($request->segment(1) == 'pos'): ?>
          <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_cash_register')): ?>
          <button type="button" id="register_details" title="<?php echo e(__('cash_register.register_details'), false); ?>" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".register_details_modal" 
          data-href="<?php echo e(action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails']), false); ?>" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-cash-register" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
          <?php endif; ?>
          <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('close_cash_register')): ?>
          <button type="button" id="close_register" title="<?php echo e(__('cash_register.close_register'), false); ?>" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".close_register_modal" 
          data-href="<?php echo e(action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister']), false); ?>" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-times-rectangle" style="color: #ef4444; font-size: 16px;"></i>
          </button>
          <?php endif; ?>
        <?php endif; ?>

        <?php if(in_array('pos_sale', $enabled_modules)): ?>
          <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sell.create')): ?>
            <a href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'create']), false); ?>" title="<?php echo app('translator')->get('sale.pos_sale'); ?>" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); padding: 6px 12px;">
              <i class="fa fa-shopping-cart" style="color: #10b981; margin-right: 8px; font-size: 16px;"></i> <span style="font-size: 14px; font-weight: 500;"><?php echo app('translator')->get('sale.pos_sale'); ?></span>
            </a>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('profit_loss_report.view')): ?>
          <button type="button" id="view_todays_profit" title="<?php echo e(__('home.todays_profit'), false); ?>" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-line-chart" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
        <?php endif; ?>

        <!-- Light Glass Clock Display -->
        <div class="m-8 pull-left mt-15 hidden-xs clock-display" style="color: #ffffff; background: rgba(0, 123, 191, 0.25); padding: 6px 12px; border-radius: 6px; backdrop-filter: blur(10px); border: 1px solid rgba(0, 172, 238, 0.3);">
            <i class="fa fa-clock-o" style="color: #60a5fa; margin-right: 8px; font-size: 16px;"></i>
            <strong style="font-size: 14px;"><?php echo e(\Carbon::createFromTimestamp(strtotime('now'))->format(session('business.date_format')), false); ?></strong>
        </div>

        <!-- Light Glass Announcements Button -->
        <button id="btnAnnouncements" title="<?php echo app('translator')->get('lang_v1.updates'); ?>" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 hidden-xs announcements-btn" onclick="featureshift.announcements();" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); margin-left: 15px;">
            <i class="fa fa-bullhorn" style="color: #06b6d4; font-size: 16px;" aria-hidden="true"></i>
        </button>

        <ul class="nav navbar-nav">
          <?php echo $__env->make('layouts.partials.header-notifications', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
          <!-- Modern User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Modern Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color: #ffffff; transition: all 0.3s ease; padding: 6px 12px; border-radius: 6px; background: rgba(0, 123, 191, 0.25); backdrop-filter: blur(10px); border: 1px solid rgba(0, 172, 238, 0.3); margin: 6px;">
              <!-- The user image in the navbar-->
              <?php
                $profile_photo = auth()->user()->media;
              ?>
              <?php if(!empty($profile_photo)): ?>
                <img src="<?php echo e($profile_photo->display_url, false); ?>" class="user-image" alt="User Image" style="border-radius: 50%; border: 2px solid rgba(203, 213, 225, 0.4);">
              <?php else: ?>
                <i class="fa fa-user-circle" style="color: #60a5fa; font-size: 20px; margin-right: 8px;"></i>
              <?php endif; ?>
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span style="color: #ffffff; font-weight: 500; font-size: 14px;"><?php echo e(Auth::User()->first_name, false); ?> <?php echo e(Auth::User()->last_name, false); ?></span>
            </a>
            <ul class="dropdown-menu" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); backdrop-filter: blur(16px); background: rgba(0, 0, 0, 0.8); min-width: 220px;">
              <!-- The user image in the menu -->
              <li class="user-header" style="background: rgba(255, 255, 255, 0.05); border-radius: 8px 8px 0 0; padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <?php if(!empty(Session::get('business.logo'))): ?>
                  <img src="<?php echo e(asset( 'uploads/business_logos/' . Session::get('business.logo') ), false); ?>" alt="Logo" style="border-radius: 8px; border: 2px solid rgba(203, 213, 225, 0.4);">
                <?php endif; ?>
                <p style="color: #ffffff; font-weight: 500; text-shadow: 0 1px 3px rgba(0,0,0,0.5); margin-top: 10px; font-size: 15px;">
                  <i class="fa fa-user-circle" style="color: #60a5fa; margin-right: 8px; font-size: 18px;"></i>
                  <?php echo e(Auth::User()->first_name, false); ?> <?php echo e(Auth::User()->last_name, false); ?>

                </p>
              </li>
              <!-- Menu Footer-->
              <li class="user-footer" style="padding: 15px; background: transparent;">
                <div class="pull-left">
                  <a href="<?php echo e(action([\App\Http\Controllers\UserController::class, 'getProfile']), false); ?>" class="btn btn-flat" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
                    <i class="fa fa-cog" style="color: #06b6d4; margin-right: 6px; font-size: 14px;"></i>
                    <span style="font-size: 13px;"><?php echo app('translator')->get('lang_v1.profile'); ?></span>
                  </a>
                </div>
                <div class="pull-right">
                  <a href="<?php echo e(action([\App\Http\Controllers\Auth\LoginController::class, 'logout']), false); ?>" class="btn btn-flat" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
                    <i class="fa fa-sign-out" style="color: #ef4444; margin-right: 6px; font-size: 14px;"></i>
                    <span style="font-size: 13px;"><?php echo app('translator')->get('lang_v1.sign_out'); ?></span>
                  </a>
                </div>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
  </header>

  <!-- Minimal responsive CSS for header layout -->
  <style>
  /* Ensure proper layout on tablets */
  @media (max-width: 1024px) and (min-width: 768px) {
      .main-header .navbar {
          display: flex;
          align-items: center;
          flex-wrap: nowrap;
          overflow-x: auto;
          scrollbar-width: none;
          -ms-overflow-style: none;
      }
      
      .main-header .navbar::-webkit-scrollbar {
          display: none;
      }
      
      .navbar-left-items {
          flex-shrink: 0 !important;
          margin-right: 8px !important;
      }
      
      .main-header .navbar-custom-menu {
          flex-shrink: 0;
          margin-left: auto;
      }
      
      .main-header .btn, 
      .main-header .btn-group {
          flex-shrink: 0;
          white-space: nowrap;
      }
      
      /* Hide subscription info on tablets to save space */
      .subscription-info-desktop {
          display: none !important;
      }
      
      /* Hide clock/date display on tablets to save space */
      .clock-display {
          display: none !important;
      }
      
      /* Hide announcements button on tablets to save space */
      .announcements-btn {
          display: none !important;
      }
      
      /* Make bank transfers button icon-only on tablets */
      .bank-transfers-btn .btn-text {
          display: none;
      }

      .bank-transfers-btn i {
          margin-right: 0 !important;
      }
  }
  
  /* Mobile layout adjustments */
  @media (max-width: 767px) {
      .navbar-left-items {
          display: flex !important;
          align-items: center;
          justify-content: center;
          margin: 4px 0 !important;
          gap: 4px !important;
      }
      
      .main-header .navbar-custom-menu {
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          gap: 4px;
      }
      
      /* Hide subscription info on mobile to save space */
      .subscription-info-desktop {
          display: none !important;
      }
      
      /* Make bank transfers button icon-only on mobile */
      .bank-transfers-btn .btn-text {
          display: none;
      }

      .bank-transfers-btn i {
          margin-right: 0 !important;
      }
  }
  </style><?php /**PATH /var/www/html/resources/views/layouts/partials/header.blade.php ENDPATH**/ ?>