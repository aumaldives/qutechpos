@php
    $subtype = '';
@endphp
@if(!empty($transaction_sub_type))
    @php
        $subtype = '?sub_type='.$transaction_sub_type;
    @endphp
@endif
@foreach($sales as $sale)
    @if($sale->is_suspend)
        <div class="col-xs-6 col-sm-3 suspended-sale-item" 
            data-search-terms="{{ strtolower($sale->invoice_no . ' ' . $sale->name . ' ' . ($sale->additional_notes ?? '') . ' ' . ($sale->table->name ?? '')) }}">
            <div class="small-box bg-yellow">
                <div class="inner text-center">
                    @if(!empty($sale->additional_notes))
                        <p><i class="fa fa-edit"></i> {{$sale->additional_notes}}</p>
                    @endif
                  <p>{{$sale->invoice_no}}<br>
                  {{@format_date($sale->transaction_date)}}<br>
                  <strong><i class="fa fa-user"></i> {{$sale->name}}</strong></p>
                  <p><i class="fa fa-cubes"></i>@lang('lang_v1.total_items'): {{count($sale->sell_lines)}}<br>
                  <i class="fas fa-money-bill-alt"></i> @lang('sale.total'): <span class="display_currency" data-currency_symbol=true>{{$sale->final_total}}</span>
                  </p>
                  @if($is_tables_enabled && !empty($sale->table->name))
                      @lang('restaurant.table'): {{$sale->table->name}}
                  @endif
                  @if($is_service_staff_enabled && !empty($sale->service_staff))
                      <br>@lang('restaurant.service_staff'): {{$sale->service_staff->user_full_name}}
                  @endif
                </div>
                @if(auth()->user()->can('sell.update') || auth()->user()->can('direct_sell.update'))
                    <a href="{{action([\App\Http\Controllers\SellPosController::class, 'edit'], ['po' => $sale->id]).$subtype}}" class="small-box-footer bg-blue p-10">
                    @lang('sale.edit_sale') <i class="fa fa-arrow-circle-right"></i>
                    </a>
                @endif
                @if(auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete'))
                    <a href="{{action([\App\Http\Controllers\SellPosController::class, 'destroy'], ['po' => $sale->id])}}" class="small-box-footer delete-sale bg-red is_suspended">
                        @lang('messages.delete') <i class="fas fa-trash"></i>
                    </a>
                @endif
                @if(!auth()->user()->can('sell.update') && auth()->user()->can('edit_pos_payment'))
                    <a href="{{action([\App\Http\Controllers\SellPosController::class, 'edit'], ['po' => $sale->id]).'?payment_edit=true'.$subtype}}" class="small-box-footer bg-blue p-10">
                        <i class="fas fa-money-bill-alt"></i> @lang('purchase.add_edit_payment')
                    </a>
                @endif
            </div>
        </div>
    @endif
@endforeach