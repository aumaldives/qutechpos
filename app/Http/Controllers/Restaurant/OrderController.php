<?php

namespace App\Http\Controllers\Restaurant;

use App\TransactionSellLine;
use App\User;
use App\Utils\RestaurantUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $commonUtil;

    protected $restUtil;

    /**
     * Constructor
     *
     * @param  Util  $commonUtil
     * @param  RestaurantUtil  $restUtil
     * @return void
     */
    public function __construct(Util $commonUtil, RestaurantUtil $restUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // if (!auth()->user()->can('sell.view')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        $is_service_staff = $this->restUtil->is_service_staff($user_id);
        
        // Show cooked orders (ready to be served) for both service staff and admins
        $orders = $this->restUtil->getAllOrders($business_id, ['line_order_status' => 'cooked']);
        
        // Group orders and get detailed item breakdown with quantities
        $grouped_orders = [];
        foreach ($orders as $order) {
            $order_items = [];
            $total_items = 0;
            
            foreach ($order->sell_lines as $line) {
                // Only show items that are cooked but not yet served
                if ($line->res_line_order_status !== 'cooked') {
                    continue;
                }
                
                $product_name = $line->product->name ?? 'N/A';
                if (!empty($line->variations) && is_object($line->variations) && method_exists($line->variations, 'count') && $line->variations->count() > 0) {
                    $variation_names = [];
                    foreach ($line->variations as $variation) {
                        if (is_object($variation) && isset($variation->name)) {
                            $variation_names[] = $variation->name;
                        }
                    }
                    if (!empty($variation_names)) {
                        $product_name .= ' (' . implode(', ', $variation_names) . ')';
                    }
                }
                
                $order_items[] = [
                    'name' => $product_name,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit->actual_name ?? '',
                    'notes' => $line->sell_line_note ?? ''
                ];
                $total_items += $line->quantity;
            }
            
            if (!empty($order_items)) {
                $grouped_orders[] = [
                    'id' => $order->id,
                    'invoice_no' => $order->invoice_no,
                    'created_at' => $order->created_at,
                    'customer_name' => $order->contact->name ?? 'Walk-in Customer',
                    'table_name' => $order->table->name ?? 'N/A',
                    'location_name' => $order->business_location ?? 'N/A',
                    'waiter_name' => $order->service_staff->user_full_name ?? 'Unassigned',
                    'items' => $order_items,
                    'total_items' => $total_items,
                    'order_status' => $this->getOrderStatus($order)
                ];
            }
        }
        
        // Service staff dropdown for filtering (optional for admins)
        $service_staff = [];
        if (! $is_service_staff) {
            $service_staff = $this->restUtil->service_staff_dropdown($business_id);
        }

        return view('restaurant.orders.index', compact('grouped_orders', 'is_service_staff', 'service_staff'));
    }

    /**
     * Get order status based on line items
     */
    private function getOrderStatus($order)
    {
        $count_sell_line = count($order->sell_lines);
        $count_cooked = count($order->sell_lines->where('res_line_order_status', 'cooked'));
        $count_served = count($order->sell_lines->where('res_line_order_status', 'served'));
        
        if ($count_served == $count_sell_line) {
            return 'served';
        } elseif ($count_cooked == $count_sell_line) {
            return 'cooked';
        } elseif ($count_served > 0 && $count_served < $count_sell_line) {
            return 'partial_served';
        } elseif ($count_cooked > 0 && $count_cooked < $count_sell_line) {
            return 'partial_cooked';
        }
        
        return 'received';
    }

    /**
     * Marks an order as served
     *
     * @return json $output
     */
    public function markAsServed($id)
    {
        // if (!auth()->user()->can('sell.update')) {
        //     abort(403, 'Unauthorized action.');
        // }
        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Allow all service staff and admins to mark any order as served
            $query = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
                        ->where('t.business_id', $business_id)
                        ->where('transaction_id', $id)
                        ->update(['res_line_order_status' => 'served']);

            $output = ['success' => 1,
                'msg' => trans('restaurant.order_successfully_marked_served'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Marks an line order as served
     *
     * @return json $output
     */
    public function markLineOrderAsServed($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            // Allow all service staff and admins to mark any line order as served
            $sell_line = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
                        ->where('t.business_id', $business_id)
                        ->where('transaction_sell_lines.id', $id)
                        ->first();

            if (! empty($sell_line)) {
                $sell_line->res_line_order_status = 'served';
                $sell_line->save();
                $output = ['success' => 1,
                    'msg' => trans('restaurant.order_successfully_marked_served'),
                ];
            } else {
                $output = ['success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function printLineOrder(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $waiter_id = request()->session()->get('user.id');
            $line_id = $request->input('line_id');
            if (! empty($request->input('service_staff_id'))) {
                $waiter_id = $request->input('service_staff_id');
            }

            $line_orders = $this->restUtil->getLineOrders($business_id, ['waiter_id' => $waiter_id, 'line_id' => $line_id]);
            $order = $line_orders[0];
            $html_content = view('restaurant.partials.print_line_order', compact('order'))->render();
            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.success'),
                'html_content' => $html_content,
            ];
        } catch (Exception $e) {
            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Refresh orders list for AJAX calls
     *
     * @return Html $output
     */
    public function refreshOrdersList(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');
            $orders_for = $request->orders_for;

            if ($orders_for == 'waiter') {
                // For orders module, return the modernized grouped orders format
                $is_service_staff = $this->restUtil->is_service_staff($user_id);
                
                // Show cooked orders (ready to be served) for both service staff and admins
                $orders = $this->restUtil->getAllOrders($business_id, ['line_order_status' => 'cooked']);
                
                // Group orders and get detailed item breakdown with quantities
                $grouped_orders = [];
                foreach ($orders as $order) {
                    $order_items = [];
                    $total_items = 0;
                    
                    foreach ($order->sell_lines as $line) {
                        // Only show items that are cooked but not yet served
                        if ($line->res_line_order_status !== 'cooked') {
                            continue;
                        }
                        
                        $product_name = $line->product->name ?? 'N/A';
                        if (!empty($line->variations) && is_object($line->variations) && method_exists($line->variations, 'count') && $line->variations->count() > 0) {
                            $variation_names = [];
                            foreach ($line->variations as $variation) {
                                if (is_object($variation) && isset($variation->name)) {
                                    $variation_names[] = $variation->name;
                                }
                            }
                            if (!empty($variation_names)) {
                                $product_name .= ' (' . implode(', ', $variation_names) . ')';
                            }
                        }
                        
                        $order_items[] = [
                            'name' => $product_name,
                            'quantity' => $line->quantity,
                            'unit' => $line->unit->actual_name ?? '',
                            'notes' => $line->sell_line_note ?? ''
                        ];
                        $total_items += $line->quantity;
                    }
                    
                    if (!empty($order_items)) {
                        $grouped_orders[] = [
                            'id' => $order->id,
                            'invoice_no' => $order->invoice_no,
                            'created_at' => $order->created_at,
                            'customer_name' => $order->contact->name ?? 'Walk-in Customer',
                            'table_name' => $order->table->name ?? 'N/A',
                            'location_name' => $order->business_location ?? 'N/A',
                            'waiter_name' => $order->service_staff->user_full_name ?? 'Unassigned',
                            'items' => $order_items,
                            'total_items' => $total_items,
                            'order_status' => $this->getOrderStatus($order)
                        ];
                    }
                }

                return view('restaurant.partials.orders_list', compact('grouped_orders'));
            } else {
                // For other cases, use default behavior
                return view('restaurant.partials.show_orders', compact('orders', 'orders_for'));
            }
        } catch (\Exception $e) {
            return '<div class="col-md-12"><h4 class="text-center">Error loading orders</h4></div>';
        }
    }
}
