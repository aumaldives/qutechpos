@foreach ($packages as $package)
	@if($package->is_private == 1 && !auth()->user()->can('superadmin'))
		@php
			continue;
		@endphp
	@endif
	@php
		$interval_type = !empty($intervals[$package->interval]) ? $intervals[$package->interval] : __('lang_v1.' . $package->interval);
	@endphp

	@php
        $restrictDate = $package->restrict_date;
    @endphp

    @if ($restrictDate !== null)
        @continue
    @endif

	<div class="plan basic package-{{ $package->interval }}">
		<div class="title-row">
			<div class="title">
				<h3>{{$package->name}}</h3>
			</div>
			<div class="price">
				@if($package->price != 0)
					@php 
						$price = $package->price;
						if(Request::get('cur') === 'mvr') {
							$mvrConversion = 15.40;
							$price = $price*$mvrConversion;
						}
					@endphp
					<h4>{{number_format($price,2)}} {{Request::get('cur') ? strtoupper(Request::get('cur')) : 'USD'}}</h4>
					<span>Per {{$package->interval_count}} {{$interval_type}}</span>
				@else
				<h4>{{number_format($package->price,2)}} {{Request::get('cur') ? strtoupper(Request::get('cur')) : 'USD'}}</h4>
					<span>@lang('superadmin::lang.free_for_duration', ['duration' => $package->interval_count . ' ' . $interval_type])</span>
				@endif
			</div>
		</div>
		<hr>
		<div class="features">
			<ul>
				<li>
					<span class="fa-li"><i class="fas fa-check-circle"></i></span>
					@if($package->location_count == 0)
						@lang('superadmin::lang.unlimited')
					@else
						{{$package->location_count}}
					@endif
					@lang('business.business_locations')
				</li>
				<li>
					<span class="fa-li"><i class="fas fa-check-circle"></i></span>
					@if($package->user_count == 0)
					@lang('superadmin::lang.unlimited')
					@else
						{{$package->user_count}}
					@endif

					@lang('superadmin::lang.users')
				</li>
				<li>
					<span class="fa-li"><i class="fas fa-check-circle"></i></span>
					@if($package->product_count == 0)
						@lang('superadmin::lang.unlimited')
					@else
						{{$package->product_count}}
					@endif
					@lang('superadmin::lang.products')
				</li>
				<li>
					<span class="fa-li"><i class="fas fa-check-circle"></i></span>

					@if($package->invoice_count == 0)
					    @lang('superadmin::lang.unlimited')
					@else
						{{$package->invoice_count}}
					@endif
                    @lang('superadmin::lang.invoices')
				</li>
			</ul>
		</div>
		<button type="button">
			@if($package->enable_custom_link == 1)
				<a href="{{$package->custom_link}}" class="text-white look-btn">{{$package->custom_link_text}}</a>
			@else
				@if(isset($action_type) && $action_type == 'register')
					<a href="{{ route('business.getRegister') }}?package={{$package->id}}" 
					class="text-white look-btn">
						@if($package->price != 0)
							@lang('superadmin::lang.register_subscribe')
						@else
							@lang('superadmin::lang.register_free')
						@endif
					</a>
				@else
					<a href="{{action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package->id])}}" 
					class="text-white look-btn">
						@if($package->price != 0)
							@lang('superadmin::lang.pay_and_subscribe')
						@else
							@lang('superadmin::lang.subscribe')
						@endif
					</a>
				@endif
			@endif
		</button>
	</div>
    @if($loop->iteration%3 == 0)
		</div><div class="plans">
    @endif
@endforeach