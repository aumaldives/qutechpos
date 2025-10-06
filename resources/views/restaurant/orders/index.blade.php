@extends('layouts.restaurant')
@section('title', __( 'restaurant.orders' ))

@section('content')

<!-- Main content -->
<section class="content min-height-90hv no-print">
    
    <div class="row">
        <div class="col-md-12 text-center">
            <h3>
                <i class="fas fa-concierge-bell text-green"></i>
                @lang( 'restaurant.all_orders' ) - Ready to Serve
                @show_tooltip(__('lang_v1.tooltip_serviceorder'))
            </h3>
            <p class="text-muted">Orders that have been cooked and are ready for serving</p>
        </div>
    </div>

    <div class="row">
        @if(!$is_service_staff)
            <div class="col-md-12">
                <div class="box box-info collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fas fa-filter"></i> Filters</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        {!! Form::open(['url' => action([\App\Http\Controllers\Restaurant\OrderController::class, 'index']), 'method' => 'get', 'id' => 'select_service_staff_form' ]) !!}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label><i class="fa fa-user-secret"></i> Filter by Service Staff:</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user-tie"></i>
                                        </span>
                                        {!! Form::select('service_staff', $service_staff, request()->service_staff, ['class' => 'form-control select2', 'placeholder' => __('restaurant.select_service_staff'), 'id' => 'service_staff_id']); !!}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fas fa-utensils text-green"></i>
                        Ready to Serve
                        <span class="badge bg-green">{{ count($grouped_orders) }}</span>
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-success btn-sm" id="refresh_orders">
                            <i class="fas fa-sync"></i> @lang( 'restaurant.refresh' )
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <input type="hidden" id="orders_for" value="waiter">
                    <div class="row" id="orders_div">
                        @forelse($grouped_orders as $order)
                            <div class="col-lg-3 col-md-6 col-sm-12 order_div" style="margin-bottom: 10px;">
                                <div class="box box-widget" style="padding: 5px;">
                                    <div class="box-header with-border" style="padding: 8px 12px;">
                                        <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center;">
                                            <div style="width: 30px; height: 30px; background-color: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                                                <i class="fas fa-clipboard-check" style="color: white; font-size: 14px;"></i>
                                            </div>
                                            <div>
                                                <div style="margin-bottom: 2px;">
                                                    <strong style="color: #333; font-size: 13px;">Order #{{ $order['invoice_no'] }}</strong>
                                                    <span class="label" style="background-color: #28a745; color: white; font-size: 10px; padding: 2px 4px; margin-left: 8px;">Ready to Serve</span>
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
                                                <strong style="color: #007bff; font-size: 12px;">{{ $order['waiter_name'] }}</strong>
                                            </div>
                                        </div>
                                        @else
                                        <div class="row" style="margin-bottom: 10px;">
                                            <div class="col-md-12">
                                                <small style="color: #666; font-weight: 600; font-size: 10px;"><i class="fas fa-user-tie" style="color: #666;"></i> Service Staff:</small><br>
                                                <span class="label" style="background-color: #ff8c00; color: white; padding: 4px 8px; border-radius: 3px;">Unassigned - Any staff can serve</span>
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
                                                            <span class="badge" style="background-color: #28a745; color: white; font-size: 11px; padding: 4px 6px;">{{ number_format($item['quantity'], 2) }}</span>
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
                                            <div class="col-md-6">
                                                <a href="#" class="btn btn-success btn-flat btn-block mark_as_served_btn" 
                                                   data-href="{{ action([\App\Http\Controllers\Restaurant\OrderController::class, 'markAsServed'], [$order['id']]) }}">
                                                    <i class="fas fa-check-double"></i> @lang('restaurant.mark_as_served')
                                                </a>
                                            </div>
                                            <div class="col-md-6">
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
                                        <i class="fas fa-clipboard-check text-green" style="font-size: 48px; margin-bottom: 20px;"></i>
                                        <h4 class="text-muted">@lang('restaurant.no_orders_found')</h4>
                                        <p class="text-muted">No orders are ready to be served at the moment.</p>
                                        <small class="text-muted">Check back when the kitchen has prepared some orders!</small>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
                
                <div class="overlay hide">
                    <i class="fas fa-sync fa-spin"></i>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Mark as served functionality
        $(document).on('click', 'a.mark_as_served_btn', function(e){
            e.preventDefault();
            swal({
              title: "Mark Order as Served?",
              text: "This will mark all items in this order as served to the customer.",
              icon: "success",
              buttons: {
                  cancel: {
                      text: "Cancel",
                      visible: true,
                      className: "btn-secondary"
                  },
                  confirm: {
                      text: "Mark as Served",
                      className: "btn-success"
                  }
              }
            }).then((willMark) => {
                if (willMark) {
                    var _this = $(this);
                    var href = _this.data('href');
                    
                    // Add loading state
                    _this.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                    
                    $.ajax({
                        method: "GET",
                        url: href,
                        dataType: "json",
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                // Add fade out animation
                                _this.closest('.order_div').fadeOut(500, function() {
                                    $(this).remove();
                                    // Update order count
                                    var currentCount = parseInt($('.badge.bg-green').text()) - 1;
                                    $('.badge.bg-green').text(currentCount);
                                    
                                    // Show no orders message if empty
                                    if(currentCount === 0) {
                                        $('#orders_div').html('<div class="col-md-12"><div class="box"><div class="box-body text-center" style="padding: 50px;"><i class="fas fa-clipboard-check text-green" style="font-size: 48px; margin-bottom: 20px;"></i><h4 class="text-muted">All orders served!</h4><p class="text-muted">Excellent work! All orders have been served to customers.</p></div></div></div>');
                                    }
                                });
                            } else {
                                toastr.error(result.msg);
                                _this.prop('disabled', false).html('<i class="fas fa-check-double"></i> @lang("restaurant.mark_as_served")');
                            }
                        },
                        error: function() {
                            toastr.error('An error occurred. Please try again.');
                            _this.prop('disabled', false).html('<i class="fas fa-check-double"></i> @lang("restaurant.mark_as_served")');
                        }
                    });
                }
            });
        });
        
        // Auto refresh every 30 seconds
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                refresh_orders();
            }
        }, 30000);
        
        // Refresh orders functionality - use AJAX instead of page reload  
        $('#refresh_orders').on('click', function() {
            refresh_orders();
        });
        
        // Service staff filter change
        $('#service_staff_id').on('change', function() {
            if($(this).val() !== '') {
                $('#select_service_staff_form').submit();
            }
        });
    });
</script>

<style>
.order_div .box {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 6px;
    transition: transform 0.2s ease;
}

.order_div .box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.table-condensed th {
    padding: 6px;
    font-size: 12px;
    font-weight: 600;
}

.table-condensed td {
    padding: 8px 6px;
    font-size: 13px;
}

.user-block > img {
    width: 35px;
    height: 35px;
}

.box-footer .btn {
    margin-bottom: 5px;
}

.collapsed-box .box-body {
    display: none;
}

@media (max-width: 768px) {
    .order_div {
        margin-bottom: 15px;
    }
    
    .box-footer .btn {
        margin-bottom: 10px;
    }
}
</style>
@endsection