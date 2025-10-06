<!-- default value -->
@php
    $go_back_url = action([\App\Http\Controllers\SellPosController::class, 'index']);
    $transaction_sub_type = '';
    $view_suspended_sell_url = action([\App\Http\Controllers\SellController::class, 'index']).'?suspended=1';
    $pos_redirect_url = action([\App\Http\Controllers\SellPosController::class, 'create']);
@endphp

@if(!empty($pos_module_data))
    @foreach($pos_module_data as $key => $value)
        @php
            if(!empty($value['go_back_url'])) {
                $go_back_url = $value['go_back_url'];
            }

            if(!empty($value['transaction_sub_type'])) {
                $transaction_sub_type = $value['transaction_sub_type'];
                $view_suspended_sell_url .= '&transaction_sub_type='.$transaction_sub_type;
                $pos_redirect_url .= '?sub_type='.$transaction_sub_type;
            }
        @endphp
    @endforeach
@endif
<input type="hidden" name="transaction_sub_type" id="transaction_sub_type" value="{{$transaction_sub_type}}">
@inject('request', 'Illuminate\Http\Request')
<div class="col-md-12 no-print pos-header">
  <input type="hidden" id="pos_redirect_url" value="{{$pos_redirect_url}}">
  <div class="row">
    <div class="col-md-6">
      <div class="m-6 mt-5" style="display: flex;">
        <p ><strong>@lang('sale.location'): &nbsp;</strong> 
          @if(empty($transaction->location_id))
            @if(count($business_locations) > 1)
            <div style="width: 28%;margin-bottom: 5px;">
               {!! Form::select('select_location_id', $business_locations, $default_location->id ?? null , ['class' => 'form-control input-sm',
                'id' => 'select_location_id', 
                'required', 'autofocus'], $bl_attributes); !!}
            </div>
            @else
              {{$default_location->name}}
            @endif
          @endif

          @if(!empty($transaction->location_id)) {{$transaction->location->name}} @endif &nbsp; <span class="curr_datetime">{{ @format_datetime('now') }}</span> <i class="fas fa-keyboard hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('sale_pos.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i>
        </p>
      </div>
    </div>
    <div class="col-md-6">
      <a href="{{$go_back_url}}" title="{{ __('lang_v1.go_back') }}" class="btn btn-info btn-flat m-6 btn-xs m-5 pull-right">
        <strong><i class="fas fa-arrow-left fa-lg"></i></strong>
      </a>
      @if(!empty($pos_settings['inline_service_staff']))
        <button type="button" id="show_service_staff_availability" title="{{ __('lang_v1.service_staff_availability') }}" class="btn btn-primary btn-flat m-6 btn-xs m-5 pull-right" data-container=".view_modal" 
          data-href="{{ action([\App\Http\Controllers\SellPosController::class, 'showServiceStaffAvailibility'])}}">
            <strong><i class="fas fa-users fa-lg"></i></strong>
        </button>
      @endif

      @can('close_cash_register')
      <button type="button" id="close_register" title="{{ __('cash_register.close_register') }}" class="btn btn-danger btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".close_register_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister'])}}">
            <strong><i class="fas fa-times-circle fa-lg"></i></strong>
      </button>
      @endcan
      
      @can('view_cash_register')
      <button type="button" id="register_details" title="{{ __('cash_register.register_details') }}" class="btn btn-success btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".register_details_modal" 
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails'])}}">
            <strong><i class="fas fa-cash-register fa-lg" aria-hidden="true"></i></strong>
      </button>
      
      @endcan

      <button type="button" class="btn btn-danger btn-flat m-6 btn-xs m-5 pull-right popover-default" id="return_sale" title="@lang('lang_v1.sell_return')" data-toggle="popover" data-trigger="click" data-content='<div class="m-8"><input type="text" class="form-control" placeholder="@lang("sale.invoice_no")" id="send_for_sell_return_invoice_no"></div><div class="w-100 text-center"><button type="button" class="btn btn-danger" id="send_for_sell_return">@lang("lang_v1.send")</button></div>' data-html="true" data-placement="bottom">
            <strong><i class="fas fa-undo fa-lg"></i></strong>
      </button>

      <button type="button" title="{{ __('lang_v1.full_screen') }}" class="btn btn-primary btn-flat m-6 hidden-xs btn-xs m-5 pull-right" id="full_screen">
            <strong><i class="fas fa-expand fa-lg"></i></strong>
      </button>

      <button type="button" id="view_suspended_sales" title="{{ __('lang_v1.view_suspended_sales') }}" class="btn bg-yellow btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".view_modal" 
          data-href="{{$view_suspended_sell_url}}">
            <strong><i class="fas fa-pause-circle fa-lg"></i></strong>
      </button>
      @if(empty($pos_settings['hide_product_suggestion']) && isMobile())
        <button type="button" title="{{ __('lang_v1.view_products') }}"   
          data-placement="bottom" class="btn btn-success btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-toggle="modal" data-target="#mobile_product_suggestion_modal">
            <strong><i class="fas fa-cubes fa-lg"></i></strong>
        </button>
      @endif

      @if(Session::get('business.name') == 'Agro Mart')
        <button type="button" id="customer-screen-btn" title="Customer Screen" data-toggle="tooltip" data-placement="bottom" class="btn btn-success btn-flat m-6 btn-xs m-5 pull-right" onclick="window.open('{{ route('pos.customer-display') }}', '_blank', 'location=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=1024,height=768')">
          <i class="fas fa-desktop fa-lg"></i>
          <strong>Customer Screen</strong>
        </button>

        <button type="button" id="bank_transfers_btn" title="Bank Transfers" data-toggle="tooltip" data-placement="bottom" class="btn bg-purple btn-flat m-6 btn-xs m-5 pull-right">
          <i class="fa fa-university fa-lg"></i>
          <strong>Bank Transfers</strong>
        </button>
      @endif

        @if(in_array('pos_sale', $enabled_modules) && !empty($transaction_sub_type))
          @can('sell.create')
            <a href="{{action([\App\Http\Controllers\SellPosController::class, 'create'])}}" title="@lang('sale.pos_sale')" class="btn btn-success btn-flat m-6 btn-xs m-5 pull-right">
              <strong><i class="fas fa-cash-register"></i> &nbsp; @lang('sale.pos_sale')</strong>
            </a>
          @endcan
        @endif
        @can('view_cash_register')
        <button type="button" id="cash_adjustment" title="{{ __('cash_register.cash_adjustment') }}" class="btn btn-warning btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".cash_adjustment_modal"
          data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCashAdjustment'])}}">
            <strong><i class="fas fa-money-bill-alt fa-lg" aria-hidden="true"></i> @lang('cash_register.cash_adjustment')</strong>
        </button>
        @endcan

    </div>
    
  </div>
</div>
