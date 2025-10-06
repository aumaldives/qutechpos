@if($__is_essentials_enabled && $is_employee_allowed)
	<!-- Modern Clock In Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 clock_in_btn
		@if(!empty($clock_in)) hide @endif"
	    data-type="clock_in"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    title="@lang('essentials::lang.clock_in')" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-sign-in" style="color: #3b82f6; font-size: 16px;"></i>
	</button>

	<!-- Modern Clock Out Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 clock_out_btn
		@if(empty($clock_in)) hide @endif" 	
	    data-type="clock_out"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-html="true"
	    title="@lang('essentials::lang.clock_out') @if(!empty($clock_in))
                    <br>
                    <small>
                    	<b>@lang('essentials::lang.clocked_in_at'):</b> {{@format_datetime($clock_in->clock_in_time)}}
                    </small>
                    <br>
                    <small><b>@lang('essentials::lang.shift'):</b> {{ucfirst($clock_in->shift_name)}}</small>
                    @if(!empty($clock_in->start_time) && !empty($clock_in->end_time))
                    	<br>
                    	<small>
                    		<b>@lang('restaurant.start_time'):</b> {{@format_time($clock_in->start_time)}}<br>
                    		<b>@lang('restaurant.end_time'):</b> {{@format_time($clock_in->end_time)}}
                    	</small>
                    @endif
                @endif" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-sign-out" style="color: #f59e0b; font-size: 16px;"></i>
	</button>

	<!-- Modern OT In Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 ot_in_btn
		@if(!empty($ot_session)) hide @endif"
	    data-type="ot_in"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    title="@lang('essentials::lang.ot_in')" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-clock-o" style="color: #8b5cf6; font-size: 16px;"></i>
	</button>

	<!-- Modern OT Out Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 ot_out_btn
		@if(empty($ot_session)) hide @endif" 	
	    data-type="ot_out"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-html="true"
	    title="@lang('essentials::lang.ot_out') @if(!empty($ot_session))
                    <br>
                    <small>
                    	<b>@lang('essentials::lang.ot_started_at'):</b> {{@format_datetime($ot_session->start_time)}}
                    </small>
                @endif" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-stop-circle" style="color: #ef4444; font-size: 16px;"></i>
	</button>
@endif