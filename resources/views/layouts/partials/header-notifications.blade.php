@php
  $all_notifications = auth()->user()->notifications;
  $unread_notifications = $all_notifications->where('read_at', null);
  $total_unread = count($unread_notifications);
@endphp
<!-- Modern Notifications -->
<li class="dropdown notifications-menu">
  <a href="#" class="dropdown-toggle load_notifications" data-toggle="dropdown" id="show_unread_notifications" data-loaded="false" style="color: #ffffff; transition: all 0.3s ease; padding: 6px 12px; border-radius: 6px; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); margin: 6px; position: relative;">
    <i class="fa fa-bell" style="color: #fbbf24; font-size: 16px;"></i>
    @if(!empty($total_unread))
      <span class="notifications_count" style="position: absolute; top: -5px; right: -5px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 1px solid rgba(0, 172, 238, 0.3); box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);">{{$total_unread}}</span>
    @endif
  </a>
  <ul class="dropdown-menu" style="border-radius: 8px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); backdrop-filter: blur(20px); background: rgba(0, 0, 0, 0.8); min-width: 320px; margin-top: 8px;">
    <!-- Light Glass header -->
    <li class="header" style="background: rgba(255, 255, 255, 0.05); color: #ffffff; padding: 12px 16px; border-radius: 8px 8px 0 0; text-align: center; font-weight: 500; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
      <i class="fa fa-bell" style="color: #fbbf24; margin-right: 8px; font-size: 16px;"></i>
      @if(!empty($total_unread))
        <span style="font-size: 14px;">You have {{$total_unread}} unread notifications</span>
      @else
        <span style="font-size: 14px;">No new notifications</span>
      @endif
    </li>
    <li>
      <!-- inner menu: contains the actual data -->
      <ul class="menu" id="notifications_list" style="max-height: 280px; overflow-y: auto; padding: 8px 0; color: #ffffff;">
      </ul>
    </li>
    
    @if(count($all_notifications) > 10)
      <li class="footer load_more_li" style="padding: 8px 16px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center;">
        <a href="#" class="load_more_notifications" style="color: rgba(255, 255, 255, 0.8); text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
          <i class="fa fa-chevron-down" style="color: #60a5fa; margin-right: 6px; font-size: 14px;"></i>
          <span style="font-size: 13px;">@lang('lang_v1.load_more')</span>
        </a>
      </li>
    @endif
  </ul>
</li>

<input type="hidden" id="notification_page" value="1">