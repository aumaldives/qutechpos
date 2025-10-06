@inject('request', 'Illuminate\Http\Request')
<!-- Light Glass Header -->
<header class="main-header no-print" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
    <a href="{{route('home')}}" class="logo" style="background: transparent; transition: all 0.3s ease;">
      <span class="logo-lg" style="color: #ffffff; font-weight: 600; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
        {{ Session::get('business.name') }} 
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
        @if(Module::has('Superadmin'))
          <div class="subscription-info-desktop">
            @includeIf('superadmin::layouts.partials.active_subscription')
          </div>
        @endif

        @if(!empty(session('previous_user_id')) && !empty(session('previous_username')))
            <a href="{{route('sign-in-as-user', session('previous_user_id'))}}" class="btn btn-flat m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <i class="fa fa-arrow-left" style="color: #ffffff; font-size: 14px;"></i> <span style="font-size: 14px;">@lang('lang_v1.back_to_username', ['username' => session('previous_username')] )</span>
            </a>
        @endif
      </div>

      <!-- Modern Navbar Right Menu -->
      <div class="navbar-custom-menu">

        @if(Module::has('Essentials'))
          @includeIf('essentials::layouts.partials.header_part')
        @endif

        <div class="btn-group" style="position: static;">
          <button id="header_shortcut_dropdown" type="button" class="btn dropdown-toggle btn-flat pull-left m-8 btn-sm mt-10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-dropdown-anchor="parent" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-magic" style="color: #ffffff; font-size: 16px;"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-right" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); backdrop-filter: blur(16px); background: rgba(0, 0, 0, 0.8); color: #ffffff; top: 100% !important; bottom: auto !important;">
            @if(config('app.env') != 'demo')
              <li><a href="{{route('calendar')}}" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-calendar" style="color: #60a5fa; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang('lang_v1.calendar')</span>
              </a></li>
            @endif
            @if(Module::has('Essentials'))
              <li><a href="#" class="btn-modal" data-href="{{action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create'])}}" data-container="#task_modal" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-tasks" style="color: #34d399; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang( 'essentials::lang.add_to_do' )</span>
              </a></li>
            @endif
            @if(auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
              <li><a id="start_tour" href="#" style="padding: 10px 16px; transition: all 0.3s ease; color: #ffffff; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-map-o" style="color: #06b6d4; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang('lang_v1.application_tour')</span>
              </a></li>
            @endif
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
        <button id="btnCalculator" title="@lang('lang_v1.calculator')" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 popover-default hidden-xs" data-toggle="popover" data-trigger="click" data-content='@include("layouts.partials.calculator")' data-html="true" data-placement="bottom" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-calculator" style="color: #ffffff; font-size: 16px;" aria-hidden="true"></i>
        </button>
        
        @if($request->segment(1) == 'pos')
          @can('view_cash_register')
          <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".register_details_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails'])}}" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-cash-register" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
          @endcan
          @can('close_cash_register')
          <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".close_register_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister'])}}" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-times-rectangle" style="color: #ef4444; font-size: 16px;"></i>
          </button>
          @endcan
        @endif

        @if(in_array('pos_sale', $enabled_modules))
          @can('sell.create')
            <a href="{{action([\App\Http\Controllers\SellPosController::class, 'create'])}}" title="@lang('sale.pos_sale')" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); padding: 6px 12px;">
              <i class="fa fa-shopping-cart" style="color: #10b981; margin-right: 8px; font-size: 16px;"></i> <span style="font-size: 14px; font-weight: 500;">@lang('sale.pos_sale')</span>
            </a>
          @endcan
        @endif

        @can('profit_loss_report.view')
          <button type="button" id="view_todays_profit" title="{{ __('home.todays_profit') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="fa fa-line-chart" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
        @endcan

        <!-- Light Glass Clock Display -->
        <div class="m-8 pull-left mt-15 hidden-xs clock-display" style="color: #ffffff; background: rgba(0, 123, 191, 0.25); padding: 6px 12px; border-radius: 6px; backdrop-filter: blur(10px); border: 1px solid rgba(0, 172, 238, 0.3);">
            <i class="fa fa-clock-o" style="color: #60a5fa; margin-right: 8px; font-size: 16px;"></i>
            <strong style="font-size: 14px;">{{ @format_date('now') }}</strong>
        </div>

        <!-- Light Glass Announcements Button -->
        <button id="btnAnnouncements" title="@lang('lang_v1.updates')" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 hidden-xs announcements-btn" onclick="featureshift.announcements();" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); margin-left: 15px;">
            <i class="fa fa-bullhorn" style="color: #06b6d4; font-size: 16px;" aria-hidden="true"></i>
        </button>

        <ul class="nav navbar-nav">
          @include('layouts.partials.header-notifications')
          <!-- Modern User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Modern Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color: #ffffff; transition: all 0.3s ease; padding: 6px 12px; border-radius: 6px; background: rgba(0, 123, 191, 0.25); backdrop-filter: blur(10px); border: 1px solid rgba(0, 172, 238, 0.3); margin: 6px;">
              <!-- The user image in the navbar-->
              @php
                $profile_photo = auth()->user()->media;
              @endphp
              @if(!empty($profile_photo))
                <img src="{{$profile_photo->display_url}}" class="user-image" alt="User Image" style="border-radius: 50%; border: 2px solid rgba(203, 213, 225, 0.4);">
              @else
                <i class="fa fa-user-circle" style="color: #60a5fa; font-size: 20px; margin-right: 8px;"></i>
              @endif
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span style="color: #ffffff; font-weight: 500; font-size: 14px;">{{ Auth::User()->first_name }} {{ Auth::User()->last_name }}</span>
            </a>
            <ul class="dropdown-menu" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); backdrop-filter: blur(16px); background: rgba(0, 0, 0, 0.8); min-width: 220px;">
              <!-- The user image in the menu -->
              <li class="user-header" style="background: rgba(255, 255, 255, 0.05); border-radius: 8px 8px 0 0; padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                @if(!empty(Session::get('business.logo')))
                  <img src="{{ asset( 'uploads/business_logos/' . Session::get('business.logo') ) }}" alt="Logo" style="border-radius: 8px; border: 2px solid rgba(203, 213, 225, 0.4);">
                @endif
                <p style="color: #ffffff; font-weight: 500; text-shadow: 0 1px 3px rgba(0,0,0,0.5); margin-top: 10px; font-size: 15px;">
                  <i class="fa fa-user-circle" style="color: #60a5fa; margin-right: 8px; font-size: 18px;"></i>
                  {{ Auth::User()->first_name }} {{ Auth::User()->last_name }}
                </p>
              </li>
              <!-- Menu Footer-->
              <li class="user-footer" style="padding: 15px; background: transparent;">
                <div class="pull-left">
                  <a href="{{action([\App\Http\Controllers\UserController::class, 'getProfile'])}}" class="btn btn-flat" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
                    <i class="fa fa-cog" style="color: #06b6d4; margin-right: 6px; font-size: 14px;"></i>
                    <span style="font-size: 13px;">@lang('lang_v1.profile')</span>
                  </a>
                </div>
                <div class="pull-right">
                  <a href="{{action([\App\Http\Controllers\Auth\LoginController::class, 'logout'])}}" class="btn btn-flat" style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
                    <i class="fa fa-sign-out" style="color: #ef4444; margin-right: 6px; font-size: 14px;"></i>
                    <span style="font-size: 13px;">@lang('lang_v1.sign_out')</span>
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
  </style>