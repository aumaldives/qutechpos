@extends('layouts.app')
@section('title', __('product.edit_product'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')

@php
$is_image_required = !empty($common_settings['is_product_image_required']) && empty($product->image);
@endphp

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>@lang('product.edit_product')</h1>
  <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
  {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'update'] , [$product->id] ), 'method' => 'PUT', 'id' => 'product_add_form',
  'class' => 'product_form', 'files' => true ]) !!}
  <input type="hidden" id="product_id" value="{{ $product->id }}">

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('name', __('product.product_name') . ':*') !!}
        {!! Form::text('name', $product->name, ['class' => 'form-control', 'required',
        'placeholder' => __('product.product_name')]); !!}
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('sku', __('product.sku') . ':*') !!} @show_tooltip(__('tooltip.sku'))
        {!! Form::text('sku', $product->sku, ['class' => 'form-control',
        'placeholder' => __('product.sku'), 'required']); !!}
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('barcode', __('product.barcode') . ':*') !!} @show_tooltip(__('tooltip.barcode'))
        {!! Form::text('barcode', $product->barcode, ['class' => 'form-control',
        'placeholder' => __('product.barcode')]); !!}
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
        {!! Form::select('barcode_type', $barcode_types, $product->barcode_type, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('unit_id', __('product.unit') . ':*') !!}
        <div class="input-group">
          {!! Form::select('unit_id', $units, $product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
          <span class="input-group-btn">
            <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat quick_add_unit btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
          </span>
        </div>
      </div>
    </div>

    <div class="col-sm-4 @if(!session('business.enable_sub_units')) hide @endif">
      <div class="form-group">
        {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

        <select name="sub_unit_ids[]" class="form-control select2" multiple id="sub_unit_ids">
          @foreach($sub_units as $sub_unit_id => $sub_unit_value)
          <option value="{{$sub_unit_id}}" @if(is_array($product->sub_unit_ids) &&in_array($sub_unit_id, $product->sub_unit_ids)) selected
            @endif>{{$sub_unit_value['name']}}</option>
          @endforeach
        </select>
      </div>
    </div>
    55
    @if(!empty($common_settings['enable_secondary_unit']))
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('secondary_unit_id', __('lang_v1.secondary_unit') . ':') !!} @show_tooltip(__('lang_v1.secondary_unit_help'))
        {!! Form::select('secondary_unit_id', $units, $product->secondary_unit_id, ['class' => 'form-control select2']); !!}
      </div>
    </div>
    @endif

    <div class="col-sm-4 @if(!session('business.enable_brand')) hide @endif">
      <div class="form-group">
        {!! Form::label('brand_id', __('product.brand') . ':') !!}
        <div class="input-group">
          {!! Form::select('brand_id', $brands, $product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
          <span class="input-group-btn">
            <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
          </span>
        </div>
      </div>
    </div>
    <div class="col-sm-4 @if(!session('business.enable_category')) hide @endif">
      <div class="form-group">
        {!! Form::label('category_id', __('product.category') . ':') !!}
        {!! Form::select('category_id', $categories, $product->category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
      </div>
    </div>

    <div class="col-sm-4 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
      <div class="form-group">
        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
        {!! Form::select('sub_category_id', $sub_categories, $product->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
        {!! Form::select('product_locations[]', $business_locations, $product->product_locations->pluck('id'), ['class' => 'form-control select2', 'multiple', 'id' => 'product_locations']); !!}
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-sm-4">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('enable_stock', 1, $product->enable_stock, ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
        </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
      </div>
    </div>
    <div class="col-sm-4" id="alert_quantity_div" @if(!$product->enable_stock) style="display:none" @endif>
      <div class="form-group">
        {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
        {!! Form::text('alert_quantity', $alert_quantity, ['class' => 'form-control input_number',
        'placeholder' => __('product.alert_quantity') , 'min' => '0']); !!}
      </div>
    </div>
    @if(!empty($common_settings['enable_product_warranty']))
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
        {!! Form::select('warranty_id', $warranties, $product->warranty_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
      </div>
    </div>
    @endif
    <!-- include module fields -->
    @if(!empty($pos_module_data))
    @foreach($pos_module_data as $key => $value)
    @if(!empty($value['view_path']))
    @includeIf($value['view_path'], ['view_data' => $value['view_data']])
    @endif
    @endforeach
    @endif
    <div class="clearfix"></div>
    <div class="col-sm-8">
      <div class="form-group">
        {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
        {!! Form::textarea('product_description', $product->product_description, ['class' => 'form-control']); !!}
      </div>
    </div>
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
        {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*', 'required' => $is_image_required]); !!}
        <small>
          <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]). @lang('lang_v1.aspect_ratio_should_be_1_1') @if(!empty($product->image)) <br> @lang('lang_v1.previous_image_will_be_replaced') @endif</p>
        </small>
      </div>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="form-group">
      {!! Form::label('product_brochure', __('lang_v1.product_brochure') . ':') !!}
      {!! Form::file('product_brochure', ['id' => 'product_brochure', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
      <small>
        <p class="help-block">
          @lang('lang_v1.previous_file_will_be_replaced')<br>
          @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
          @includeIf('components.document_help_text')
        </p>
      </small>
    </div>
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    @if(session('business.enable_product_expiry'))

    @if(session('business.expiry_type') == 'add_expiry')
    @php
    $expiry_period = 12;
    $hide = true;
    @endphp
    @else
    @php
    $expiry_period = null;
    $hide = false;
    @endphp
    @endif
    <div class="col-sm-4 @if($hide) hide @endif">
      <div class="form-group">
        <div class="multi-input">
          @php
          $disabled = false;
          $disabled_period = false;
          if( empty($product->expiry_period_type) || empty($product->enable_stock) ){
          $disabled = true;
          }
          if( empty($product->enable_stock) ){
          $disabled_period = true;
          }
          @endphp
          {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
          {!! Form::text('expiry_period', @num_format($product->expiry_period), ['class' => 'form-control pull-left input_number',
          'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;', 'disabled' => $disabled]); !!}
          {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], $product->expiry_period_type, ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type', 'disabled' => $disabled_period]); !!}
        </div>
      </div>
    </div>
    @endif
    <div class="col-sm-4">
      <div class="checkbox">
        <label>
          {!! Form::checkbox('enable_sr_no', 1, $product->enable_sr_no, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
        </label>
        @show_tooltip(__('lang_v1.tooltip_sr_no'))
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('not_for_selling', 1, $product->not_for_selling, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
        </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
      </div>
    </div>

    <div class="clearfix"></div>

    <!-- Rack, Row & position number -->
    @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
    <div class="col-md-12">
      <h4>@lang('lang_v1.rack_details'):
        @show_tooltip(__('lang_v1.tooltip_rack_details'))
      </h4>
    </div>
    @foreach($business_locations as $id => $location)
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('rack_' . $id, $location . ':') !!}


        @if(!empty($rack_details[$id]))
        @if(session('business.enable_racks'))
        {!! Form::text('product_racks_update[' . $id . '][rack]', $rack_details[$id]['rack'], ['class' => 'form-control', 'id' => 'rack_' . $id]); !!}
        @endif

        @if(session('business.enable_row'))
        {!! Form::text('product_racks_update[' . $id . '][row]', $rack_details[$id]['row'], ['class' => 'form-control']); !!}
        @endif

        @if(session('business.enable_position'))
        {!! Form::text('product_racks_update[' . $id . '][position]', $rack_details[$id]['position'], ['class' => 'form-control']); !!}
        @endif
        @else
        {!! Form::text('product_racks[' . $id . '][rack]', null, ['class' => 'form-control', 'id' => 'rack_' . $id, 'placeholder' => __('lang_v1.rack')]); !!}

        {!! Form::text('product_racks[' . $id . '][row]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}

        {!! Form::text('product_racks[' . $id . '][position]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]); !!}
        @endif

      </div>
    </div>
    @endforeach
    @endif


    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('weight', __('lang_v1.weight') . ':') !!}
        {!! Form::text('weight', $product->weight, ['class' => 'form-control', 'placeholder' => __('lang_v1.weight')]); !!}
      </div>
    </div>
    <div class="clearfix"></div>

    @php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
    $product_cf_details = !empty($custom_labels['product_cf_details']) ? $custom_labels['product_cf_details'] : [];
    @endphp
    <!--custom fields-->

    @foreach($product_custom_fields as $index => $cf)
    @if(!empty($cf))
    @php
    $db_field_name = 'product_custom_field' . $loop->iteration;
    $cf_type = !empty($product_cf_details[$loop->iteration]['type']) ? $product_cf_details[$loop->iteration]['type'] : 'text';
    $dropdown = !empty($product_cf_details[$loop->iteration]['dropdown_options']) ? explode(PHP_EOL, $product_cf_details[$loop->iteration]['dropdown_options']) : [];
    @endphp

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label($db_field_name, $cf . ':') !!}
        @if(in_array($cf_type, ['text', 'date']))
        <input type="{{$cf_type}}" name="{{$db_field_name}}" id="{{$db_field_name}}" value="{{$product->$db_field_name}}" class="form-control" placeholder="{{$cf}}">
        @elseif($cf_type == 'dropdown')
        {!! Form::select($db_field_name, $dropdown, $product->$db_field_name, ['placeholder' => $cf, 'class' => 'form-control select2']); !!}
        @endif
      </div>
    </div>
    @endif
    @endforeach

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('preparation_time_in_minutes', __('lang_v1.preparation_time_in_minutes') . ':') !!}
        {!! Form::number('preparation_time_in_minutes', $product->preparation_time_in_minutes, ['class' => 'form-control', 'placeholder' => __('lang_v1.preparation_time_in_minutes')]); !!}
      </div>
    </div>
    <!--custom fields-->
    @include('layouts.partials.module_form_part')
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
      <div class="form-group">
        {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
        {!! Form::select('tax', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
      </div>
    </div>

    <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
      <div class="form-group">
        {!! Form::label('tax_type', __('product.selling_price_tax_type') . ':*') !!}
        {!! Form::select('tax_type',['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $product->tax_type,
        ['class' => 'form-control select2', 'required']); !!}
      </div>
    </div>

    <div class="clearfix"></div>
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
        {!! Form::select('type', $product_types, $product->type, ['class' => 'form-control select2',
        'required','disabled', 'data-action' => 'edit', 'data-product_id' => $product->id ]); !!}
      </div>
    </div>

    <div class="form-group col-sm-12" id="product_form_part"></div>
    <input type="hidden" id="variation_counter" value="0">
    <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">
  </div>
  @endcomponent

  <div class="row">
    <input type="hidden" name="submit_type" id="submit_type">
    <div class="col-sm-12">
      <div class="text-center">
        <div class="btn-group">
          @if($selling_price_group_count)
          <button type="submit" value="submit_n_add_selling_prices" class="btn btn-warning submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
          @endif

          @can('product.opening_stock')
          <button type="submit" @if(empty($product->enable_stock)) disabled="true" @endif id="opening_stock_button" value="update_n_edit_opening_stock" class="btn bg-purple submit_product_form">@lang('lang_v1.update_n_edit_opening_stock')</button>
          @endif

          <button type="submit" value="save_n_add_another" class="btn bg-maroon submit_product_form">@lang('lang_v1.update_n_add_another')</button>

          <button type="submit" value="submit" class="btn btn-primary submit_product_form">@lang('messages.update')</button>
        </div>
      </div>
    </div>
  </div>
  {!! Form::close() !!}
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    __page_leave_confirmation('#product_add_form');

    $("#barcode").on("keydown", function(event) {
      if (event.key === "Enter") {
        event.preventDefault();
      }
    });

    $(document).on('mainFormPartLoaded', function() {

      var productId = getProductIdFromUrl();

      $.ajax({
        url: '/products/get-sub-unit-prices',
        type: 'GET',
        data: {
          product_id: productId
        },
        success: function(response) {
          if (response) {
            appendUnitPrices(response);
          }
        },
        error: function(xhr) {
          console.error(xhr.responseText);
        }
      });

    });

    function getProductIdFromUrl() {
      var url = window.location.pathname;
      var parts = url.split('/');
      return parts[2]; // Assuming the URL is /products/{id}/edit
    }

    function appendUnitPrices(data) {
      -
      $.each(data, function(index, unitPrice) {
          var selectedValue = unitPrice.sub_unit_id;
          var db_unit_id = unitPrice.id;
          var one = parseFloat(unitPrice.default_purchase_price).toFixed(2);
          var two = parseFloat(unitPrice.dpp_inc_tax).toFixed(2);
          var three = parseFloat(unitPrice.profit_percent).toFixed(2);
          var four = parseFloat(unitPrice.default_sell_price).toFixed(2);
          var five = parseFloat(unitPrice.sell_price_inc_tax).toFixed(2);
          var default_id = unitPrice.id;

          var id_suffix = '_' + selectedValue;
          var selectedText = $('#sub_unit_ids').find('option[value="' + selectedValue + '"]').text();
          var unit_id = $('#unit_id').val();
          var id_suffix = '_' + selectedValue;

          var price_row = `
        <tr class="${selectedValue}">
            <th>${selectedText}</th>
        </tr>
        <tr class="${selectedValue}">
            <td>
                <input type="hidden" name="n_sub_unit_id[]" value="${selectedValue}">
                <input type="hidden" name="n_id[]" value="${default_id}">
                <div class="col-sm-6">
                    <label for="single_dpp">Exc. tax:*</label>
                    <input class="form-control input-sm dpp input_number" placeholder="Exc. tax"  name="n_single_dpp[]" type="text" value="${one}" id="single_dpp${id_suffix}">
                </div>
                <div class="col-sm-6">
                    <label for="single_dpp_inc_tax">Inc. tax:*</label>
                    <input class="form-control input-sm dpp_inc_tax input_number" placeholder="Inc. tax" name="n_single_dpp_inc_tax[]" type="text" value="${two}" id="single_dpp_inc_tax${id_suffix}">
                </div>
            </td>
            <td>
                <br>
                <input class="form-control input-sm input_number" id="profit_percent${id_suffix}" name="n_profit_percent[]" type="text" value="${three}">
            </td>
            <td>
                <label><span class="dsp_label">Exc. Tax</span></label>
                <input class="form-control input-sm dsp input_number" placeholder="Exc. tax" id="single_dsp${id_suffix}" name="n_single_dsp[]" type="text" value="${four}">
                <input class="form-control input-sm hide input_number" placeholder="Inc. tax" id="single_dsp_inc_tax${id_suffix}" name="n_single_dsp_inc_tax[]" type="text" value="${five}">
            </td>
            <td>
                <div class="form-group">
                    <label for="variation_images">Product image:</label>
                    <input class="variation_images" accept="image/*" multiple="" name="n_variation_images[]" type="file">
                    <small><p class="help-block">Max File size: 5MB <br> Aspect ratio should be 1:1</p></small>
                </div>
            </td>
        </tr>
    `;

          if (selectedValue != unit_id) {
            $('.price_container tbody').append(price_row);
            addEventListeners(selectedValue);
          }
        }

      )
    };

  });


  $('#sub_unit_ids').on('select2:select', function(e) {
    var selectedOption = e.params.data;
    var selectedValue = selectedOption.id;
    var selectedText = selectedOption.text;

    var unit_id = $('#unit_id').val();

    console.log(unit_id);
    console.log(selectedValue);
    // Create unique IDs for the new row
    var id_suffix = '_' + selectedValue;

    var price_row = `
        <tr class="${selectedValue}">
            <th>${selectedText}</th>
        </tr>
        <tr class="${selectedValue}">
            <td>
                <input type="hidden" name="n_sub_unit_id[]" value="${selectedValue}">
                <div class="col-sm-6">
                    <label for="single_dpp">Exc. tax:*</label>
                    <input class="form-control input-sm dpp input_number" placeholder="Exc. tax"  name="n_single_dpp[]" type="text" value="" id="single_dpp${id_suffix}">
                </div>
                <div class="col-sm-6">
                    <label for="single_dpp_inc_tax">Inc. tax:*</label>
                    <input class="form-control input-sm dpp_inc_tax input_number" placeholder="Inc. tax" name="n_single_dpp_inc_tax[]" type="text" value="" id="single_dpp_inc_tax${id_suffix}">
                </div>
            </td>
            <td>
                <br>
                <input class="form-control input-sm input_number" id="profit_percent${id_suffix}" name="n_profit_percent[]" type="text" value="">
            </td>
            <td>
                <label><span class="dsp_label">Exc. Tax</span></label>
                <input class="form-control input-sm dsp input_number" placeholder="Exc. tax" id="single_dsp${id_suffix}"  name="n_single_dsp[]" type="text" value="">
                <input class="form-control input-sm hide input_number" placeholder="Inc. tax" id="single_dsp_inc_tax${id_suffix}" name="n_single_dsp_inc_tax[]" type="text" value="">
            </td>
            <td>
                <div class="form-group">
                    <label for="variation_images">Product image:</label>
                    <input class="variation_images" accept="image/*" multiple="" name="n_variation_images[]" type="file">
                    <small><p class="help-block">Max File size: 5MB <br> Aspect ratio should be 1:1</p></small>
                </div>
            </td>
        </tr>
    `;

    if (selectedValue != unit_id) {
      $('.price_container tbody').append(price_row);
      addEventListeners(selectedValue);
    }

  });

  function addEventListeners(selectedValue) {
    var id_suffix = '_' + selectedValue;


    $(document).on('change', `input#single_dpp${id_suffix}`, function(e) {
      var purchase_exc_tax = parseFloat($(this).val()) || 0;
      var tax_rate = parseFloat($('select#tax').find(':selected').data('rate')) || 0;
      var purchase_inc_tax = purchase_exc_tax * (1 + (tax_rate / 100));
      var profit_percent = parseFloat($(`input#profit_percent${id_suffix}`).val()) || 0;
      var selling_price = purchase_exc_tax * (1 + (profit_percent / 100));
      var selling_price_inc_tax = selling_price * (1 + (tax_rate / 100));

      __write_number($(`input#single_dpp_inc_tax${id_suffix}`), purchase_inc_tax);
      __write_number($(`input#single_dsp${id_suffix}`), selling_price);
      __write_number($(`input#single_dsp_inc_tax${id_suffix}`), selling_price_inc_tax);
    });


    $(document).on('change', 'select#tax', function() {
      $(`input#single_dpp${id_suffix}`).change();
    });

    $(document).on('change', `input#single_dpp_inc_tax${id_suffix}`, function(e) {
      var purchase_inc_tax = parseFloat($(this).val()) || 0;
      var tax_rate = parseFloat($('select#tax').find(':selected').data('rate')) || 0;
      var purchase_exc_tax = purchase_inc_tax / (1 + (tax_rate / 100));
      var profit_percent = parseFloat($(`input#profit_percent${id_suffix}`).val()) || 0;
      var selling_price = purchase_exc_tax * (1 + (profit_percent / 100));
      var selling_price_inc_tax = selling_price * (1 + (tax_rate / 100));

      __write_number($(`input#single_dpp${id_suffix}`), purchase_exc_tax);
      __write_number($(`input#single_dsp${id_suffix}`), selling_price);
      __write_number($(`input#single_dsp_inc_tax${id_suffix}`), selling_price_inc_tax);
    });

    $(document).on('change', `input#profit_percent${id_suffix}`, function(e) {
      $(`input#single_dpp${id_suffix}`).change();
    });

    $(document).on('change', `input#single_dsp${id_suffix}`, function(e) {
      var selling_price = parseFloat($(this).val()) || 0;
      var purchase_exc_tax = parseFloat($(`input#single_dpp${id_suffix}`).val()) || 0;
      var tax_rate = parseFloat($('select#tax').find(':selected').data('rate')) || 0;
      var profit_percent = ((selling_price - purchase_exc_tax) / purchase_exc_tax) * 100;
      var selling_price_inc_tax = selling_price * (1 + (tax_rate / 100));

      __write_number($(`input#profit_percent${id_suffix}`), profit_percent);
      __write_number($(`input#single_dsp_inc_tax${id_suffix}`), selling_price_inc_tax);
    });

    $(document).on('change', `input#single_dsp_inc_tax${id_suffix}`, function(e) {
      var selling_price_inc_tax = parseFloat($(this).val()) || 0;
      var tax_rate = parseFloat($('select#tax').find(':selected').data('rate')) || 0;
      var selling_price = selling_price_inc_tax / (1 + (tax_rate / 100));
      var purchase_exc_tax = parseFloat($(`input#single_dpp${id_suffix}`).val()) || 0;
      var profit_percent = ((selling_price - purchase_exc_tax) / purchase_exc_tax) * 100;

      __write_number($(`input#single_dsp${id_suffix}`), selling_price);
      __write_number($(`input#profit_percent${id_suffix}`), profit_percent);
    });

    $(document).on('change', 'select#tax_type', function() {
      toggle_dsp_input_v2(`${id_suffix}`);
    });
  }

  $('#sub_unit_ids').on('select2:unselect', function(e) {
    var removedOption = e.params.data;
    var removedValue = removedOption.id;

    $('.' + removedValue).remove();
  });


  function toggle_dsp_input_v2(id_suffix = '') {
    var tax_type = $('#tax_type').val();

    if (tax_type == 'inclusive') {
      $('.dsp_label').each(function() {
        $(this).text('Inc. Tax'); // Update with appropriate translation if needed
      });

      $(`input#single_dsp${id_suffix}`).each(function() {
        $(this).addClass('hide');
      });
      $(`input#single_dsp_inc_tax${id_suffix}`).each(function() {
        $(this).removeClass('hide');
      });

      $('.add-product-price-table')
        .find(`.variable_dsp_inc_tax${id_suffix}`)
        .each(function() {
          $(this).removeClass('hide');
        });
      $('.add-product-price-table')
        .find(`.variable_dsp${id_suffix}`)
        .each(function() {
          $(this).addClass('hide');
        });
    } else if (tax_type == 'exclusive') {
      $('.dsp_label').each(function() {
        $(this).text('Exc. Tax'); // Update with appropriate translation if needed
      });

      $(`input#single_dsp${id_suffix}`).each(function() {
        $(this).removeClass('hide');
      });
      $(`input#single_dsp_inc_tax${id_suffix}`).each(function() {
        $(this).addClass('hide');
      });

      $('.add-product-price-table')
        .find(`.variable_dsp_inc_tax${id_suffix}`)
        .each(function() {
          $(this).addClass('hide');
        });
      $('.add-product-price-table')
        .find(`.variable_dsp${id_suffix}`)
        .each(function() {
          $(this).removeClass('hide');
        });
    }
  }
</script>



@endsection