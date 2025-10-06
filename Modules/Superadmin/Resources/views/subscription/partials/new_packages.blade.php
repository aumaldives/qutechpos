<div class="plans">;
@php
$totalRecords = count($packages);
$recordsPerBox = 4;
@endphp

@foreach ($packages as $index => $package)
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

	@if($package->id != "16")
	<div class="plan package-{{ $package->interval }}" style="display:none">
		<div class="title-row">
			<div class="title">
				<h3>{{$package->name}}</h3>
				
			</div>
			<div class="price">
				@php
				$interval_type = !empty($intervals[$package->interval]) ? $intervals[$package->interval] : __('lang_v1.' . $package->interval);
				@endphp

				@if($package->is_per_location_pricing)

				<h4>
					<span class="display_currency" data-currency_symbol="true" style="font-size: 1rem !important;">
						{{$package->price_per_location}}
					</span>
					<span style="font-size:0.8rem">(MVR {{ intval($package->price_per_location * 15.4)}})</span>
				</h4>

				<span>per location per 
				@if($package->interval == 'months')
					month
				@elseif($package->interval == 'years')
					year
				@else
					{{$interval_type}}
				@endif
				</span>

				@elseif($package->price != 0)

				<h4>
					<span class="display_currency" data-currency_symbol="true" style="font-size: 1rem !important;">
						{{$package->price}}
					</span>
					<span style="font-size:0.8rem">(MVR {{ intval($package->price * 15.4)}})</span>
				</h4>

				<span>{{$package->interval_count}} {{$interval_type}}</span>
				@else
				@lang('superadmin::lang.free_for_duration', ['duration' => $package->interval_count . ' ' . $interval_type])
				@endif
			</div>

		</div>
		<hr>
		<div class="features">
			<ul>
				<li><span class="fa-li"><i class="fa-duotone fa-building" style="--fa-primary-color: #2e7d32; --fa-secondary-color: #a5d6a7;"></i></span>
					<b>
						@if($package->location_count == 0)
						@lang('superadmin::lang.unlimited')
						@else
						{{$package->location_count}}
						@endif

						@lang('business.business_locations')
				</li>
				</b>
				<li><span class="fa-li"><i class="fa-duotone fa-shield-check" style="--fa-primary-color: #1976d2; --fa-secondary-color: #90caf9;"></i></span>Custom permissions</li>
				<li><span class="fa-li"><i class="fa-duotone fa-users" style="--fa-primary-color: #7b1fa2; --fa-secondary-color: #ce93d8;"></i></span>@if($package->user_count == 0)
					@lang('superadmin::lang.unlimited')
					@else
					{{$package->user_count}}
					@endif

					@lang('superadmin::lang.users')
				</li>
				<li><span class="fa-li"><i class="fa-duotone fa-boxes-stacked" style="--fa-primary-color: #f57c00; --fa-secondary-color: #ffcc02;"></i></span>@if($package->product_count == 0)
					@lang('superadmin::lang.unlimited')
					@else
					{{$package->product_count}}
					@endif

					@lang('superadmin::lang.products')
				</li>
				<li><span class="fa-li"><i class="fa-duotone fa-file-invoice-dollar" style="--fa-primary-color: #388e3c; --fa-secondary-color: #81c784;"></i></span>@if($package->invoice_count == 0)
					@lang('superadmin::lang.unlimited')
					@else
					{{$package->invoice_count}}
					@endif

					@lang('superadmin::lang.invoices')
				</li>

				@if(!empty($package->custom_permissions))
				@foreach($package->custom_permissions as $permission => $value)
				@isset($permission_formatted[$permission])
				<li><span class="fa-li">
					@if($permission == "manufacturing_module")
						<i class="fa-duotone fa-industry" style="--fa-primary-color: #d32f2f; --fa-secondary-color: #ffcdd2;"></i>
					@elseif($permission == "repair_module")
						<i class="fa-duotone fa-screwdriver-wrench" style="--fa-primary-color: #f57c00; --fa-secondary-color: #ffe0b2;"></i>
					@elseif($permission == "accounting_module")
						<i class="fa-duotone fa-calculator" style="--fa-primary-color: #1976d2; --fa-secondary-color: #bbdefb;"></i>
					@elseif($permission == "crm_module")
						<i class="fa-duotone fa-handshake" style="--fa-primary-color: #388e3c; --fa-secondary-color: #c8e6c9;"></i>
					@elseif($permission == "essentials_module")
						<i class="fa-duotone fa-briefcase" style="--fa-primary-color: #7b1fa2; --fa-secondary-color: #e1bee7;"></i>
					@elseif($permission == "woocommerce_module")
						<i class="fa-duotone fa-store" style="--fa-primary-color: #96588a; --fa-secondary-color: #d1c4e9;"></i>
					@elseif($permission == "connector_module")
						<i class="fa-duotone fa-plug" style="--fa-primary-color: #455a64; --fa-secondary-color: #cfd8dc;"></i>
					@elseif($permission == "quickbooks_module")
						<i class="fa-duotone fa-books" style="--fa-primary-color: #0077c5; --fa-secondary-color: #b3d9f2;"></i>
					@elseif($permission == "assetmanagement_module")
						<i class="fa-duotone fa-warehouse" style="--fa-primary-color: #5d4037; --fa-secondary-color: #d7ccc8;"></i>
					@elseif($permission == "productcatalogue_module")
						<i class="fa-duotone fa-tags" style="--fa-primary-color: #e91e63; --fa-secondary-color: #f8bbd9;"></i>
					@else
						<i class="fa-duotone fa-puzzle-piece" style="--fa-primary-color: #ff5722; --fa-secondary-color: #ffab91;"></i>
					@endif
				</span>
					@if($permission != "plasticbag_module" && $permission != "ageingreport_module")
						@if($permission == "connector_module")
							API Integration
						@else
							{{$permission_formatted[$permission]}}
						@endif
					@endif
				</li>
				@endisset
				@endforeach
				@endif

				@if($package->trial_days != 0)

				<li><span class="fa-li"><i class="fa-duotone fa-gift" style="--fa-primary-color: #e91e63; --fa-secondary-color: #f8bbd9;"></i></span> {{$package->trial_days}} @lang('superadmin::lang.trial_days')
				</li>
				@endif

			</ul>
		</div>
		<button type="button">
			@if($package->enable_custom_link == 1)
			<a href="{{$package->custom_link}}" class="btn btn-block btn-success">{{$package->custom_link_text}}</a>
			@else
			@if(isset($action_type) && $action_type == 'register')
			<a href="{{ route('business.getRegister') }}?package={{$package->id}}" class="btn btn-block btn-success">
				@if($package->price != 0)
				@lang('superadmin::lang.register_subscribe')
				@else
				@lang('superadmin::lang.register_free')
				@endif
			</a>
			@else
			<a href="{{action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package->id])}}" class="btn btn-block btn-success">
				@if($package->price != 0)
				@lang('superadmin::lang.pay_and_subscribe')
				@else
				@lang('superadmin::lang.subscribe')
				@endif
			</a>
			@endif
			@endif
		</button>

		<div class="row">
			<h5 style="margin-top:10px; color:RED; padding:10px; text-align:center">{{$package->description}}</h5>
		</div>
	</div>
	@endif


@endforeach
		</div>