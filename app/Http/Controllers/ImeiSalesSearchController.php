<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ImeiSalesSearchController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param BusinessUtil $businessUtil
     * @param TransactionUtil $transactionUtil  
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display IMEI sales search interface
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        
        // Add variables that might be needed by the layout
        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');
        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
        
        // Ensure enabled_modules is available (fallback if session issue)
        $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];
        if (is_string($enabled_modules)) {
            $enabled_modules = json_decode($enabled_modules, true) ?? [];
        }
        if (!is_array($enabled_modules)) {
            $enabled_modules = [];
        }

        return view('imei_sales_search.index', compact('business_locations', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'enabled_modules'));
    }

    /**
     * Search sales by IMEI number
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByImei(Request $request)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized.');
        }

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        $query = Transaction::join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('product_imeis', 'transaction_sell_lines.id', '=', 'product_imeis.sell_line_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->leftJoin('product_variations', 'transaction_sell_lines.variation_id', '=', 'product_variations.id')
            ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select([
                'transactions.id',
                'transactions.invoice_no',
                'transactions.transaction_date',
                'transactions.final_total',
                'transactions.total_before_tax',
                'contacts.name as customer_name',
                'contacts.mobile as customer_mobile',
                'contacts.email as customer_email',
                'business_locations.name as location_name',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'product_imeis.imei',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price_before_discount',
                'transaction_sell_lines.id as sell_line_id'
            ]);

        // Apply IMEI search filter
        if (!empty($request->imei_search)) {
            $query->where('product_imeis.imei', 'like', '%' . $request->imei_search . '%');
        }

        // Apply location filter
        if (!empty($request->location_id)) {
            $query->where('transactions.location_id', $request->location_id);
        }

        // Apply date range filters
        if (!empty($request->start_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $this->businessUtil->uf_date($request->start_date, true));
        }
        if (!empty($request->end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $this->businessUtil->uf_date($request->end_date, true));
        }

        // Apply permission-based filtering
        if (auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', $user_id);
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-info btn-xs btn-modal" 
                    data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]) . '">
                    <i class="fa fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '
                </button>';
                
                if (auth()->user()->can('sell.update')) {
                    $html .= '<button type="button" class="btn btn-primary btn-xs" 
                        onclick="window.open(\'' . action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]) . '\', \'_blank\')">
                        <i class="fa fa-edit" aria-hidden="true"></i> ' . __('messages.edit') . '
                    </button>';
                }
                
                $html .= '<button type="button" class="btn btn-success btn-xs" 
                    onclick="window.open(\'' . action([\App\Http\Controllers\SellPosController::class, 'printInvoice'], [$row->id]) . '\', \'_blank\')">
                    <i class="fa fa-print" aria-hidden="true"></i> ' . __('messages.print') . '
                </button>';
                $html .= '</div>';
                return $html;
            })
            ->addColumn('customer_info', function ($row) {
                $customer_info = $row->customer_name;
                if (!empty($row->customer_mobile)) {
                    $customer_info .= '<br><small><i class="fa fa-phone"></i> ' . $row->customer_mobile . '</small>';
                }
                if (!empty($row->customer_email)) {
                    $customer_info .= '<br><small><i class="fa fa-envelope"></i> ' . $row->customer_email . '</small>';
                }
                return $customer_info;
            })
            ->addColumn('product_info', function ($row) {
                $product_name = $row->product_name;
                if (!empty($row->variation_name) && $row->variation_name != 'DUMMY') {
                    $product_name .= ' (' . $row->variation_name . ')';
                }
                $product_name .= '<br><small>Qty: ' . $row->quantity . ' @ ' . $this->businessUtil->num_f($row->unit_price_before_discount) . '</small>';
                return $product_name;
            })
            ->addColumn('imei_info', function ($row) {
                return '<span class="label label-primary">' . $row->imei . '</span>';
            })
            ->addColumn('invoice_info', function ($row) {
                return '<strong>' . $row->invoice_no . '</strong><br>' .
                       '<small>' . $this->businessUtil->format_date($row->transaction_date, true) . '</small>';
            })
            ->addColumn('total_amount', function ($row) {
                return '<span class="display_currency" data-currency_symbol="1">' . 
                       $row->final_total . '</span>';
            })
            ->filterColumn('customer_info', function($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('contacts.name', 'like', "%{$keyword}%")
                      ->orWhere('contacts.mobile', 'like', "%{$keyword}%")
                      ->orWhere('contacts.email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('product_info', function($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('products.name', 'like', "%{$keyword}%")
                      ->orWhere('product_variations.name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('imei_info', function($query, $keyword) {
                $query->where('product_imeis.imei', 'like', "%{$keyword}%");
            })
            ->filterColumn('invoice_info', function($query, $keyword) {
                $query->where('transactions.invoice_no', 'like', "%{$keyword}%");
            })
            ->rawColumns(['action', 'customer_info', 'product_info', 'imei_info', 'invoice_info', 'total_amount'])
            ->make(true);
    }

    /**
     * Quick IMEI search API endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickSearch(Request $request)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('view_own_sell_only')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $imei = $request->get('imei');
        if (empty($imei)) {
            return response()->json(['error' => 'IMEI is required'], 400);
        }

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        $query = Transaction::join('transaction_sell_lines', 'transactions.id', '=', 'transaction_sell_lines.transaction_id')
            ->join('product_imeis', 'transaction_sell_lines.id', '=', 'product_imeis.sell_line_id')
            ->join('products', 'transaction_sell_lines.product_id', '=', 'products.id')
            ->leftJoin('product_variations', 'transaction_sell_lines.variation_id', '=', 'product_variations.id')
            ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->where('product_imeis.imei', $imei);

        // Apply permission-based filtering
        if (auth()->user()->can('view_own_sell_only')) {
            $query->where('transactions.created_by', $user_id);
        }

        $result = $query->select([
            'transactions.id',
            'transactions.invoice_no',
            'transactions.transaction_date',
            'transactions.final_total',
            'contacts.name as customer_name',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'product_imeis.imei'
        ])->first();

        if (!$result) {
            return response()->json(['found' => false, 'message' => __('lang_v1.imei_not_found')]);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'transaction_id' => $result->id,
                'invoice_no' => $result->invoice_no,
                'transaction_date' => $this->businessUtil->format_date($result->transaction_date, true),
                'customer_name' => $result->customer_name,
                'product_name' => $result->product_name . (!empty($result->variation_name) && $result->variation_name != 'DUMMY' ? ' (' . $result->variation_name . ')' : ''),
                'total_amount' => $this->businessUtil->num_f($result->final_total),
                'imei' => $result->imei,
                'view_url' => action([\App\Http\Controllers\SellController::class, 'show'], [$result->id])
            ]
        ]);
    }
}