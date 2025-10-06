@inject('request', 'Illuminate\Http\Request')
<!-- Light Theme Header -->
<header class="main-header no-print" style="background: rgba(248, 250, 252, 0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);">
    <a href="{{route('home')}}" class="logo" style="background: transparent; transition: all 0.3s ease;">
      <span class="logo-lg" style="color: #1a202c; font-weight: 600; text-shadow: none;">
        {{ Session::get('business.name') }} 
        <i class="fa-duotone fa-wifi" style="--fa-primary-color: #10b981; --fa-secondary-color: #6ee7b7; margin-left: 8px;" id="online_indicator" title="Online"></i>
      </span> 
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation" style="background: transparent;">
      <!-- Light Theme Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" style="color: #374151; font-size: 18px; padding: 10px 12px; transition: all 0.3s ease; border-radius: 6px; margin: 4px; background: rgba(241, 245, 249, 0.7); border: 1px solid rgba(0, 0, 0, 0.06); min-width: 42px; text-align: center;">
        <i class="fa fa-bars" style="color: #374151; font-size: 16px;"></i>
        <span class="sr-only">Toggle navigation</span>
      </a>

      @if(Module::has('Superadmin'))
        @includeIf('superadmin::layouts.partials.active_subscription')
      @endif

        @if(!empty(session('previous_user_id')) && !empty(session('previous_username')))
            <a href="{{route('sign-in-as-user', session('previous_user_id'))}}" class="btn btn-flat m-8 btn-sm mt-10" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);"
                <i class="fa fa-arrow-left" style="color: #374151; font-size: 14px;"></i> <span style="font-size: 14px;">@lang('lang_v1.back_to_username', ['username' => session('previous_username')] )</span>
            </a>
        @endif

      <!-- Light Theme Navbar Right Menu -->
      <div class="navbar-custom-menu">

        @if(Module::has('Essentials'))
          @includeIf('essentials::layouts.partials.header_part_light')
        @endif

        <div class="btn-group">
          <button id="header_shortcut_dropdown" type="button" class="btn dropdown-toggle btn-flat pull-left m-8 btn-sm mt-10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
            <i class="fa fa-magic" style="color: #374151; font-size: 16px;"></i>
          </button>
          <ul class="dropdown-menu" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); backdrop-filter: blur(16px); background: rgba(248, 250, 252, 0.95); color: #374151;">
            @if(config('app.env') != 'demo')
              <li><a href="{{route('calendar')}}" style="padding: 10px 16px; transition: all 0.3s ease; color: #374151; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-calendar" style="color: #60a5fa; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang('lang_v1.calendar')</span>
              </a></li>
            @endif
            @if(Module::has('Essentials'))
              <li><a href="#" class="btn-modal" data-href="{{action([\Modules\Essentials\Http\Controllers\ToDoController::class, 'create'])}}" data-container="#task_modal" style="padding: 10px 16px; transition: all 0.3s ease; color: #374151; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-tasks" style="color: #34d399; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang( 'essentials::lang.add_to_do' )</span>
              </a></li>
            @endif
            @if(auth()->user()->hasRole('Admin#' . auth()->user()->business_id))
              <li><a id="start_tour" href="#" style="padding: 10px 16px; transition: all 0.3s ease; color: #374151; border-radius: 6px; margin: 2px 4px;">
                  <i class="fa fa-map-o" style="color: #06b6d4; margin-right: 10px; font-size: 16px;" aria-hidden="true"></i> <span style="font-size: 14px;">@lang('lang_v1.application_tour')</span>
              </a></li>
            @endif
          </ul>
        </div>
        <button id="btnCalculator" title="@lang('lang_v1.calculator')" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 popover-default hidden-xs" data-toggle="popover" data-trigger="click" data-content='@include("layouts.partials.calculator")' data-html="true" data-placement="bottom" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
            <i class="fa fa-calculator" style="color: #374151; font-size: 16px;" aria-hidden="true"></i>
        </button>
        
        @if($request->segment(1) == 'pos')
          @can('view_cash_register')
          <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".register_details_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails'])}}" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
            <i class="fa fa-cash-register" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
          @endcan
          @can('close_cash_register')
          <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10 btn-modal" data-container=".close_register_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister'])}}" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
            <i class="fa fa-times-rectangle" style="color: #ef4444; font-size: 16px;"></i>
          </button>
          @endcan
        @endif

        @if(in_array('pos_sale', $enabled_modules))
          @can('sell.create')
            <a href="{{action([\App\Http\Controllers\SellPosController::class, 'create'])}}" title="@lang('sale.pos_sale')" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06); padding: 6px 12px;">
              <i class="fa fa-shopping-cart" style="color: #10b981; margin-right: 8px; font-size: 16px;"></i> <span style="font-size: 14px; font-weight: 500;">@lang('sale.pos_sale')</span>
            </a>
          @endcan
        @endif

        @can('profit_loss_report.view')
          <button type="button" id="view_todays_profit" title="{{ __('home.todays_profit') }}" data-toggle="tooltip" data-placement="bottom" class="btn btn-flat pull-left m-8 btn-sm mt-10" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
            <i class="fa fa-line-chart" style="color: #10b981; font-size: 16px;" aria-hidden="true"></i>
          </button>
        @endcan

        <!-- Light Theme Clock Display -->
        <div class="m-8 pull-left mt-15 hidden-xs" style="color: #374151; background: rgba(59, 130, 246, 0.1); padding: 6px 12px; border-radius: 6px; backdrop-filter: blur(10px); border: 1px solid rgba(59, 130, 246, 0.2);">
            <i class="fa fa-clock-o" style="color: #3b82f6; margin-right: 8px; font-size: 16px;"></i>
            <strong style="font-size: 14px;">{{ @format_date('now') }}</strong>
        </div>

        <!-- Light Theme Announcements Button -->
        <button id="btnAnnouncements" title="@lang('lang_v1.updates')" type="button" class="btn btn-flat pull-left m-8 btn-sm mt-10 hidden-xs" onclick="featureshift.announcements();" style="background: rgba(0, 0, 0, 0.05); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.1); margin-left: 15px;">
            <i class="fa fa-bullhorn" style="color: #06b6d4; font-size: 16px;" aria-hidden="true"></i>
        </button>

        <ul class="nav navbar-nav">
          @include('layouts.partials.header-notifications')
          <!-- Light Theme User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Light Theme Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="color: #374151; transition: all 0.3s ease; padding: 6px 12px; border-radius: 6px; background: rgba(59, 130, 246, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(59, 130, 246, 0.2); margin: 6px;">
              <!-- The user image in the navbar-->
              @php
                $profile_photo = auth()->user()->media;
              @endphp
              @if(!empty($profile_photo))
                <img src="{{$profile_photo->display_url}}" class="user-image" alt="User Image" style="border-radius: 50%; border: 2px solid rgba(107, 114, 128, 0.4);">
              @else
                <i class="fa fa-user-circle" style="color: #3b82f6; font-size: 20px; margin-right: 8px;"></i>
              @endif
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span style="color: #374151; font-weight: 500; font-size: 14px;">{{ Auth::User()->first_name }} {{ Auth::User()->last_name }}</span>
            </a>
            <ul class="dropdown-menu" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); backdrop-filter: blur(16px); background: rgba(248, 250, 252, 0.95); min-width: 220px;">
              <!-- The user image in the menu -->
              <li class="user-header" style="background: rgba(241, 245, 249, 0.5); border-radius: 8px 8px 0 0; padding: 20px; border-bottom: 1px solid rgba(0, 0, 0, 0.06);">
                @if(!empty(Session::get('business.logo')))
                  <img src="{{ asset( 'uploads/business_logos/' . Session::get('business.logo') ) }}" alt="Logo" style="border-radius: 8px; border: 2px solid rgba(107, 114, 128, 0.4);">
                @endif
                <p style="color: #374151; font-weight: 500; text-shadow: none; margin-top: 10px; font-size: 15px;">
                  <i class="fa fa-user-circle" style="color: #3b82f6; margin-right: 8px; font-size: 18px;"></i>
                  {{ Auth::User()->first_name }} {{ Auth::User()->last_name }}
                </p>
              </li>
              <!-- Menu Footer-->
              <li class="user-footer" style="padding: 15px; background: transparent;">
                <div class="pull-left">
                  <a href="{{action([\App\Http\Controllers\UserController::class, 'getProfile'])}}" class="btn btn-flat" style="background: rgba(241, 245, 249, 0.7); color: #374151; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
                    <i class="fa fa-cog" style="color: #06b6d4; margin-right: 6px; font-size: 14px;"></i>
                    <span style="font-size: 13px;">@lang('lang_v1.profile')</span>
                  </a>
                </div>
                <div class="pull-right">
                  <a href="{{action([\App\Http\Controllers\Auth\LoginController::class, 'logout'])}}" class="btn btn-flat" style="background: rgba(241, 245, 249, 0.7); color: #374151; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
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