@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
<section class="content no-print">
	<input type="hidden" id="amount_rounding_method" value="{{$pos_settings['amount_rounding_method'] ?? ''}}">
	@if(!empty($pos_settings['allow_overselling']))
		<input type="hidden" id="is_overselling_allowed">
	@endif
	@if(session('business.enable_rp') == 1)
        <input type="hidden" id="reward_point_enabled">
    @endif
    @php
		$is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
		$is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
	@endphp
	{!! Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_pos_sell_form' ]) !!}
	<div class="row mb-12">
		<div class="col-md-12">
			<div class="row">
				<div class="@if(empty($pos_settings['hide_product_suggestion'])) col-md-7 @else col-md-10 col-md-offset-1 @endif no-padding pr-12">
					<div class="box box-solid mb-12 @if(!isMobile()) mb-40 @endif">
						<div class="box-body pb-0">
							{!! Form::hidden('location_id', $default_location->id ?? null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '']); !!}
							<!-- sub_type -->
							{!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
							<input type="hidden" id="item_addition_method" value="{{$business_details->item_addition_method}}">
								@include('sale_pos.partials.pos_form')

								@include('sale_pos.partials.pos_form_totals')

								@include('sale_pos.partials.payment_modal')

								@if(empty($pos_settings['disable_suspend']))
									@include('sale_pos.partials.suspend_note_modal')
								@endif

								@if(empty($pos_settings['disable_recurring_invoice']))
									@include('sale_pos.partials.recurring_invoice_modal')
								@endif
							</div>
						</div>
					</div>
				@if(empty($pos_settings['hide_product_suggestion']) && !isMobile())
				<div class="col-md-5 no-padding">
					@include('sale_pos.partials.pos_sidebar')
				</div>
				@endif
			</div>
		</div>
	</div>
	@include('sale_pos.partials.pos_form_actions')
	{!! Form::close() !!}
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
@if(empty($pos_settings['hide_product_suggestion']) && isMobile())
	@include('sale_pos.partials.mobile_product_suggestions')
@endif
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade cash_adjustment_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@include('sale_pos.partials.configure_search_modal')

@include('sale_pos.partials.recent_transactions_modal')

@include('sale_pos.partials.weighing_scale_modal')

@stop
@section('css')
	<!-- include module css -->
    @if(!empty($pos_module_data))
        @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop
@section('javascript')
	<script src="{{ asset('js/pos.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/modal-fix.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	<script>
		window.plastic_bag_api_url = '/plastic-bag/get-plastic-bag-types-for-pos';
	</script>
	<script src="{{ asset('js/pos_plastic_bags.js?v=' . $asset_v) }}"></script>
	@include('sale_pos.partials.keyboard_shortcuts')

	<!-- Call restaurant module if defined -->
    @if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules))
    	<script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <!-- include module js -->
    @if(!empty($pos_module_data))
	    @foreach($pos_module_data as $key => $value)
            @if(!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
	    @endforeach
	@endif

	
<script>
$(document).ready(function() {
    // Function to format a number to 2 decimal places
    function formatNumber(value) {
        return parseFloat(value).toFixed(2);
    }

    // Event listener for both main page and modal unit price inputs
    $('body').on('input', '.main_page_unit_price, .modal_unit_price', function() {
        var inputElement = $(this);
        
        // Get the value from the input that changed
        var changedValue = inputElement.val();
        
        // Find the corresponding main page and modal inputs
        var row = inputElement.closest('tr.product_row');
        var mainPageUnitPriceInput = row.find('input.main_page_unit_price');
        var modalUnitPriceInput = row.find('input.modal_unit_price');
        
        // Update the corresponding inputs
        mainPageUnitPriceInput.val(changedValue);
        modalUnitPriceInput.val(changedValue);
    });

    // Event listener for when the input field loses focus
    $('body').on('focusout', '.main_page_unit_price, .modal_unit_price', function() {
        var inputElement = $(this);
        
        // Get the value from the input that changed
        var changedValue = inputElement.val();
        
        // Format the input value with two decimal places (0.00)
        inputElement.val(formatNumber(changedValue));
    });

    // Event listener for opening the modal
    $('body').on('show.bs.modal', '.row_edit_product_price_model', function () {
        // Find the modal unit price input and update it with the main page value
        var modal = $(this);
        var mainPageUnitPriceInput = modal.closest('tr.product_row').find('input.main_page_unit_price');
        var modalUnitPriceInput = modal.find('input.modal_unit_price');
        var mainPageValue = formatNumber(mainPageUnitPriceInput.val());
        modalUnitPriceInput.val(mainPageValue);
    });

    // Event listener for saving the modal
    $('body').on('click', '.modal-save-button', function() {
        // Find the modal unit price input and format it
        var modal = $(this).closest('.modal');
        var modalUnitPriceInput = modal.find('input.modal_unit_price');
        modalUnitPriceInput.val(formatNumber(modalUnitPriceInput.val()));
    });
});
</script>



@endsection