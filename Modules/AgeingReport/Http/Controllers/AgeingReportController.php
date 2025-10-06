<?php

namespace Modules\AgeingReport\Http\Controllers;

use App\Brands;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Charts\CommonChart;
use App\Contact;
use App\CustomerGroup;
use App\ExpenseCategory;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\SellingPriceGroup;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use Datatables;
use DB;
use Illuminate\Http\Request;
class AgeingReportController
{
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;

/**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
     /**
     * Shows report for Supplier
     *
     * @return \Illuminate\Http\Response
     */
 //   public function getCustomerSuppliers(Request $request)
 public function index(Request $request)
    {
        if (!auth()->user()->can('contacts_report.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.type as contact_type'
                );
            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }

            if (!empty($request->input('customer_group_id'))) {
                $contacts->where('contacts.customer_group_id', $request->input('customer_group_id'));
            }

            if (!empty($request->input('contact_type'))) {
                $contacts->whereIn('contacts.type', [$request->input('contact_type'), 'both']);
            }

            return Datatables::of($contacts)
                ->editColumn('name', function ($row) {
                    $name = $row->name;
                    if (!empty($row->supplier_business_name)) {
                        $name .= ', ' . $row->supplier_business_name;
                    }
                    return '<a href="' . action('ContactController@show', [$row->id]) . '" target="_blank" class="no-print">' .
                        $name .
                        '</a><span class="print_section">' . $name . '</span>';
                })
                ->editColumn('total_purchase', function ($row) {
                    return '<span class="display_currency total_purchase" data-orig-value="' . $row->total_purchase . '" data-currency_symbol = true>' . $row->total_purchase . '</span>';
                })
                ->editColumn('total_purchase_return', function ($row) {
                    return '<span class="display_currency total_purchase_return" data-orig-value="' . $row->total_purchase_return . '" data-currency_symbol = true>' . $row->total_purchase_return . '</span>';
                })
                ->editColumn('total_sell_return', function ($row) {
                    return '<span class="display_currency total_sell_return" data-orig-value="' . $row->total_sell_return . '" data-currency_symbol = true>' . $row->total_sell_return . '</span>';
                })
                ->editColumn('total_invoice', function ($row) {
                    return '<span class="display_currency total_invoice" data-orig-value="' . $row->total_invoice . '" data-currency_symbol = true>' . $row->total_invoice . '</span>';
                })
                ->addColumn('due', function ($row) {
                    $due = ($row->total_invoice - $row->invoice_received - $row->total_sell_return + $row->sell_return_paid) - ($row->total_purchase - $row->total_purchase_return + $row->purchase_return_received - $row->purchase_paid);

                    if ($row->contact_type == 'supplier') {
                        $due -= $row->opening_balance - $row->opening_balance_paid;
                    } else {
                        $due += $row->opening_balance - $row->opening_balance_paid;
                    }

                    return '<span class="display_currency total_due" data-orig-value="' . $due . '" data-currency_symbol=true data-highlight=true>' . $due . '</span>';
                })
                ->addColumn(
                    'opening_balance_due',
                    '<span class="display_currency opening_balance_due" data-currency_symbol=true data-orig-value="{{$opening_balance - $opening_balance_paid}}">{{$opening_balance - $opening_balance_paid}}</span>'
                )
                ->removeColumn('supplier_business_name')
                ->removeColumn('invoice_received')
                ->removeColumn('purchase_paid')
                ->removeColumn('id')
                ->rawColumns(['total_purchase', 'total_invoice', 'due', 'name', 'total_purchase_return', 'total_sell_return', 'opening_balance_due'])
                ->make(true);
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('ageingreport::customer'),
            'supplier' => __('ageingreport::supplier')
        ];
        $labels = [];
        $values = [];
        $chart = new CommonChart;
        $chart->labels($labels)
            ->dataset(__('report.total_unit_sold'), 'column', $values);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $suppliers = Contact::suppliersDropdown($business_id);
        return view('ageingreport::index')
            ->with(compact('chart', 'customer_group', 'types', 'business_locations', 'customers', 'suppliers'));
    }
    public function getSuppliersAgeing(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        //Return the details in ajax call
        if ($request->ajax()) {
            $contacts = Contact::where('contacts.business_id', $business_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
                ->active()
                ->groupBy('contacts.id')
                ->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_paid"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as purchase_return_received"),
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                    DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                    'contacts.supplier_business_name',
                    'contacts.name',
                    'contacts.id',
                    'contacts.type as contact_type',
                    't.id as transaction_id'
                );
            $date = request()->get('date', null);
            if (!empty($date)) {
                $contacts->whereDate('t.transaction_date', $date);
            }

            $location_id = request()->get('location_id', null);
            if (!empty($location_id)) {
                $contacts->where('t.location_id', $location_id);
            }
            $customer_id = request()->get('customer_id', null);
            if (!empty($customer_id)) {
                $contacts->where('t.contact_id', $customer_id);
            }
            $supplier_id = request()->get('supplier_id', null);
            if (!empty($supplier_id)) {
                $contacts->where('t.contact_id', $supplier_id);
            }
            $contacts->whereIn('contacts.type', [request()->get('contact_type')]);
            $permitted_locations = auth()->user()->permitted_locations();

            if ($permitted_locations != 'all') {
                $contacts->whereIn('t.location_id', $permitted_locations);
            }
            $contactsPeriod = $contacts;
            $contacts = $contacts->get();

            $contactsArray = $contacts->pluck('id');
            $transactionsIds = $contacts->pluck('transaction_id');

            $due1to30Array = [];
            $due31to60Array = [];
            $due61to90Array = [];
            $due91to120Array = [];
            $due121to150Array = [];
            $due151to180Array = [];
            $due180plusArray = [];
            $totalDueArray = [];
            $currentArray = [];
            foreach ($contactsArray as $ca) {

                $due1to30 = 0;
                $due31to60 = 0;
                $due61to90 = 0;
                $due91to120 = 0;
                $due121to150 = 0;
                $due151to180 = 0;
                $due180 = 0;
                $totalDue = 0;
                $current = 0;
                $getContactDetails = Contact::where('id', $ca)->get()->first();
                $contactType = $getContactDetails->type;
                $days = 30;
                if ($getContactDetails->pay_term_type == 'months') {
                    $getMonth = $getContactDetails->pay_term_number;
                    if ($getMonth) {
                        $currentmonth = date('m');
                        $currentyear = date('Y');
                        $days = 0;
                        for ($month = $getMonth; $month > 0; $month--) {
                            //$days += cal_days_in_month(CAL_GREGORIAN, $currentmonth, $currentyear);
                            $days += date('t', mktime(0, 0, 0, $currentmonth, 1, $currentyear));
                            $currentmonth--;
                        }
                    } else {
                        $days = 30;
                    }
                } elseif ($getContactDetails->pay_term_type == 'days') {
                    $days = $getContactDetails->pay_term_number;
                } else {
                    $days = 30;
                }

                /*Current*/
                $getCurrent = $this->getAgeingQuery($ca);
                $getCurrent = $getCurrent->whereDate('transactions.transaction_date', '>', \carbon\Carbon::now()->subdays($days)->format('Y-m-d'))->get()->first();
                if ($getCurrent) {
                    $getCurrent = $getCurrent->toArray();
                    if (!empty($getCurrent)) {
                        $current = $this->calculateDueAmount($getCurrent, $contactType);
                    }
                }
                $currentArray[] = $current;

                /*1 to 30 days */
                $get1to30Data = $this->getAgeingQuery($ca);
                $toDays30 = $days + 1;
                $fromDays30 = $days + 30;

                $get1to30Data = $get1to30Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays30)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays30)->format('Y-m-d')])->get()->first();


                if ($get1to30Data) {
                    $get1to30Data = $get1to30Data->toArray();
                    if (!empty($get1to30Data)) {
                        $due1to30 = $this->calculateDueAmount($get1to30Data, $contactType);
                    }
                }
                $due1to30Array[] = $due1to30;

                /*31 to 60 days */

                $toDays60 = $days + 31;
                $fromDays60 = $days + 60;
                $get31to60Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get31to60Data->whereDate('transactions.transaction_date', $date);
                // }
                $get31to60Data = $get31to60Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays60)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays60)->format('Y-m-d')])->get()->first();

                if ($get31to60Data) {
                    $get31to60Data = $get31to60Data->toArray();
                    if (!empty($get31to60Data)) {
                        $due31to60 = $this->calculateDueAmount($get31to60Data, $contactType);
                    }
                }
                $due31to60Array[] = $due31to60;

                /*61 to 90 days */
                $toDays90 = $days + 61;
                $fromDays90 = $days + 90;
                $get61to90Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get61to90Data->whereDate('transactions.transaction_date', $date);
                // }
                $get61to90Data = $get61to90Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays90)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays90)->format('Y-m-d')])->get()->first();
                if ($get61to90Data) {
                    $get61to90Data = $get61to90Data->toArray();
                    if (!empty($get61to90Data)) {
                        $due61to90 = $this->calculateDueAmount($get61to90Data, $contactType);
                    }
                }
                $due61to90Array[] = $due61to90;

                /*91 to 120 days */
                $toDays120 = $days + 91;
                $fromDays120 = $days + 120;
                $get91to120Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get91to120Data->whereDate('transactions.transaction_date', $date);
                // }
                $get91to120Data = $get91to120Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays120)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays120)->format('Y-m-d')])->get()->first();
                if ($get91to120Data) {
                    $get91to120Data = $get91to120Data->toArray();
                    if (!empty($get91to120Data)) {
                        $due91to120 = $this->calculateDueAmount($get91to120Data, $contactType);
                    }
                }
                $due91to120Array[] = $due91to120;

                /*121 to 150 days */
                $toDays150 = $days + 121;
                $fromDays150 = $days + 150;
                $get121to150Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get121to150Data->whereDate('transactions.transaction_date', $date);
                // }
                $get121to150Data = $get121to150Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays150)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays150)->format('Y-m-d')])->get()->first();
                if ($get121to150Data) {
                    $get121to150Data = $get121to150Data->toArray();
                    if (!empty($get121to150Data)) {
                        $due121to150 = $this->calculateDueAmount($get121to150Data, $contactType);
                    }
                }
                $due121to150Array[] = $due121to150;

                /*151 to 180 days */
                $toDays180 = $days + 151;
                $fromDays180 = $days + 180;
                $get151to180Data = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get151to180Data->whereDate('transactions.transaction_date', $date);
                // }
                $get151to180Data = $get151to180Data->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays180)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d')])->get()->first();
                if ($get151to180Data) {
                    $get151to180Data = $get151to180Data->toArray();
                    if (!empty($get151to180Data)) {
                        $due151to180 = $this->calculateDueAmount($get151to180Data, $contactType);
                    }
                }
                $due151to180Array[] = $due151to180;

                /*180 plus*/
                $toDays180 = $days + 181;
                $get180plusData = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $get180plusData->whereDate('transactions.transaction_date', $date);
                // }
                $get180plusData = $get180plusData->where('transactions.transaction_date', '<=', \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d'))->get()->first();
                if ($get180plusData) {
                    $get180plusData = $get180plusData->toArray();
                    if (!empty($get180plusData)) {
                        $due180 = $this->calculateDueAmount($get180plusData, $contactType);
                    }
                }
                $due180plusArray[] = $due180;

                /*Total Due*/
                $getTotalDue = $this->getAgeingQuery($ca);
                // if (!empty($date)) {
                //     $getTotalDue->whereDate('transactions.transaction_date', $date);
                // }
                $getTotalDue = $getTotalDue->get()->first();
                if ($getTotalDue) {
                    $getTotalDue = $getTotalDue->toArray();
                    if (!empty($getTotalDue)) {
                        $totalDue = $this->calculateDueAmount($getTotalDue, $contactType);
                    }
                }
                $totalDueArray[] = $totalDue;
            }

            if (request()->get('contact_type') == 'supplier') {
                return view('ageingreport::partials.supplier_contact', compact('contacts', 'due1to30Array', 'due31to60Array', 'due61to90Array', 'due91to120Array', 'due121to150Array', 'due151to180Array', 'due180plusArray', 'totalDueArray', 'currentArray'))->render();
            } else {
                return view('ageingreport::partials.customer_contact', compact('contacts', 'due1to30Array', 'due31to60Array', 'due61to90Array', 'due91to120Array', 'due121to150Array', 'due151to180Array', 'due180plusArray', 'totalDueArray', 'currentArray'))->render();
            }
        }

        $customer_group = CustomerGroup::forDropdown($business_id, false, true);
        $types = [
            '' => __('lang_v1.all'),
            'customer' => __('report.customer'),
            'supplier' => __('report.supplier')
        ];
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $customers = Contact::customersDropdown($business_id, false);
        $suppliers = Contact::suppliersDropdown($business_id);
        return view('ageingreport::index')
            ->with(compact('customer_group', 'types', 'business_locations', 'customers', 'suppliers'));
    }

    public function calculateDueAmount($data, $contactType)
    {
        $due = ($data['total_invoice'] - $data['invoice_received'] - $data['total_sell_return'] + $data['sell_return_paid']) - ($data['total_purchase'] - $data['total_purchase_return'] + $data['purchase_return_received'] - $data['purchase_paid']);
        if ($contactType == 'supplier') {
            $due -= $data['opening_balance'] - $data['opening_balance_paid'];
        } else {
            $due += $data['opening_balance'] - $data['opening_balance_paid'];
        }
        return $due;
    }


     public function getAgeingQuery($contactid)
    {
        $periodRow = Transaction::where('transactions.contact_id', $contactid)->select(
            DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as total_purchase"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(transactions.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_paid"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as invoice_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as sell_return_paid"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_return_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as total_sell_return"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as opening_balance_paid"),
        );
        return $periodRow;
    }

    public function getAgeingQueryDetails($contactid, $transactionsIds)
    {
        $periodRow = Transaction::where('transactions.contact_id', $contactid)->leftjoin(
            'business_locations AS BS',
            'transactions.location_id',
            '=',
            'BS.id'
        )->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')->select(
            DB::raw("SUM(IF(transactions.type = 'purchase', final_total, 0)) as total_purchase"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(transactions.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_paid"),
            DB::raw("SUM(IF(transactions.type = 'sell' AND transactions.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as invoice_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as sell_return_paid"),
            DB::raw("SUM(IF(transactions.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as purchase_return_received"),
            DB::raw("SUM(IF(transactions.type = 'sell_return', final_total, 0)) as total_sell_return"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', final_total, 0)) as opening_balance"),
            DB::raw("SUM(IF(transactions.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id), 0)) as opening_balance_paid"),
            'transactions.*',
            'transactions.id as transaction_id',
            'BS.name as location_name',
            'contacts.name',
            'transactions.status',
            'transactions.payment_status',
            'transactions.ref_no as ref_no',
        );
        return $periodRow;
    }

    public function getAgeingDetails(Request $request)
 //public function index(Request $request)
    {
        $contact_id = request()->get('contact_id', null);
        $business_id = $request->session()->get('user.business_id');
        $col = request()->get('col', null);
        $date_f = request()->get('date', null);
        $contacts = Contact::where('contacts.business_id', $business_id)
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->active()
            ->groupBy('contacts.id')
            ->where('t.contact_id', $contact_id)
            ->select(
                'contacts.supplier_business_name',
                'contacts.name',
                'contacts.id',
                't.id as transaction_id'
            );
        if (!empty($date_f)) {
            $contacts->whereDate('t.transaction_date', $date_f);
        }
        $transactionsIds = $contacts->pluck('transaction_id');
        $data = [];
        $due = 0;

        $getContactDetails = Contact::where('id', $contact_id)->get()->first();
        $contactType = $getContactDetails->type;
        $days = 30;
        if ($getContactDetails->pay_term_type == 'months') {
            $getMonth = $getContactDetails->pay_term_number;
            if ($getMonth) {
                $currentmonth = date('m');
                $currentyear = date('Y');
                $days = 0;
                for ($month = $getMonth; $month > 0; $month--) {
                    //$days += cal_days_in_month(CAL_GREGORIAN, $currentmonth, $currentyear);
                    $days += date('t', mktime(0, 0, 0, $currentmonth, 1, $currentyear));
                    $currentmonth--;
                }
            } else {
                $days = 30;
            }
        } elseif ($getContactDetails->pay_term_type == 'days') {
            $days = $getContactDetails->pay_term_number;
        } else {
            $days = 30;
        }
        $label = '';
        if ($col == '1') {
            /*Current*/
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $data = $data->groupBy('transactions.id')->whereDate('transactions.transaction_date', '>', \carbon\Carbon::now()->subdays($days)->format('Y-m-d'))->get();
            $label = 'Current';
        } elseif ($col == '2') {
            $label = '1-30 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays30 = $days + 1;
            $fromDays30 = $days + 30;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays30)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays30)->format('Y-m-d')])->get();
        } elseif ($col == '3') {
            $label = '31-60 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays60 = $days + 31;
            $fromDays60 = $days + 60;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays60)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays60)->format('Y-m-d')])->get();
        } elseif ($col == '4') {
            $label = '61-90 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays90 = $days + 61;
            $fromDays90 = $days + 90;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays90)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays90)->format('Y-m-d')])->get();
        } elseif ($col == '5') {
            $label = '91-120 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays120 = $days + 91;
            $fromDays120 = $days + 120;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays120)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays120)->format('Y-m-d')])->get();
        } elseif ($col == '6') {
            $label = '121-150 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays150 = $days + 121;
            $fromDays150 = $days + 150;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays150)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays150)->format('Y-m-d')])->get();
        } elseif ($col == '7') {
            $label = '151-180 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays180 = $days + 151;
            $fromDays180 = $days + 180;
            $data = $data->groupBy('transactions.id')->whereBetween('transactions.transaction_date', [\carbon\Carbon::now()->subdays($fromDays180)->format('Y-m-d'), \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d')])->get();
        } elseif ($col == '8') { 
            $label = '>= 180 Days';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $toDays180 = $days + 181;
            $data = $data->groupBy('transactions.id')->where('transactions.transaction_date', '<=', \carbon\Carbon::now()->subdays($toDays180)->format('Y-m-d'))->get();
        } else {
            $label = 'Total Due';
            $data = $this->getAgeingQueryDetails($contact_id, $transactionsIds);
            $data = $data->groupBy('transactions.id')->get();
        }

        $details = view('ageingreport::partials.ageing_details', compact('data', 'getContactDetails', 'label', 'contactType'))->render();
        return response()->json(['details' => $details]);
    }
}