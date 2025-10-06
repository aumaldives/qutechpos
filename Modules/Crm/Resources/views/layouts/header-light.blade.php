@inject('request', 'Illuminate\Http\Request')
<!-- Light Theme CRM Header -->
<header class="main-header no-print" style="background: rgba(248, 250, 252, 0.85); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);">
    <a href="{{action([\Modules\Crm\Http\Controllers\DashboardController::class, 'index'])}}" class="logo" style="background: transparent; transition: all 0.3s ease;">
        <span class="logo-lg" style="color: #1a202c; font-weight: 600; text-shadow: none;">{{ Session::get('business.name') }}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation" style="background: transparent;">
        <!-- Light Theme Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" style="color: #374151; font-size: 18px; padding: 10px 12px; transition: all 0.3s ease; border-radius: 6px; margin: 4px; background: rgba(241, 245, 249, 0.7); border: 1px solid rgba(0, 0, 0, 0.06); min-width: 42px; text-align: center;">
            <i class="fa fa-bars" style="color: #374151; font-size: 16px;"></i>
            <span class="sr-only">Toggle navigation</span>
        </a>

        <!-- Light Theme Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <button id="btnCalculator" title="@lang('lang_v1.calculator')" type="button" class="btn btn-flat pull-left m-8 hidden-xs btn-sm mt-10 popover-default" data-toggle="popover" data-trigger="click" data-content='@include("layouts.partials.calculator")' data-html="true" data-placement="bottom" style="background: rgba(241, 245, 249, 0.7); color: #374151; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(0, 0, 0, 0.06);">
                <i class="fa fa-calculator" style="color: #374151; font-size: 16px;" aria-hidden="true"></i>
            </button>

            <div class="m-8 pull-left mt-15 hidden-xs" style="color: #374151; background: rgba(59, 130, 246, 0.1); padding: 6px 12px; border-radius: 6px; backdrop-filter: blur(10px); border: 1px solid rgba(59, 130, 246, 0.2);">
                <i class="fa fa-clock-o" style="color: #3b82f6; margin-right: 8px; font-size: 16px;"></i>
                <strong style="font-size: 14px;">{{ @format_date('now') }}</strong>
            </div>

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
                                <img src="{{ url( 'uploads/business_logos/' . Session::get('business.logo') ) }}" alt="Logo" style="border-radius: 8px; border: 2px solid rgba(107, 114, 128, 0.4);">
                            @endif
                            <p style="color: #374151; font-weight: 500; text-shadow: none; margin-top: 10px; font-size: 15px;">
                                <i class="fa fa-user-circle" style="color: #3b82f6; margin-right: 8px; font-size: 18px;"></i>
                                {{ Auth::User()->first_name }} {{ Auth::User()->last_name }}
                            </p>
                        </li>
                        <li class="user-footer" style="padding: 15px; background: transparent;">
                            <div class="pull-left">
                                <a href="{{action([\Modules\Crm\Http\Controllers\ManageProfileController::class, 'getProfile'])}}" class="btn btn-flat" style="background: rgba(241, 245, 249, 0.7); color: #374151; border: 1px solid rgba(0, 0, 0, 0.06); border-radius: 6px; transition: all 0.3s ease; padding: 6px 12px;">
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
                <!-- Control Sidebar Toggle Button -->
            </ul>
        </div>
    </nav>
</header>