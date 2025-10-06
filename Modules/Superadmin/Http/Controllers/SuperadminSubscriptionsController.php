<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Utils\CurrencyUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Routing\Controller;

class SuperadminSubscriptionsController extends BaseController
{
    protected $businessUtil;

    /**
     * Constructor
     *
     * @param  BusinessUtil  $businessUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $superadmin_subscription = Subscription::join('business', 'subscriptions.business_id', '=', 'business.id')
                ->join('packages', 'subscriptions.package_id', '=', 'packages.id')
                ->select('business.name as business_name', 'packages.name as package_name', 'subscriptions.status',
                 'subscriptions.created_at', 'subscriptions.start_date', 'subscriptions.trial_end_date', 'subscriptions.end_date', 'subscriptions.package_price', 'subscriptions.paid_via', 'subscriptions.payment_transaction_id', 'subscriptions.receipt_file_path', 'subscriptions.selected_bank', 'subscriptions.selected_currency', 'subscriptions.id',
                 'subscriptions.location_quantity', 'subscriptions.price_per_location', 'packages.is_per_location_pricing', 'packages.location_count');

            $statusFilter = request()->input('status');
            if (!empty($statusFilter) && $statusFilter !== 'all') {
                $superadmin_subscription->where('subscriptions.status', $statusFilter);
            } elseif (empty($statusFilter) || $statusFilter === 'all') {
                // If no status is selected or "all" is selected, default to waiting
                $superadmin_subscription->where('subscriptions.status', 'waiting');
            }
            if(!empty(request()->input('package_id'))) {
                $superadmin_subscription->where('packages.id', request()->input('package_id'));
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $superadmin_subscription->whereDate('subscriptions.created_at', '>=', $start)
                    ->whereDate('subscriptions.created_at', '<=', $end);
            }
            
            return DataTables::of($superadmin_subscription)
                        ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                        ->editColumn('trial_end_date', '@if(!empty($trial_end_date)){{@format_date($trial_end_date)}} @endif')
                        ->editColumn('start_date', '@if(!empty($start_date)){{@format_date($start_date)}}@endif')
                        ->editColumn('end_date', '@if(!empty($end_date)){{@format_date($end_date)}}@endif')
                        ->editColumn(
                            'status',
                            '@if($status == "approved")
                                <span class="label bg-light-green">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @elseif($status == "waiting")
                                <span class="label bg-aqua">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @else($status == "declined")
                                <span class="label bg-red">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @endif'
                        )
                        ->editColumn(
                            'package_price',
                            function ($row) {
                                $exchangeRate = CurrencyUtil::getUsdToMvrRate();
                                $mvrAmount = $row->package_price * $exchangeRate;
                                return '<span class="display_currency" data-currency_symbol="true">MVR ' . number_format($mvrAmount, 2) . '</span>';
                            }
                        )
                        ->editColumn('package_name', function ($row) {
                            $location_info = '';
                            if ($row->is_per_location_pricing && !empty($row->location_quantity)) {
                                $location_info = '<br><small><span class="label bg-blue">' . $row->location_quantity . ' locations</span></small>';
                            } elseif ($row->location_count == 0) {
                                $location_info = '<br><small><span class="label bg-green">Unlimited</span></small>';
                            } else {
                                $location_info = '<br><small><span class="label bg-purple">' . $row->location_count . ' locations</span></small>';
                            }
                            return $row->package_name . $location_info;
                        })
                        ->addColumn(
                            'payment_info',
                            '@if(!empty($selected_bank) || !empty($selected_currency) || !empty($receipt_file_path))
                                @if(!empty($selected_bank) && !empty($selected_currency))
                                    <span class="label bg-green">{{strtoupper($selected_bank)}} - {{strtoupper($selected_currency)}}</span>
                                @elseif(!empty($selected_bank))
                                    <span class="label bg-yellow">{{strtoupper($selected_bank)}}</span>
                                @endif
                                @if(!empty($receipt_file_path))
                                    @if(!empty($selected_bank) || !empty($selected_currency))<br>@endif
                                    <button onclick="viewReceipt(\'{{$receipt_file_path}}\', \'{{$selected_bank}}\', \'{{$selected_currency}}\')" class="btn btn-success btn-xs" title="View Receipt" style="margin-top: 3px;">
                                        <i class="fa fa-file-image-o"></i> Receipt
                                    </button>
                                @endif
                            @else
                                -
                            @endif'
                        )
                        ->addColumn(
                            'action',
                            '<button data-href ="{{action(\'\Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController@edit\',[$id])}}" class="btn btn-info btn-xs change_status" data-toggle="modal" data-target="#statusModal">
                            @lang( "superadmin::lang.status")
                            </button> <button data-href ="{{action(\'\Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController@editSubscription\',["id" => $id])}}" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal">
                            @lang( "messages.edit")
                            </button>'
                        )
                        ->removeColumn('id')
                        ->removeColumn('receipt_file_path')
                        ->removeColumn('selected_bank')
                        ->removeColumn('selected_currency')
                        ->removeColumn('location_quantity')
                        ->removeColumn('price_per_location')
                        ->removeColumn('is_per_location_pricing')
                        ->removeColumn('location_count')
                        ->rawColumns([1, 2, 7, 10, 11])
                        ->make(false);
        }

        $packages = Package::listPackages()->pluck('name', 'id');

        $subscription_statuses = [
            'approved' => __('superadmin::lang.approved'),
            'waiting' => __('superadmin::lang.waiting'),
            'declined' => __('superadmin::lang.declined'),
        ];

        return view('superadmin::superadmin_subscription.index')
                    ->with(compact('packages', 'subscription_statuses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->input('business_id');
        $packages = Package::active()->orderby('sort_order')->pluck('name', 'id');

        $gateways = $this->_payment_gateways();

        return view('superadmin::superadmin_subscription.add_subscription')
              ->with(compact('packages', 'business_id', 'gateways'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $input = $request->only(['business_id', 'package_id', 'paid_via', 'payment_transaction_id']);
            $package = Package::find($input['package_id']);
            $user_id = $request->session()->get('user.id');

            $subscription = $this->_add_subscription($input['business_id'], $package, $input['paid_via'], $input['payment_transaction_id'], $user_id, true);

            DB::commit();

            $output = ['success' => 1,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $status = Subscription::package_subscription_status();
            $subscription = Subscription::find($id);

            return view('superadmin::superadmin_subscription.edit')
                        ->with(compact('subscription', 'status'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['status', 'payment_transaction_id']);

                $subscriptions = Subscription::findOrFail($id);

                // Handle approval: Set dates if not already set
                if ($subscriptions->status != 'approved' && empty($subscriptions->start_date) && $input['status'] == 'approved') {
                    $dates = $this->_get_package_dates($subscriptions->business_id, $subscriptions->package);
                    $subscriptions->start_date = $dates['start'];
                    $subscriptions->end_date = $dates['end'];
                    $subscriptions->trial_end_date = $dates['trial'];
                }

                // Handle decline: If subscription was previously started (has start_date), deactivate it immediately
                if ($input['status'] == 'declined' && !empty($subscriptions->start_date)) {
                    // End the subscription immediately by setting end_date to yesterday
                    // This ensures it's no longer considered active
                    $subscriptions->end_date = now()->subDay()->toDateString();
                }

                $subscriptions->status = $input['status'];
                $subscriptions->payment_transaction_id = $input['payment_transaction_id'];
                $subscriptions->save();

                $output = ['success' => true,
                    'msg' => __('superadmin::lang.subcription_updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy()
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function editSubscription($id)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $subscription = Subscription::find($id);

            return view('superadmin::superadmin_subscription.edit_date_modal')
                        ->with(compact('subscription'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function updateSubscription(Request $request)
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['start_date', 'end_date', 'trial_end_date']);

                $subscription = Subscription::findOrFail($request->input('subscription_id'));

                $subscription->start_date = ! empty($input['start_date']) ? $this->businessUtil->uf_date($input['start_date']) : null;
                $subscription->end_date = ! empty($input['end_date']) ? $this->businessUtil->uf_date($input['end_date']) : null;
                $subscription->trial_end_date = ! empty($input['trial_end_date']) ? $this->businessUtil->uf_date($input['trial_end_date']) : null;
                $subscription->save();

                $output = ['success' => true,
                    'msg' => __('superadmin::lang.subcription_updated_success'),
                ];
            } catch (\Exception $e) {
                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = ['success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }
}
