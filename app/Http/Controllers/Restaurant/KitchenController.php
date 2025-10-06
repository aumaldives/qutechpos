<?php

namespace App\Http\Controllers\Restaurant;

use App\TransactionSellLine;
use App\Utils\RestaurantUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class KitchenController extends Controller
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
        $orders = $this->restUtil->getAllOrders($business_id, ['line_order_status' => 'received']);
        
        // Group orders and get detailed item breakdown with quantities
        $grouped_orders = [];
        foreach ($orders as $order) {
            $order_items = [];
            $total_items = 0;
            
            foreach ($order->sell_lines as $line) {
                // Skip items that are already cooked
                if ($line->res_line_order_status === 'cooked' || $line->res_line_order_status === 'served') {
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

        return view('restaurant.kitchen.index', compact('grouped_orders'));
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
     * Marks an order as cooked
     *
     * @return json $output
     */
    public function markAsCooked($id)
    {
        // if (!auth()->user()->can('sell.update')) {
        //     abort(403, 'Unauthorized action.');
        // }
        try {
            $business_id = request()->session()->get('user.business_id');
            $sl = TransactionSellLine::leftJoin('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
                        ->where('t.business_id', $business_id)
                        ->where('transaction_id', $id)
                        ->where(function ($q) {
                            $q->whereNull('res_line_order_status')
                                ->orWhere('res_line_order_status', 'received');
                        })
                        ->update(['res_line_order_status' => 'cooked']);

            $output = ['success' => 1,
                'msg' => trans('restaurant.order_successfully_marked_cooked'),
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
     * Retrives fresh orders
     *
     * @return Json $output
     */
    public function refreshOrdersList(Request $request)
    {

        // if (!auth()->user()->can('sell.view')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        $orders_for = $request->orders_for;

        if ($orders_for == 'kitchen') {
            // For kitchen, return the modernized grouped orders format
            $orders = $this->restUtil->getAllOrders($business_id, ['line_order_status' => 'received']);
            
            // Group orders and get detailed item breakdown with quantities (same as index method)
            $grouped_orders = [];
            foreach ($orders as $order) {
                $order_items = [];
                $total_items = 0;
                
                foreach ($order->sell_lines as $line) {
                    // Skip items that are already cooked
                    if ($line->res_line_order_status === 'cooked' || $line->res_line_order_status === 'served') {
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

            return view('restaurant.partials.kitchen_orders', compact('grouped_orders'));
        } else {
            // For other cases, keep the old behavior
            $filter = [];
            $service_staff_id = request()->session()->get('user.id');

            if (! $this->restUtil->is_service_staff($service_staff_id) && ! empty($request->input('service_staff_id'))) {
                $service_staff_id = $request->input('service_staff_id');
            }

            if ($orders_for == 'waiter') {
                $filter['waiter_id'] = $service_staff_id;
            }

            $orders = $this->restUtil->getAllOrders($business_id, $filter);
            return view('restaurant.partials.show_orders', compact('orders', 'orders_for'));
        }
    }

    /**
     * Retrives fresh orders
     *
     * @return Json $output
     */
    public function refreshLineOrdersList(Request $request)
    {

        // if (!auth()->user()->can('sell.view')) {
        //     abort(403, 'Unauthorized action.');
        // }
        $business_id = request()->session()->get('user.business_id');
        $orders_for = $request->orders_for;
        $filter = [];
        $service_staff_id = request()->session()->get('user.id');

        if (! $this->restUtil->is_service_staff($service_staff_id) && ! empty($request->input('service_staff_id'))) {
            $service_staff_id = $request->input('service_staff_id');
        }

        if ($orders_for == 'kitchen') {
            $filter['order_status'] = 'received';
        } elseif ($orders_for == 'waiter') {
            $filter['waiter_id'] = $service_staff_id;
        }

        $line_orders = $this->restUtil->getLineOrders($business_id, $filter);

        return view('restaurant.partials.line_orders', compact('line_orders', 'orders_for'));
    }

    /**
     * Print KOT for an order
     *
     * @param  Request  $request
     * @return json $output
     */
    public function printKot(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $order_id = $request->input('order_id');

            // Get the order with full details
            $orders = $this->restUtil->getAllOrders($business_id, ['line_order_status' => 'received']);
            $order = $orders->where('id', $order_id)->first();
            
            if (!$order) {
                return ['success' => 0, 'msg' => 'Order not found'];
            }

            // Prepare order data similar to how it's done in index method
            $order_items = [];
            $total_items = 0;
            
            foreach ($order->sell_lines as $line) {
                // Skip items that are already cooked or served
                if ($line->res_line_order_status === 'cooked' || $line->res_line_order_status === 'served') {
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
            
            $order_data = [
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

            $html_content = view('restaurant.partials.print_kot', compact('order_data'))->render();
            
            $output = [
                'success' => 1,
                'msg' => trans('lang_v1.success'),
                'html_content' => $html_content,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            
            $output = [
                'success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
