@forelse($grouped_orders as $order)
    <div class="col-lg-3 col-md-6 col-sm-12 order_div" style="margin-bottom: 10px;">
        <div class="box box-widget" style="margin-bottom: 0; padding: 5px;">
            <div class="box-header with-border" style="padding: 8px 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 30px; height: 30px; background-color: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                            <i class="fas fa-receipt" style="color: white; font-size: 14px;"></i>
                        </div>
                        <div>
                            <div style="margin-bottom: 2px;">
                                <strong style="color: #333; font-size: 13px;">Order #{{ $order['invoice_no'] }}</strong>
                                <span class="label" style="margin-left: 8px; 
                                    @if($order['order_status'] == 'cooked') background-color: #dc3545; 
                                    @elseif($order['order_status'] == 'served') background-color: #28a745; 
                                    @elseif($order['order_status'] == 'partial_cooked') background-color: #fd7e14; 
                                    @else background-color: #17a2b8; @endif
                                    color: white; font-size: 10px; padding: 2px 4px;">
                                    @lang('restaurant.order_statuses.' . $order['order_status'])
                                </span>
                            </div>
                            <div style="color: #666; font-size: 11px;">
                                <i class="fas fa-clock" style="margin-right: 4px;"></i>{{ @format_date($order['created_at']) }} {{ @format_time($order['created_at']) }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="label" style="background-color: #6c757d; color: white; font-size: 10px; padding: 3px 6px;">{{ $order['total_items'] }} items</span>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="box-body" style="padding: 8px 12px;">
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-6">
                        <small style="color: #666; font-weight: 600; font-size: 10px;"><i class="fas fa-user" style="color: #666;"></i> Customer:</small><br>
                        <strong style="color: #333; font-size: 12px;">{{ $order['customer_name'] }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small style="color: #666; font-weight: 600; font-size: 10px;"><i class="fas fa-map-marker-alt" style="color: #666;"></i> Table:</small><br>
                        <strong style="color: #333; font-size: 12px;">{{ $order['table_name'] }}</strong>
                    </div>
                </div>
                
                @if($order['waiter_name'] != 'Unassigned')
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-12">
                        <small style="color: #666; font-weight: 600; font-size: 10px;"><i class="fas fa-user-tie" style="color: #666;"></i> Service Staff:</small><br>
                        <strong style="color: #333; font-size: 12px;">{{ $order['waiter_name'] }}</strong>
                    </div>
                </div>
                @endif

                <!-- Items List -->
                <div class="table-responsive">
                    <table class="table table-condensed table-bordered">
                        <thead>
                            <tr style="background-color: #f4f4f4; color: #333;">
                                <th width="60%" style="font-weight: bold; color: #333; font-size: 11px;"><i class="fas fa-utensils" style="color: #333;"></i> Item</th>
                                <th width="20%" class="text-center" style="font-weight: bold; color: #333; font-size: 11px;"><i class="fas fa-sort-numeric-up" style="color: #333;"></i> Qty</th>
                                <th width="20%" class="text-center" style="font-weight: bold; color: #333; font-size: 11px;"><i class="fas fa-info-circle" style="color: #333;"></i> Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order['items'] as $item)
                            <tr style="background-color: #f8f9fa;">
                                <td style="padding: 6px 4px; font-size: 12px;">
                                    <strong style="color: #333; font-size: 12px;">{{ $item['name'] }}</strong>
                                    @if(!empty($item['unit']))
                                        <br><small style="color: #666; font-size: 10px;">({{ $item['unit'] }})</small>
                                    @endif
                                </td>
                                <td class="text-center" style="padding: 6px 4px;">
                                    <span class="badge" style="background-color: #007bff; color: white; font-size: 11px; padding: 4px 6px;">{{ number_format($item['quantity'], 2) }}</span>
                                </td>
                                <td class="text-center" style="padding: 6px 4px;">
                                    @if(!empty($item['notes']))
                                        <i class="fas fa-sticky-note" 
                                           style="color: #ff8c00; font-size: 14px; cursor: pointer;"
                                           data-toggle="tooltip" 
                                           title="{{ $item['notes'] }}"></i>
                                    @else
                                        <span style="color: #999;">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="box-footer">
                <div class="row">
                    <div class="col-md-4">
                        <a href="#" class="btn btn-success btn-flat btn-block mark_as_cooked_btn" 
                           data-href="{{ action([\App\Http\Controllers\Restaurant\KitchenController::class, 'markAsCooked'], [$order['id']]) }}">
                            <i class="fas fa-check"></i> @lang('restaurant.mark_as_cooked')
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="btn btn-warning btn-flat btn-block print_kot_btn" 
                           data-order-id="{{ $order['id'] }}">
                            <i class="fas fa-print"></i> Print KOT
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="#" class="btn btn-info btn-flat btn-block btn-modal" 
                           data-href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$order['id']]) }}" 
                           data-container=".view_modal">
                            <i class="fas fa-eye"></i> @lang('restaurant.order_details')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @if($loop->iteration % 4 == 0)
        <div class="clearfix visible-lg"></div>
    @endif
    @if($loop->iteration % 2 == 0)
        <div class="clearfix visible-md"></div>
    @endif
@empty
    <div class="col-md-12">
        <div class="box">
            <div class="box-body text-center" style="padding: 50px;">
                <i class="fas fa-check-circle text-green" style="font-size: 48px; margin-bottom: 20px;"></i>
                <h4 class="text-muted">@lang('restaurant.no_orders_found')</h4>
                <p class="text-muted">All orders have been completed!</p>
            </div>
        </div>
    </div>
@endforelse