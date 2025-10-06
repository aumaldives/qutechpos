@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | ' . __('superadmin::lang.subscription'))

@section('content')

<link rel="stylesheet" href="{{ asset('css/internal-pricing.css?v=' . config('app.asset_version', 1)) }}">
<style>
	.pricing_table_container .plans .plan ul li {
		font-size: 14px;
	}

	.fa-solid,
	.fas {
		font-weight: 900;
	}

	.features ul li {
		font-size: 15px;
		font-weight: bold;
	}

	.features ul li span {
		font-size: 13px !important;
	}
</style>

<!-- Main content -->
<section class="content" style="background:#EFF7FD">

	@include('superadmin::layouts.partials.currency')

	<div class="pricing_table_container" style="min-height:auto">
		<div class="plans">
			<div class="plan">
				@if(!empty($active))
				@php
					$active_package_details = is_array($active->package_details) ? $active->package_details : json_decode($active->package_details, true) ?? [];
				@endphp
				<div class="title-row">
					<div class="title">
						<h3>{{$active_package_details['name'] ?? 'N/A'}}</h3>

					</div>
					<div class="price">
						@lang('superadmin::lang.running')
					</div>

				</div>
				<hr>
				<div class="features" style="height: auto;">
					<ul>
						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@lang('superadmin::lang.start_date') : {{@format_date($active->start_date)}} <br />
							</b>
						</li>

						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@lang('superadmin::lang.end_date') : {{@format_date($active->end_date)}} <br />
							</b>
						</li>

						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@lang('superadmin::lang.remaining', ['days' => \Carbon::today()->diffInDays($active->end_date)])
							</b>
						</li>
						
						@if($active->paid_via == 'stripe' && !empty($active->stripe_subscription_id))
						<li style="text-align: center; margin-top: 15px;">
							<a href="{{ route('subscription.stripe.manage') }}" class="btn btn-primary btn-sm">
								<i class="fa fa-cogs"></i> @lang('superadmin::lang.manage_subscription')
							</a>
						</li>
						@endif
					</ul>
				</div>
			</div>
			@else
			<h3 class="text-danger">@lang('superadmin::lang.no_active_subscription')</h3>
			@endif


			@if(!empty($nexts))
			@foreach($nexts as $next)
			@php
				$next_package_details = is_array($next->package_details) ? $next->package_details : json_decode($next->package_details, true) ?? [];
			@endphp
			<div class="plan" style="height: 290px;">
				<div class="title-row">
					<div class="title">
						<h3>{{$next_package_details['name'] ?? 'N/A'}}</h3>
					</div>
				</div>

				<hr>
				<div class="features" style="height: auto;">
					<ul>
						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@lang('superadmin::lang.start_date') : {{@format_date($next->start_date)}}
							</b>
						</li>

						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@lang('superadmin::lang.end_date') : {{@format_date($next->end_date)}}
							</b>
						</li>
					</ul>
				</div>
			</div>
			@endforeach
			@endif

			@if(!empty($waiting))
			@foreach($waiting as $row)
			@php
				$waiting_package_details = is_array($row->package_details) ? $row->package_details : json_decode($row->package_details, true) ?? [];
			@endphp
			<div class="plan">
				<div class="title-row">
					<div class="title">
						<h3>{{$waiting_package_details['name'] ?? 'N/A'}}</h3>
					</div>
				</div>
				<div class="features" style="height: auto;">
					<ul>
						<li><span class="fa-li"><i class="fas fa-check-circle"></i></span>
							<b>
								@if($row->paid_via == 'offline')
								@lang('superadmin::lang.waiting_approval')
								@else
								@lang('superadmin::lang.waiting_approval_gateway')
								@endif
							</b>
						</li>
					</ul>
				</div>
			</div>
			@endforeach
			@endif

		</div>
	</div>

	<!-- Collapsible Table grid -->
	<div class="box">
		<div class="box-header with-border" style="cursor: pointer;" data-toggle="collapse" data-target="#all-subscriptions-section" aria-expanded="false">
			<h3 class="box-title">
				<i class="fa fa-plus-circle" id="collapse-icon"></i>
				@lang('superadmin::lang.all_subscriptions')
			</h3>
			<div class="box-tools pull-right">
				<small class="text-muted">Click to expand</small>
			</div>
		</div>

		<div class="box-body collapse" id="all-subscriptions-section">
			<div class="row">
				<div class="col-xs-12">
					<div class="table-responsive">
						<!-- location table-->
						<table class="table table-bordered table-hover" id="all_subscriptions_table">
							<thead>
								<tr>
									<th>@lang( 'superadmin::lang.package_name' )</th>
									<th>@lang( 'superadmin::lang.start_date' )</th>
									<th>@lang( 'superadmin::lang.trial_end_date' )</th>
									<th>@lang( 'superadmin::lang.end_date' )</th>
									<th>@lang( 'superadmin::lang.price' )</th>
									<th>@lang( 'superadmin::lang.paid_via' )</th>
									<th>@lang( 'superadmin::lang.payment_transaction_id' )</th>
									<th>@lang( 'sale.status' )</th>
									<th>@lang( 'lang_v1.created_at' )</th>
									<th>@lang( 'business.created_by' )</th>
									<th>@lang('messages.action')</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- package section -->
	<!-- <div class="box">
		<div class="box-header">
			<h3 class="box-title">@lang('superadmin::lang.packages')</h3>
		</div>

		<div class="box-body">

		</div>
	</div> -->

	<div class="pricing_table_container">
	
    <div class="heading" style="margin-top: 20px;margin-bottom: 10px;">
        <h2>Our <b>Packages</b> </h2>
    </div>

		<div class="row switch-container text-center">
			<div class="switch-wrapper">
				<input id="monthly" type="radio" name="switch" checked>
				<input id="yearly" type="radio" name="switch">
				<label for="monthly">Monthly</label>
				<label for="yearly">Yearly</label>
				<span class="highlighter"></span>
			</div>
		</div>

		@include('superadmin::subscription.partials.new_packages_internal')

	</div>

</section>
@endsection

@section('javascript')

<script type="text/javascript">
	$(document).ready(function() {
		var subscriptionsTableInitialized = false;
		
		// Handle collapse/expand functionality
		$('[data-target="#all-subscriptions-section"]').on('click', function() {
			var icon = $('#collapse-icon');
			var isExpanded = $(this).attr('aria-expanded') === 'true';
			
			if (isExpanded) {
				// Collapsing
				icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
				$(this).attr('aria-expanded', 'false');
				$('.box-tools small').text('Click to expand');
			} else {
				// Expanding
				icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
				$(this).attr('aria-expanded', 'true');
				$('.box-tools small').text('Click to collapse');
				
				// Initialize DataTable only when first expanded
				if (!subscriptionsTableInitialized) {
					initializeSubscriptionsTable();
					subscriptionsTableInitialized = true;
				}
			}
		});
		
		function initializeSubscriptionsTable() {
			$('#all_subscriptions_table').DataTable({
				processing: true,
				serverSide: true,
				ajax: '{{action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'allSubscriptions'])}}',
				columns: [{
						data: 'package_name',
						name: 'P.name'
					},
					{
						data: 'start_date',
						name: 'start_date'
					},
					{
						data: 'trial_end_date',
						name: 'trial_end_date'
					},
					{
						data: 'end_date',
						name: 'end_date'
					},
					{
						data: 'package_price',
						name: 'package_price'
					},
					{
						data: 'paid_via',
						name: 'paid_via'
					},
					{
						data: 'payment_transaction_id',
						name: 'payment_transaction_id'
					},
					{
						data: 'status',
						name: 'status'
					},
					{
						data: 'created_at',
						name: 'created_at'
					},
					{
						data: 'created_by',
						name: 'created_by'
					},
					{
						data: 'action',
						name: 'action',
						searchable: false,
						orderable: false
					},
				],
				"fnDrawCallback": function(oSettings) {
					__currency_convert_recursively($('#all_subscriptions_table'), true);
				}
			});
		}

		$(".plan.package-months").show();

		$("input[name='switch']").change(function() {
			var filter = $(this).attr("id");
			if (filter === "monthly") {
				$(".plan.package-years").hide();
				$(".plan.package-months").show();
			} else {
				$(".plan.package-months").hide();
				$(".plan.package-years").show();
			}
		});

	});
</script>

@endsection