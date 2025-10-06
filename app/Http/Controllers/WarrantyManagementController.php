<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\Warranty;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WarrantyManagementController extends Controller
{
    /**
     * All Utils instance.
     *
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

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
        'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Display a listing of warranty items
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

        return view('warranty_management.index', compact('business_locations', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'enabled_modules'));
    }

    /**
     * Get warranty data for DataTables
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWarrantyData(Request $request)
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('view_own_sell_only')) {
            abort(403, 'Unauthorized.');
        }

        $business_id = request()->session()->get('user.business_id');
        $user_id = request()->session()->get('user.id');

        // Get warranty data - simplified approach
        $query = \App\TransactionSellLine::from('transaction_sell_lines as tsl')
            ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin('product_variations as pv', 'tsl.variation_id', '=', 'pv.id')
            ->join('contacts as c', 't.contact_id', '=', 'c.id')
            ->join('warranties as w', 'p.warranty_id', '=', 'w.id')
            ->leftJoin('product_imeis as pi', 'tsl.id', '=', 'pi.sell_line_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->select([
                't.id as transaction_id',
                't.transaction_date',
                't.invoice_no',
                'p.name as product_name',
                'pv.name as variation_name',
                'c.name as customer_name',
                'c.mobile as customer_mobile',
                'c.email as customer_email',
                'w.name as warranty_name',
                'w.duration',
                'w.duration_type',
                'pi.imei as imei_number',
                'tsl.quantity',
                'tsl.id as sell_line_id'
            ]);

        // Apply location filter
        if (!empty($request->location_id)) {
            $query->where('t.location_id', $request->location_id);
        }

        // Apply date range filters
        if (!empty($request->sold_date_start)) {
            $query->whereDate('t.transaction_date', '>=', $this->businessUtil->uf_date($request->sold_date_start, true));
        }
        if (!empty($request->sold_date_end)) {
            $query->whereDate('t.transaction_date', '<=', $this->businessUtil->uf_date($request->sold_date_end, true));
        }

        // Apply permission-based filtering
        if (auth()->user()->can('view_own_sell_only')) {
            $query->where('t.created_by', $user_id);
        }

        $dataTable = DataTables::of($query)
            ->addColumn('warranty_expiry', function ($row) {
                if ($row->warranty_name && $row->duration) {
                    $sale_date = Carbon::parse($row->transaction_date);
                    $expiry_date = $this->calculateWarrantyExpiry($sale_date, $row->duration, $row->duration_type);
                    
                    $days_remaining = $expiry_date->diffInDays(Carbon::now(), false);
                    $status_class = '';
                    
                    if ($days_remaining > 0) {
                        $status_class = 'label-danger'; // Expired
                        $status = __('lang_v1.expired');
                    } elseif (abs($days_remaining) <= 30) {
                        $status_class = 'label-warning'; // Expiring soon
                        $status = __('lang_v1.expires_soon');
                    } else {
                        $status_class = 'label-success'; // Active
                        $status = __('lang_v1.active');
                    }
                    
                    return '<span class="label ' . $status_class . '">' . $status . '</span><br>' . 
                           '<small>' . $this->businessUtil->format_date($expiry_date->format('Y-m-d'), true) . '</small>';
                }
                return '-';
            })
            ->addColumn('product_info', function ($row) {
                $product_name = $row->product_name;
                if (!empty($row->variation_name) && $row->variation_name != 'DUMMY') {
                    $product_name .= ' (' . $row->variation_name . ')';
                }
                return $product_name;
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
            ->addColumn('warranty_contact', function ($row) {
                // For walk-in customers or customers without proper contact info, show "Walk-in Customer"
                // Otherwise show the customer contact information
                if (empty($row->customer_name) || $row->customer_name === 'Walk-In Customer' || 
                    (empty($row->customer_mobile) && empty($row->customer_email))) {
                    return '<span class="label label-warning">Walk-in Customer</span><br>' .
                           '<small>Contact via invoice details</small>';
                }
                
                $warranty_contact = $row->customer_name;
                if (!empty($row->customer_mobile)) {
                    $warranty_contact .= '<br><small><i class="fa fa-phone"></i> ' . $row->customer_mobile . '</small>';
                }
                if (!empty($row->customer_email)) {
                    $warranty_contact .= '<br><small><i class="fa fa-envelope"></i> ' . $row->customer_email . '</small>';
                }
                return $warranty_contact;
            })
            ->addColumn('imei_info', function ($row) {
                if (!empty($row->imei_number)) {
                    return '<span class="label label-info">' . $row->imei_number . '</span>';
                }
                return '-';
            })
            ->addColumn('sold_date', function ($row) {
                return $this->businessUtil->format_date($row->transaction_date, true);
            })
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-info btn-xs btn-modal" 
                    data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->transaction_id]) . '">
                    <i class="fa fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '
                </button>';
                $html .= '</div>';
                return $html;
            })
            ->filterColumn('customer_info', function($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('c.name', 'like', "%{$keyword}%")
                      ->orWhere('c.mobile', 'like', "%{$keyword}%")
                      ->orWhere('c.email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('product_info', function($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('p.name', 'like', "%{$keyword}%")
                      ->orWhere('pv.name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('imei_info', function($query, $keyword) {
                $query->where('pi.imei', 'like', "%{$keyword}%");
            })
            ->rawColumns(['warranty_expiry', 'customer_info', 'warranty_contact', 'imei_info', 'action']);

        // Apply warranty status filtering using table aliases
        if (!empty($request->warranty_status)) {
            $status = $request->warranty_status;
            $dataTable->filter(function ($query) use ($status) {
                if ($status == 'expired') {
                    $query->havingRaw('DATEDIFF(
                        CASE 
                            WHEN w.duration_type = "days" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration DAY)
                            WHEN w.duration_type = "months" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                            WHEN w.duration_type = "years" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration YEAR)
                            ELSE DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                        END, 
                        CURDATE()
                    ) < 0');
                } elseif ($status == 'expires_soon') {
                    $query->havingRaw('DATEDIFF(
                        CASE 
                            WHEN w.duration_type = "days" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration DAY)
                            WHEN w.duration_type = "months" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                            WHEN w.duration_type = "years" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration YEAR)
                            ELSE DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                        END, 
                        CURDATE()
                    ) BETWEEN 0 AND 30');
                } elseif ($status == 'active') {
                    $query->havingRaw('DATEDIFF(
                        CASE 
                            WHEN w.duration_type = "days" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration DAY)
                            WHEN w.duration_type = "months" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                            WHEN w.duration_type = "years" 
                            THEN DATE_ADD(t.transaction_date, INTERVAL w.duration YEAR)
                            ELSE DATE_ADD(t.transaction_date, INTERVAL w.duration MONTH)
                        END, 
                        CURDATE()
                    ) > 30');
                }
            });
        }

        return $dataTable->make(true);
    }

    /**
     * Calculate warranty expiry date
     *
     * @param Carbon $sale_date
     * @param int $duration
     * @param string $duration_type
     * @return Carbon
     */
    private function calculateWarrantyExpiry($sale_date, $duration, $duration_type)
    {
        switch ($duration_type) {
            case 'days':
                return $sale_date->copy()->addDays($duration);
            case 'months':
                return $sale_date->copy()->addMonths($duration);
            case 'years':
                return $sale_date->copy()->addYears($duration);
            default:
                return $sale_date->copy()->addMonths($duration); // Default to months
        }
    }
}