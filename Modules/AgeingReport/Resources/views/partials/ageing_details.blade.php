<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title col-sm-10 col-md-10"><b>{{$getContactDetails->name}}</b> </h4>
        </div>
        <?php
        $totalquantity = 0;
        $totalgross = 0;
        $totaltax = 0;
        ?>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered " id="ageing_detail_report">
                            <thead>
                                <tr style="background-color: #706db1;color:#fff;">
                                    <th>Date:</th>
                                    @if ($getContactDetails->type == 'supplier')
                                    <th>Reference number</th>
                                    <th>Location</th>
                                    <th>Supplier</th>
                                    <th>Purchase Status</th>
                                    <th>Payment Status</th>
                                    
                                    @endif
                                    <th>{{$label}}</th>
                                    @if($getContactDetails->type=='customer')
                                    <th>Invoice</th>
                                    @endif
                                    @if ($getContactDetails->type == 'supplier')
                                    <th>@lang('report.total_purchase')</th>
                                    <th>@lang('lang_v1.total_purchase_return')</th>
                                    @endif
                                    @if($getContactDetails->type=='customer')
                                    <th>@lang('report.total_sell')</th>
                                    <th>@lang('lang_v1.total_sell_return')</th>
                                    @endif
                                    <th>@lang('lang_v1.opening_balance_due')</th>
                                    <th>@lang('report.total_due')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $row)
                                <?php
                                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid);

                                    if ($getContactDetails->type == 'supplier') {
                                        $due -= $row->opening_balance - $row->opening_balance_paid;
                                    } else {
                                        $due += $row->opening_balance - $row->opening_balance_paid;
                                    }
                                    $invoiceno = 'Balance B/F';
                                    if ($row->invoice_no) {
                                        $invoiceno = $row->invoice_no;
                                    }
                                    $ref_no = 'Balance B/F';
                                    if ($row->ref_no) {
                                        $ref_no = $row->ref_no;
                                    }

                                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid);
                                    if ($contactType == 'supplier') {
                                        $due -= $row->opening_balance - $row->opening_balance_paid;
                                    } else {
                                        $due += $row->opening_balance - $row->opening_balance_paid;
                                    }
                                    $status = $row->status;
                                    if($status == 'ordered'){
                                        $statusbg = 'bg-aqua';
                                    }elseif($status == 'pending'){
                                        $statusbg = 'bg-red';
                                    }elseif ($status == 'received') {
                                        $statusbg = 'bg-light-green';
                                    }
                                    $payment_status = $row->payment_status;
                                    if($payment_status == 'paid'){
                                        $pstatusbg = 'bg-light-green';
                                    }elseif($payment_status == 'due'){
                                        $pstatusbg = 'bg-yellow';
                                    }elseif ($payment_status == 'partial') {
                                        $pstatusbg = 'bg-red';
                                    }
                                    ?>
                                @if($due != '0')
                                <tr>
                                    

                                    <td>{{@format_date($row->transaction_date)}}</td>
                                    @if($getContactDetails->type=='supplier')
                                    <td>
                                        @if($row->type == 'sell')
                                        <a data-href="{{ route('sell.show', [$row->transaction_id]) }}" href="#" data-container=".view_modal" class="btn-modal">{{$ref_no}}</a>
                                        
                                        @elseif($row->type == 'purchase')                                       
                                        <a data-href="{{ route('purchase.show', ['transaction_id' => $row->transaction_id]) }}" href="#" data-container=".view_modal" class="btn-modal">{{ $ref_no }}</a>
       
                                        @else
                                        <a data-href="{{ route('sell.show', [$row->transaction_id]) }}" href="#" data-container=".view_modal" class="btn-modal">{{$ref_no}}</a>
                                        @endif
                                    </td>
                                    <td>{{$row->location_name}}</td>
                                    <td>{{$row->name}}</td>
                                    <td><span class="label {{$statusbg}} status-label" data-status-name="Received" data-orig-value="received">{{$status}}</span></td>
                                    <td><span class="label {{$pstatusbg}} status-label" data-status-name="Received" data-orig-value="received">{{$payment_status}}</span></td>
                                    @endif
                                    <th><span class="display_currency total_due_day" data-orig-value="{{$row->total_purchase}}" data-currency_symbol=true>{{$due}}</span></th>
                                    @if($getContactDetails->type=='customer')
                                    <td>
                                      

                                        @if($row->type == 'sell')
    <a data-href="{{ route('sell.show', [$row->transaction_id]) }}"  href="#" data-container=".view_modal" class="btn-modal">{{$invoiceno}}</a>
    
@elseif($row->type == 'purchase')
    <a data-href="{{ route('purchase.show', ['transaction_id' => $row->transaction_id]) }}"  href="#" data-container=".view_modal" class="btn-modal">{{$invoiceno}}</a>
    
@else
    <a data-href="{{ route('sell.show', [$row->transaction_id]) }}"  href="#" data-container=".view_modal" class="btn-modal">{{$invoiceno}}</a>
@endif
                                    </td>
                                    @endif
                                    @if ($getContactDetails->type == 'supplier')
                                    <td><span class="display_currency total_purchase" data-orig-value="{{$row->total_purchase}}" data-currency_symbol=true>{{ $row->total_purchase}}</span></td>
                                    <td><span class="display_currency total_purchase_return" data-orig-value="{{$row->total_purchase_return}}" data-currency_symbol=true>{{$row->total_purchase_return}}</span></td>
                                    @endif
                                    @if($getContactDetails->type=='customer')
                                    <td><span class="display_currency total_invoice" data-orig-value="{{$row->total_invoice}}" data-currency_symbol=true>{{$row->total_invoice}}</span></td>
                                    <td><span class="display_currency total_sell_return" data-orig-value="{{$row->total_sell_return}}" data-currency_symbol=true>{{$row->total_sell_return}}</span></td>
                                    @endif
                                    <td><span class="display_currency total_opening_balance_due" data-currency_symbol=true data-orig-value="{{$row->opening_balance - $row->opening_balance_paid}}">{{$row->opening_balance - $row->opening_balance_paid}}</span></td>
                                    <td><span class="display_currency total_ageing_due" data-currency_symbol=true data-orig-value="{{$due}}">{{$due}}</span></td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center">
                                    <th></th>
                                    @if($getContactDetails->type=='customer')
                                    <th></th>
                                    @endif
                                    @if ($getContactDetails->type == 'supplier')
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    
                                    @endif
                                    <th></th>
                                    @if ($getContactDetails->type == 'supplier')
                                    <th><span class="display_currency" id="footer_total_purchase" data-currency_symbol="true"></span></th>
                                    <th><span class="display_currency" id="footer_total_purchase_return" data-currency_symbol="true"></span></th>
                                    @endif
                                    @if($getContactDetails->type=='customer')
                                    <th><span class="display_currency" id="footer_total_invoice" data-currency_symbol="true"></span></th>
                                    <th><span class="display_currency" id="footer_total_sell_return" data-currency_symbol="true"></span></th>
                                    @endif
                                    <th><span class="display_currency" id="footer_total_opening_balance_due" data-currency_symbol="true"></span></th>
                                    <th><span class="display_currency" id="footer_total_ageing_due" data-currency_symbol="true"></span></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->