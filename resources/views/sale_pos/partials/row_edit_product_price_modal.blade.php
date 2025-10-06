<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel">{{$product->product_name}} - {{$product->sub_sku}}</h4>
		</div>
		<div class="modal-body">
			<div class="row">
				<div class="form-group col-xs-12 @if(!auth()->user()->can('edit_product_price_from_sale_screen')) hide @endif">
					@php
						$pos_unit_price = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price;
					@endphp
					<label>@lang('sale.unit_price')</label>
						<input type="text" name="products[{{$row_count}}][unit_price]" class="form-control pos_unit_price input_number mousetrap modal_unit_price" value="{{@num_format($pos_unit_price)}}" @if(!empty($pos_settings['enable_msp'])) data-rule-min-value="{{$pos_unit_price}}" data-msg-min-value="{{__('lang_v1.minimum_selling_price_error_msg', ['price' => @num_format($pos_unit_price)])}}" @endif>

				</div>
				@if(!auth()->user()->can('edit_product_price_from_sale_screen'))
					<div class="form-group col-xs-12">
						<strong>@lang('sale.unit_price'):</strong> {{@num_format(!empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price)}}
					</div>
				@endif
				<div class="form-group col-xs-12 col-sm-6 @if(!$edit_discount) hide @endif">
					<label>@lang('sale.discount_type')</label>
						{!! Form::select("products[$row_count][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type']); !!}
				</div>
				<div class="form-group col-xs-12 col-sm-6 @if(!$edit_discount) hide @endif">
					<label>@lang('sale.discount_amount')</label>
						{!! Form::text("products[$row_count][line_discount_amount]", @num_format($discount_amount), ['class' => 'form-control input_number row_discount_amount']); !!}
				</div>
				@if(!empty($discount))
					<div class="form-group col-xs-12">
						<p class="help-block">{!! __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]) !!}</p>
					</div>
				@endif
				<div class="form-group col-xs-12 {{$hide_tax}}">
					<label>@lang('sale.tax')</label>

					{!! Form::hidden("products[$row_count][item_tax]", @num_format($item_tax), ['class' => 'item_tax']); !!}
		
					{!! Form::select("products[$row_count][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); !!}
				</div>
				@if(!empty($warranties))
					<div class="form-group col-xs-12">
						<label>@lang('lang_v1.warranty')</label>
						{!! Form::select("products[$row_count][warranty_id]", $warranties, $warranty_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control']); !!}
					</div>
				@endif
				{{-- Quantity Update Field --}}
				<div class="form-group col-xs-12 col-sm-6">
					<label>@lang('sale.qty')</label>
					<div class="input-group">
						<input type="text" name="products[{{$row_count}}][quantity]" 
							   class="form-control input_number pos_quantity mousetrap modal_quantity" 
							   value="{{@format_quantity(!empty($product->quantity_ordered) ? $product->quantity_ordered : 1)}}" 
							   data-row-index="{{$row_count}}">
						<span class="input-group-addon">
							{{$product->unit_short_name}}
						</span>
					</div>
				</div>

				{{-- IMEI Selection Section for IMEI-enabled products --}}
				@if(!empty($product->enable_sr_no) && $product->enable_sr_no == 1)
				<div class="form-group col-xs-12">
					<label>@lang('lang_v1.imei_numbers')</label>
					<div class="imei_selection_container">
						<div class="row">
							<div class="col-sm-8">
								<input type="text" class="form-control imei_search_input" 
									   placeholder="@lang('lang_v1.search_imei')" 
									   data-row-index="{{$row_count}}"
									   data-product-id="{{$product->product_id}}"
									   data-variation-id="{{$product->variation_id}}"
									   data-location-id="{{session('user.business_location_id')}}">
							</div>
							<div class="col-sm-4">
								<button type="button" class="btn btn-primary btn-sm search_available_imeis" 
										data-row-index="{{$row_count}}">
									<i class="fa fa-search"></i> @lang('lang_v1.search_available_imeis')
								</button>
							</div>
						</div>
						
						{{-- Available IMEIs List --}}
						<div class="available_imeis_container" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
							<div class="no_imeis_message text-muted" style="padding: 10px; text-align: center;">
								@lang('lang_v1.click_search_to_load_available_imeis')
							</div>
						</div>

						{{-- Selected IMEIs Display --}}
						<div class="selected_imeis_container" style="margin-top: 15px;">
							<label>@lang('lang_v1.selected_imeis'):</label>
							<div class="selected_imeis_list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; min-height: 50px;">
								<div class="no_selection_message text-muted">@lang('lang_v1.no_imeis_selected')</div>
							</div>
							<input type="hidden" name="products[{{$row_count}}][selected_imeis]" class="selected_imeis_input" value="">
						</div>

						{{-- IMEI Selection Summary --}}
						<div class="imei_selection_summary" style="margin-top: 10px;">
							<small class="text-info">
								<span class="selected_imei_count">0</span> @lang('lang_v1.imeis_selected') / 
								<span class="required_imei_count">{{!empty($product->quantity_ordered) ? $product->quantity_ordered : 1}}</span> @lang('lang_v1.required')
							</small>
						</div>
					</div>
				</div>
				@endif

				<div class="form-group col-xs-12">
		      		<label>@lang('lang_v1.description')</label>
		      		<textarea class="form-control" name="products[{{$row_count}}][sell_line_note]" rows="3">{{$sell_line_note}}</textarea>
		      		<p class="help-block">@lang('lang_v1.sell_line_description_help')</p>
		      	</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary update-modal-quantities" data-row-index="{{$row_count}}">
				<i class="fa fa-refresh"></i> @lang('messages.update')
			</button>
			<button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div>
</div>