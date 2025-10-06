<div class="modal-header">
    <h4 class="modal-title" id="modalTitle">
        <i class="fas fa-barcode" aria-hidden="true"></i> 
        @lang('lang_v1.imei_report') - <b>@lang('purchase.ref_no'):</b> #{{ $purchase->ref_no }}
    </h4>
</div>
<div class="modal-body">
    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            @lang('purchase.supplier'):
            <address>
                {!! $purchase->contact->contact_address !!}
                @if(!empty($purchase->contact->tax_number))
                    <br>@lang('contact.tax_no'): {{$purchase->contact->tax_number}}
                @endif
                @if(!empty($purchase->contact->mobile))
                    <br>@lang('contact.mobile'): {{$purchase->contact->mobile}}
                @endif
                @if(!empty($purchase->contact->email))
                    <br>@lang('business.email'): {{$purchase->contact->email}}
                @endif
            </address>
        </div>

        <div class="col-sm-4 invoice-col">
            @lang('business.business'):
            <address>
                <strong>{{ $purchase->business->name }}</strong>
                {{ $purchase->location->name }}
                @if(!empty($purchase->location->landmark))
                    <br>{{$purchase->location->landmark}}
                @endif
                @if(!empty($purchase->location->city) || !empty($purchase->location->state) || !empty($purchase->location->country))
                    <br>{{implode(',', array_filter([$purchase->location->city, $purchase->location->state, $purchase->location->country]))}}
                @endif
                
                @if(!empty($purchase->business->tax_number_1))
                    <br>{{$purchase->business->tax_label_1}}: {{$purchase->business->tax_number_1}}
                @endif

                @if(!empty($purchase->business->tax_number_2))
                    <br>{{$purchase->business->tax_label_2}}: {{$purchase->business->tax_number_2}}
                @endif

                @if(!empty($purchase->location->mobile))
                    <br>@lang('contact.mobile'): {{$purchase->location->mobile}}
                @endif
                @if(!empty($purchase->location->email))
                    <br>@lang('business.email'): {{$purchase->location->email}}
                @endif
            </address>
        </div>

        <div class="col-sm-4 invoice-col">
            <b>@lang('purchase.ref_no'):</b> #{{ $purchase->ref_no }}<br/>
            <b>@lang('messages.date'):</b> {{ @format_date($purchase->transaction_date) }}<br/>
            @if(!empty($purchase->status))
                <b>@lang('purchase.purchase_status'):</b> {{ __('lang_v1.' . $purchase->status) }}<br>
            @endif
        </div>
    </div>

    <br>

    @if($has_imeis)
        <div class="row">
            <div class="col-sm-12">
                <h4><i class="fas fa-barcode"></i> @lang('lang_v1.imei_numbers')</h4>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="bg-blue">
                                <th>#</th>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('product.sku')</th>
                                <th>@lang('purchase.purchase_quantity')</th>
                                <th>@lang('lang_v1.imei_numbers')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->purchase_lines as $purchase_line)
                                @if(!empty($purchase_line_imeis[$purchase_line->id]))
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $purchase_line->product->name }}
                                            @if( $purchase_line->product->type == 'variable')
                                                - {{ $purchase_line->variations->product_variation->name}}
                                                - {{ $purchase_line->variations->name}}
                                            @endif
                                        </td>
                                        <td>
                                            @if( $purchase_line->product->type == 'variable')
                                                {{ $purchase_line->variations->sub_sku}}
                                            @else
                                                {{ $purchase_line->product->sku }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $purchase_line->quantity }}</span> 
                                            @if(!empty($purchase_line->sub_unit)) 
                                                {{$purchase_line->sub_unit->short_name}} 
                                            @else 
                                                {{$purchase_line->product->unit->short_name}} 
                                            @endif
                                        </td>
                                        <td>
                                            <div class="imei-list">
                                                @foreach($purchase_line_imeis[$purchase_line->id] as $index => $imei)
                                                    <div class="imei-item" style="margin-bottom: 8px; padding: 5px; border: 1px solid #ddd; border-radius: 3px; background-color: #f9f9f9;">
                                                        <strong>IMEI {{ $index + 1 }}:</strong> 
                                                        <span style="font-family: monospace; font-size: 14px;">{{ $imei }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-sm-12 text-center">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    @lang('lang_v1.no_imei_numbers_found')
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12 text-center" style="margin-top: 30px;">
            <p class="text-muted">
                <small>@lang('lang_v1.imei_report_generated_on') {{ now()->format('d/m/Y H:i:s') }}</small>
            </p>
        </div>
    </div>
</div>