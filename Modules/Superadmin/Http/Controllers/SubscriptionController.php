<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Business;
use App\System;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Notifications\SubscriptionOfflinePaymentActivationConfirmation;
use Notification;
use Paystack;
use Pesapal;
use Razorpay\Api\Api;
use Srmklive\PayPal\Services\ExpressCheckout;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Price;
use Stripe\Product;
use Stripe\Subscription as StripeSubscription;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends BaseController
{
    protected $provider;

    public function __construct(ModuleUtil $moduleUtil = null)
    {
        if (! defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6);
        }

        if (! defined('CURLOPT_SSLVERSION')) {
            define('CURLOPT_SSLVERSION', 6);
        }

        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }    

        $user = auth()->user();
        if ($user->is_email_verified == "0") {
            return redirect()->route('home');
        }
        
        $business_id = request()->session()->get('user.business_id');

        //Get active subscription and upcoming subscriptions.
        $active = Subscription::active_subscription($business_id);

        $nexts = Subscription::upcoming_subscriptions($business_id);
        $waiting = Subscription::waiting_approval($business_id);

        $packages = Package::active()->orderby('sort_order')->get();

        //Get all module permissions and convert them into name => label
        $permissions = $this->moduleUtil->getModuleData('superadmin_package');
        $permission_formatted = [];
        foreach ($permissions as $permission) {
            foreach ($permission as $details) {
                $permission_formatted[$details['name']] = $details['label'];
            }
        }

      /*   echo "<pre>"; */
        $user_created_at = $user->created_at;
        $user_created_date = $user_created_at->format('Y-m-d');
        
       

        $intervals = ['days' => __('lang_v1.days'), 'months' => __('lang_v1.months'), 'years' => __('lang_v1.years')];

        return view('superadmin::subscription.index')
            ->with(compact('packages', 'active', 'nexts', 'waiting', 'permission_formatted', 'intervals', 'user_created_date'));
    }

    /**
     * Show pay form for a new package.
     *
     * @return Response
     */
    public function pay($package_id, $form_register = null)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');

            $package = Package::active()->find($package_id);

            // Handle per-location packages - show location quantity selection
            if ($package->is_per_location_pricing && !request()->has('location_quantity_confirmed')) {
                DB::commit();
                return view('superadmin::subscription.select_location_quantity')
                    ->with(compact('package'));
            }

            // Process per-location package with selected quantity
            if ($package->is_per_location_pricing) {
                $location_quantity = (int)request()->input('location_quantity', $package->min_locations);
                
                // Validate quantity within bounds
                $location_quantity = max($package->min_locations, $location_quantity);
                if ($package->max_locations > 0) {
                    $location_quantity = min($package->max_locations, $location_quantity);
                }
                
                // Store the selected quantity for later use in subscription creation
                session(['selected_location_quantity' => $location_quantity]);
                
                // Temporarily override the package location_count for validation
                $package->location_count = $location_quantity;
            }

            //Check if superadmin only package
            if ($package->is_private == 1 && ! auth()->user()->can('superadmin')) {
                $output = ['success' => 0, 'msg' => __('superadmin::lang.not_allowed_for_package')];

                return redirect()
                        ->back()
                        ->with('status', $output);
            }

            //Check if one time only package
            if (empty($form_register) && $package->is_one_time) {
                $count_subcriptions = Subscription::where('business_id', $business_id)
                                                ->where('package_id', $package_id)
                                                ->count();

                if ($count_subcriptions > 0) {
                    $output = ['success' => 0, 'msg' => __('superadmin::lang.maximum_subscription_limit_exceed')];

                    return redirect()
                        ->back()
                        ->with('status', $output);
                }
            }

            //Check active business locations against package limit
            $active_locations_count = \App\BusinessLocation::where('business_id', $business_id)
                                                          ->where('is_active', 1)
                                                          ->count();
            
            // Check if current subscription allows unlimited locations (active or waiting status)
            $current_subscription = Subscription::where('business_id', $business_id)
                                                ->whereIn('status', ['approved', 'waiting'])
                                                ->orderBy('created_at', 'desc')
                                                ->first();
            
            $current_allows_unlimited = false;
            if ($current_subscription) {
                $current_allows_unlimited = $current_subscription->allowsUnlimitedLocations();
            }
            
            // For per-location packages, get the effective location count based on user selection
            $new_location_limit = $package->is_per_location_pricing ? $package->location_count : $package->location_count;
            
            // Validate location limits when:
            // 1. New package has limits (new_location_limit > 0) AND
            // 2. Active locations exceed new limit OR (equal to limit AND coming from unlimited plan)
            $needs_location_selection = false;
            
            if ($new_location_limit > 0) {
                if ($active_locations_count > $new_location_limit) {
                    // Always show warning if exceeding limit
                    $needs_location_selection = true;
                } elseif ($active_locations_count == $new_location_limit && $current_allows_unlimited) {
                    // Show warning if exactly at limit but coming from unlimited plan (let user choose which locations to keep)
                    $needs_location_selection = true;
                }
            }
            
            if ($needs_location_selection) {
                $excess_locations = max(0, $active_locations_count - $new_location_limit);
                $locations_to_deactivate = \App\BusinessLocation::where('business_id', $business_id)
                                                               ->where('is_active', 1)
                                                               ->orderBy('created_at', 'desc')
                                                               ->get(); // Show ALL active locations, not just excess
                
                // If exactly at limit from unlimited, still show selection to let user choose which locations to keep
                if ($excess_locations == 0 && $current_allows_unlimited) {
                    $excess_locations = 0; // Will show all locations but require 0 deactivations (user choice)
                }
                
                DB::commit();
                return view('superadmin::subscription.location_limit_warning')
                    ->with(compact('package', 'active_locations_count', 'excess_locations', 'locations_to_deactivate'));
            }

            //Check for free package & subscribe it.
            if ($package->price == 0) {
                $gateway = null;
                $payment_transaction_id = 'FREE';
                $user_id = request()->session()->get('user.id');

                $this->_add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id);

                DB::commit();

                if (empty($form_register)) {
                    $output = ['success' => 1, 'msg' => __('lang_v1.success')];

                    return redirect()
                        ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                        ->with('status', $output);
                } else {
                    $output = ['success' => 1, 'msg' => __('superadmin::lang.registered_and_subscribed')];

                    return redirect()
                        ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                        ->with('status', $output);
                }
            }

            $gateways = $this->_payment_gateways();

            $system_currency = System::getCurrency();

            DB::commit();

            if (empty($form_register)) {
                $layout = 'layouts.app';
            } else {
                $layout = 'layouts.auth';
            }

            $user = request()->session()->get('user');

            $offline_payment_details = System::getProperty('offline_payment_details');
            
            // Get bank account settings
            $bank_settings = [
                'bml_usd_account_name' => System::getProperty('bml_usd_account_name') ?: 'Ahmed Nazeeh',
                'bml_usd_account_number' => System::getProperty('bml_usd_account_number') ?: '7730000153906',
                'bml_mvr_account_name' => System::getProperty('bml_mvr_account_name') ?: 'Cleviden Pvt Ltd',
                'bml_mvr_account_number' => System::getProperty('bml_mvr_account_number') ?: '7730000757923',
                'mib_usd_account_name' => System::getProperty('mib_usd_account_name') ?: 'Cleviden Pvt Ltd',
                'mib_usd_account_number' => System::getProperty('mib_usd_account_number') ?: '90501480029672000',
                'mib_mvr_account_name' => System::getProperty('mib_mvr_account_name') ?: 'Cleviden Pvt Ltd',
                'mib_mvr_account_number' => System::getProperty('mib_mvr_account_number') ?: '90501480029671000',
            ];

            return view('superadmin::subscription.pay')
                ->with(compact('package', 'gateways', 'system_currency', 'layout', 'user', 'offline_payment_details', 'bank_settings'));
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0, 'msg' => 'File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage()];

            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                ->with('status', $output);
        }
    }

    /**
     * Show pay form for a new package.
     *
     * @return Response
     */
    public function registerPay($package_id)
    {
        return $this->pay($package_id, 1);
    }

    /**
     * Save the payment details and add subscription details
     *
     * @return Response
     */
    public function confirm($package_id, Request $request)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                    'msg' => 'Feature disabled in demo!!',
                ];

                return back()->with('status', $output);
            }

            //Confirm for pesapal payment gateway
            if (isset($this->_payment_gateways()['pesapal']) && (strpos($request->merchant_reference, 'PESAPAL') !== false)) {
                return $this->confirm_pesapal($package_id, $request);
            }

            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $business_name = request()->session()->get('business.name');
            $user_id = request()->session()->get('user.id');
            $package = Package::active()->find($package_id);

            //Call the payment method
            $pay_function = 'pay_'.request()->gateway;
            $payment_result = null;
            if (method_exists($this, $pay_function)) {
                $payment_result = $this->$pay_function($business_id, $business_name, $package, $request);
            }

            // Handle different payment result types
            if (is_array($payment_result) && isset($payment_result['requires_action'])) {
                // Payment requires additional authentication (3D Secure)
                DB::commit();
                
                return view('superadmin::subscription.stripe_3ds_confirmation')
                    ->with([
                        'payment_intent_client_secret' => $payment_result['payment_intent_client_secret'],
                        'subscription_id' => $payment_result['subscription_id'],
                        'package' => $package,
                        'return_url' => action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'stripeConfirmSubscription'])
                    ]);
            }

            // Standard payment processing
            $payment_transaction_id = is_array($payment_result) ? $payment_result['subscription_id'] : $payment_result;

            //Add subscription details after payment is succesful
            $this->_add_subscription($business_id, $package_id, request()->gateway, $payment_transaction_id, $user_id);
            DB::commit();

            $msg = __('lang_v1.success');
            if (request()->gateway == 'offline') {
                $msg = __('superadmin::lang.notification_sent_for_approval');
            }
            $output = ['success' => 1, 'msg' => $msg];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            echo 'File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage();
            exit;
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return redirect()
            ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Confirm for pesapal gateway
     * when payment gateway is PesaPal payment gateway request package_id
     * is transaction_id & merchant_reference in session contains
     * the package_id.
     *
     * @return Response
     */
    protected function confirm_pesapal($transaction_id, $request)
    {
        $merchant_reference = $request->merchant_reference;
        $pesapal_session = $request->session()->pull('pesapal');

        if ($pesapal_session['ref'] == $merchant_reference) {
            $package_id = $pesapal_session['package_id'];

            $business_id = request()->session()->get('user.business_id');
            $business_name = request()->session()->get('business.name');
            $user_id = request()->session()->get('user.id');
            $package = Package::active()->find($package_id);

            $this->_add_subscription($business_id, $package, 'pesapal', $transaction_id, $user_id);
            $output = ['success' => 1, 'msg' => __('superadmin::lang.waiting_for_confirmation')];

            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                ->with('status', $output);
        }
    }

    /**
     * Stripe payment method - creates subscription with auto-renewal using Payment Methods API
     *
     * @return Response
     */
    protected function pay_stripe($business_id, $business_name, $package, $request)
    {
        $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
        $system_currency = System::getCurrency();

        $metadata = [
            'business_id' => $business_id, 
            'business_name' => $business_name, 
            'customer_email' => $request->customer_email ?? $request->stripeEmail,
            'package_name' => $package->name,
            'package_id' => $package->id,
            'user_id' => request()->session()->get('user.id')
        ];

        try {
            // Create or retrieve customer
            $customer = $stripe->customers->create([
                'name' => $request->customer_name ?? $business_name,
                'email' => $request->customer_email ?? $request->stripeEmail,
                'metadata' => $metadata,
                'description' => 'Subscription for ' . $business_name,
            ]);

            // Attach payment method to customer if provided
            if (!empty($request->payment_method_id)) {
                $stripe->paymentMethods->attach($request->payment_method_id, [
                    'customer' => $customer->id,
                ]);

                // Set as default payment method
                $stripe->customers->update($customer->id, [
                    'invoice_settings' => [
                        'default_payment_method' => $request->payment_method_id,
                    ],
                ]);
            }

            // Create or retrieve product for this package
            $product = $this->getOrCreateStripeProduct($stripe, $package);

            // Create or retrieve price for this package
            $price = $this->getOrCreateStripePrice($stripe, $product, $package, $system_currency);

            // Create subscription with automatic payment
            $subscriptionData = [
                'customer' => $customer->id,
                'items' => [
                    ['price' => $price->id],
                ],
                'metadata' => $metadata,
                'expand' => ['latest_invoice.payment_intent'],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => [
                    'save_default_payment_method' => 'on_subscription',
                ],
            ];

            // Add payment method if provided
            if (!empty($request->payment_method_id)) {
                $subscriptionData['default_payment_method'] = $request->payment_method_id;
            }

            $subscription = $stripe->subscriptions->create($subscriptionData);

            // Store customer ID in session for later use
            session(['stripe_customer_id' => $customer->id]);

            // Check if subscription requires additional authentication
            if ($subscription->status === 'incomplete') {
                $latest_invoice = $subscription->latest_invoice;
                if ($latest_invoice && $latest_invoice->payment_intent) {
                    $payment_intent = $latest_invoice->payment_intent;
                    
                    if ($payment_intent->status === 'requires_action') {
                        // Store subscription ID for confirmation
                        session(['stripe_subscription_pending' => $subscription->id]);
                        
                        // Return payment intent client secret for frontend confirmation
                        return [
                            'requires_action' => true,
                            'payment_intent_client_secret' => $payment_intent->client_secret,
                            'subscription_id' => $subscription->id
                        ];
                    }
                }
            }

            return $subscription->id;

        } catch (\Exception $e) {
            \Log::error('Stripe subscription creation failed: ' . $e->getMessage());
            throw new \Exception('Stripe subscription creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get or create Stripe product for package
     */
    protected function getOrCreateStripeProduct($stripe, $package)
    {
        try {
            // Try to retrieve existing product
            $products = $stripe->products->all(['limit' => 100]);
            foreach ($products->data as $product) {
                if (isset($product->metadata['package_id']) && $product->metadata['package_id'] == $package->id) {
                    return $product;
                }
            }
        } catch (\Exception $e) {
            // Product doesn't exist, create new one
        }

        // Create new product
        return $stripe->products->create([
            'name' => $package->name,
            'description' => $package->description ?? 'Subscription package',
            'metadata' => ['package_id' => $package->id],
        ]);
    }

    /**
     * Get or create Stripe price for package
     */
    protected function getOrCreateStripePrice($stripe, $product, $package, $system_currency)
    {
        try {
            // Try to retrieve existing price
            $prices = $stripe->prices->all(['product' => $product->id, 'limit' => 100]);
            foreach ($prices->data as $price) {
                if ($price->unit_amount == ($package->price * 100) && 
                    $price->currency == strtolower($system_currency->code)) {
                    return $price;
                }
            }
        } catch (\Exception $e) {
            // Price doesn't exist, create new one
        }

        // Determine billing interval from package
        $interval = $this->getPackageInterval($package);

        // Create new price
        return $stripe->prices->create([
            'unit_amount' => $package->price * 100,
            'currency' => strtolower($system_currency->code),
            'recurring' => ['interval' => $interval],
            'product' => $product->id,
            'metadata' => ['package_id' => $package->id],
        ]);
    }

    /**
     * Determine billing interval from package
     */
    protected function getPackageInterval($package)
    {
        // Check package details for interval information
        if (isset($package->package_details['interval'])) {
            return $package->package_details['interval'];
        }

        // Default to monthly if no interval specified
        if ($package->interval_type == 'years') {
            return 'year';
        } elseif ($package->interval_type == 'days') {
            return 'day';
        } else {
            return 'month'; // Default to monthly
        }
    }

    /**
     * Offline payment method
     *
     * @return Response
     */
    protected function pay_offline($business_id, $business_name, $package, $request)
    {

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                'msg' => 'Feature disabled in demo!!',
            ];

            return back()->with('status', $output);
        }

        // Handle receipt file upload
        $receipt_file_path = null;
        if ($request->hasFile('receipt_upload')) {
            $receipt_file = $request->file('receipt_upload');
            
            // Generate unique filename
            $filename = 'receipt_' . $business_id . '_' . time() . '.' . $receipt_file->getClientOriginalExtension();
            
            // Store file in storage/app/public/receipts directory
            $receipt_file_path = $receipt_file->storeAs('receipts', $filename, 'public');
        }

        // Store receipt and bank transfer details in session for _add_subscription method
        session(['offline_payment_data' => [
            'receipt_file_path' => $receipt_file_path,
            'selected_bank' => $request->input('selected_bank'),
            'selected_currency' => $request->input('selected_currency')
        ]]);

        //Send notification
        $email = System::getProperty('email');
        $business = Business::find($business_id);

        if (! $this->moduleUtil->IsMailConfigured()) {
            return null;
        }
        $system_currency = System::getCurrency();
        $package->price = $system_currency->symbol.number_format($package->price, 2, $system_currency->decimal_separator, $system_currency->thousand_separator);

        Notification::route('mail', $email)
            ->notify(new SubscriptionOfflinePaymentActivationConfirmation($business, $package));

        return null;
    }

    /**
     * Paypal payment method
     *
     * @return Response
     */
    protected function pay_paypal($business_id, $business_name, $package, $request)
    {
        //Set config to use the currency
        $system_currency = System::getCurrency();
        $provider = new ExpressCheckout();
        config(['paypal.currency' => $system_currency->code]);

        $provider = new ExpressCheckout();
        $response = $provider->getExpressCheckoutDetails($request->token);

        $token = $request->get('token');
        $PayerID = $request->get('PayerID');
        $invoice_id = $response['INVNUM'];

        // if response ACK value is not SUCCESS or SUCCESSWITHWARNING we return back with error
        if (! in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
        }

        $data = [];
        $data['items'] = [
            [
                'name' => $package->name,
                'price' => (float) $package->price,
                'qty' => 1,
            ],
        ];
        $data['invoice_id'] = $invoice_id;
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'], [$package->id]);
        $data['cancel_url'] = action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package->id]);
        $data['total'] = (float) $package->price;

        // if payment is not recurring just perform transaction on PayPal and get the payment status
        $payment_status = $provider->doExpressCheckoutPayment($data, $token, $PayerID);
        $status = isset($payment_status['PAYMENTINFO_0_PAYMENTSTATUS']) ? $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'] : null;

        if (! empty($status) && $status != 'Invalid') {
            return $invoice_id;
        } else {
            $error = 'Something went wrong with paypal transaction';
            throw new \Exception($error);
        }
    }

    /**
     * Paypal payment method - redirect to paypal url for payments
     *
     * @return Response
     */
    public function paypalExpressCheckout(Request $request, $package_id)
    {

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                'msg' => 'Feature disabled in demo!!',
            ];

            return back()->with('status', $output);
        }

        // Get the cart data or package details.
        $package = Package::active()->find($package_id);

        $data = [];
        $data['items'] = [
            [
                'name' => $package->name,
                'price' => (float) $package->price,
                'qty' => 1,
            ],
        ];
        $data['invoice_id'] = Str::random(5);
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'], [$package_id]).'?gateway=paypal';
        $data['cancel_url'] = action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package_id]);
        $data['total'] = (float) $package->price;

        // send a request to paypal
        // paypal should respond with an array of data
        // the array should contain a link to paypal's payment system
        $system_currency = System::getCurrency();
        $provider = new ExpressCheckout();
        $response = $provider->setCurrency(strtoupper($system_currency->code))->setExpressCheckout($data);

        // if there is no link redirect back with error message
        if (! $response['paypal_link']) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
            //For the actual error message dump out $response and see what's in there
        }

        // redirect to paypal
        // after payment is done paypal
        // will redirect us back to $this->expressCheckoutSuccess
        return redirect($response['paypal_link']);
    }

    /**
     * Razor pay payment method
     *
     * @return Response
     */
    protected function pay_razorpay($business_id, $business_name, $package, $request)
    {
        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        $payment = $razorpay_api->payment->fetch($razorpay_payment_id)->capture(['amount' => $package->price * 100]); // Captures a payment

        if (empty($payment->error_code)) {
            return $payment->id;
        } else {
            $error_description = $payment->error_description;
            throw new \Exception($error_description);
        }
    }

    /**
     * Redirect the User to Paystack Payment Page
     *
     * @return Url
     */
    public function getRedirectToPaystack()
    {
        return Paystack::getAuthorizationUrl()->redirectNow();
    }

    /**
     * Obtain Paystack payment information
     *
     * @return void
     */
    public function postPaymentPaystackCallback()
    {
        $payment = Paystack::getPaymentData();
        $business_id = $payment['data']['metadata']['business_id'];
        $package_id = $payment['data']['metadata']['package_id'];
        $gateway = $payment['data']['metadata']['gateway'];
        $payment_transaction_id = $payment['data']['reference'];
        $user_id = $payment['data']['metadata']['user_id'];

        if ($payment['status']) {
            //Add subscription
            $this->_add_subscription($business_id, $package_id, $gateway, $payment_transaction_id, $user_id);

            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                ->with('status', ['success' => 1, 'msg' => __('lang_v1.success')]);
        } else {
            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package_id])
                ->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Obtain Flutterwave payment information
     *
     * @return response
     */
    public function postFlutterwavePaymentCallback(Request $request)
    {
        $url = 'https://api.flutterwave.com/v3/transactions/'.$request->get('transaction_id').'/verify';
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer '.env('FLUTTERWAVE_SECRET_KEY'),
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $header,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $payment = json_decode($response, true);

        if ($payment['status'] == 'success') {
            //Add subscription
            $business_id = $payment['data']['meta']['business_id'];
            $package_id = $payment['data']['meta']['package_id'];
            $gateway = $payment['data']['meta']['gateway'];
            $payment_transaction_id = $payment['data']['tx_ref'];
            $user_id = $payment['data']['meta']['user_id'];

            $this->_add_subscription($business_id, $package_id, $gateway, $payment_transaction_id, $user_id);

            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                ->with('status', ['success' => 1, 'msg' => __('lang_v1.success')]);
        } else {
            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], [$package_id])
                ->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $subscription = Subscription::where('business_id', $business_id)
                                    ->with(['package', 'created_user', 'business'])
                                    ->find($id);

        $system_settings = System::getProperties([
            'invoice_business_name',
            'email',
            'invoice_business_landmark',
            'invoice_business_city',
            'invoice_business_zip',
            'invoice_business_state',
            'invoice_business_country',
            'superadmin_business_tin',
            'superadmin_gst_percentage',
        ]);
        $system = [];
        foreach ($system_settings as $setting) {
            $system[$setting['key']] = $setting['value'];
        }

        return view('superadmin::subscription.show_subscription_modal')
            ->with(compact('subscription', 'system'));
    }

    /**
     * Retrieves list of all subscriptions for the current business
     *
     * @return \Illuminate\Http\Response
     */
    public function allSubscriptions()
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $subscriptions = Subscription::where('subscriptions.business_id', $business_id)
                        ->where('subscriptions.status', 'approved') // Only show approved subscriptions
                        ->leftjoin(
                            'packages as P',
                            'subscriptions.package_id',
                            '=',
                            'P.id'
                        )
                        ->leftjoin(
                            'users as U',
                            'subscriptions.created_id',
                            '=',
                            'U.id'
                        )
                        ->addSelect(
                            'P.name as package_name',
                            DB::raw("CONCAT(COALESCE(U.surname, ''), ' ', COALESCE(U.first_name, ''), ' ', COALESCE(U.last_name, '')) as created_by"),
                            'subscriptions.*'
                        );

        return Datatables::of($subscriptions)
             ->editColumn(
                 'start_date',
                 '@if(!empty($start_date)){{@format_date($start_date)}}@endif'
             )
             ->editColumn(
                 'end_date',
                 '@if(!empty($end_date)){{@format_date($end_date)}}@endif'
             )
             ->editColumn(
                 'trial_end_date',
                 '@if(!empty($trial_end_date)){{@format_date($trial_end_date)}}@endif'
             )
             ->editColumn(
                 'package_price',
                 '<span class="display_currency" data-currency_symbol="true">{{$package_price}}</span>'
             )
             ->editColumn(
                 'created_at',
                 '@if(!empty($created_at)){{@format_date($created_at)}}@endif'
             )
             ->filterColumn('created_by', function ($query, $keyword) {
                 $query->whereRaw("CONCAT(COALESCE(U.surname, ''), ' ', COALESCE(U.first_name, ''), ' ', COALESCE(U.last_name, '')) like ?", ["%{$keyword}%"]);
             })
             ->addColumn('action', function ($row) {
                 return '<button type="button" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal" data-href="'.action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'show'], $row->id).'" ><i class="fa fa-eye" aria-hidden="true"></i> '.__('messages.view').'</button>';
             })
             ->rawColumns(['package_price', 'action'])
             ->make(true);
    }

    /**
     * Stripe subscription management page
     */
    public function stripeManage()
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        // Get active Stripe subscription
        $subscription = Subscription::where('business_id', $business_id)
            ->where('paid_via', 'stripe')
            ->whereNotNull('stripe_subscription_id')
            ->whereIn('status', ['approved', 'waiting'])
            ->with('package')
            ->first();

        if (!$subscription) {
            $output = ['success' => 0, 'msg' => 'No active Stripe subscription found'];
            return redirect()->back()->with('status', $output);
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            
            return view('superadmin::subscription.stripe_manage')
                ->with(compact('subscription', 'stripeSubscription'));
                
        } catch (\Exception $e) {
            $output = ['success' => 0, 'msg' => 'Error retrieving subscription details: ' . $e->getMessage()];
            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Cancel Stripe subscription
     */
    public function stripeCancel(Request $request)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $subscription = Subscription::where('business_id', $business_id)
            ->where('paid_via', 'stripe')
            ->whereNotNull('stripe_subscription_id')
            ->whereIn('status', ['approved', 'waiting'])
            ->first();

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found']);
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
            
            // Cancel at period end or immediately
            $cancelAtPeriodEnd = $request->input('cancel_at_period_end', true);
            
            if ($cancelAtPeriodEnd) {
                $stripe->subscriptions->update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => true
                ]);
                $message = 'Subscription will be cancelled at the end of the current billing period';
            } else {
                $stripe->subscriptions->cancel($subscription->stripe_subscription_id);
                $message = 'Subscription cancelled immediately';
            }

            return response()->json(['success' => true, 'message' => $message]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error cancelling subscription: ' . $e->getMessage()]);
        }
    }

    /**
     * Update payment method for Stripe subscription
     */
    public function stripeUpdatePayment(Request $request)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $subscription = Subscription::where('business_id', $business_id)
            ->where('paid_via', 'stripe')
            ->whereNotNull('stripe_subscription_id')
            ->whereIn('status', ['approved', 'waiting'])
            ->first();

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found']);
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
            
            // Update the customer's default payment method
            $stripe->customers->update($subscription->stripe_customer_id, [
                'source' => $request->stripeToken
            ]);

            return response()->json(['success' => true, 'message' => 'Payment method updated successfully']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating payment method: ' . $e->getMessage()]);
        }
    }

    /**
     * Redirect to Stripe Customer Portal
     */
    public function stripeCustomerPortal()
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $subscription = Subscription::where('business_id', $business_id)
            ->where('paid_via', 'stripe')
            ->whereNotNull('stripe_customer_id')
            ->whereIn('status', ['approved', 'waiting'])
            ->first();

        if (!$subscription) {
            $output = ['success' => 0, 'msg' => 'No active Stripe subscription found'];
            return redirect()->back()->with('status', $output);
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
            
            $session = $stripe->billingPortal->sessions->create([
                'customer' => $subscription->stripe_customer_id,
                'return_url' => route('subscription.index'),
            ]);

            return redirect($session->url);
            
        } catch (\Exception $e) {
            $output = ['success' => 0, 'msg' => 'Error creating customer portal session: ' . $e->getMessage()];
            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Confirm Stripe subscription after 3D Secure authentication
     */
    public function stripeConfirmSubscription(Request $request)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
            $subscription_id = $request->subscription_id ?? session('stripe_subscription_pending');
            
            if (!$subscription_id) {
                throw new \Exception('No subscription ID found');
            }

            // Retrieve the subscription to check its status
            $subscription = $stripe->subscriptions->retrieve($subscription_id, [
                'expand' => ['latest_invoice.payment_intent']
            ]);

            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            if ($subscription->status === 'active') {
                // Payment succeeded, create local subscription record
                $package = Package::find($subscription->metadata->package_id);
                
                if ($package) {
                    $this->_add_subscription($business_id, $package, 'stripe', $subscription_id, $user_id);
                }

                // Clear pending subscription from session
                session()->forget('stripe_subscription_pending');

                $output = ['success' => 1, 'msg' => __('superadmin::lang.subscription_activated_successfully')];
                
                return redirect()
                    ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                    ->with('status', $output);
                    
            } else {
                // Payment failed or still incomplete
                $output = ['success' => 0, 'msg' => __('superadmin::lang.payment_authentication_failed')];
                
                return redirect()
                    ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                    ->with('status', $output);
            }

        } catch (\Exception $e) {
            \Log::error('Stripe subscription confirmation failed: ' . $e->getMessage());
            
            $output = ['success' => 0, 'msg' => __('superadmin::lang.subscription_confirmation_failed')];
            
            return redirect()
                ->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])
                ->with('status', $output);
        }
    }

    /**
     * Handle location deactivation and proceed with subscription
     */
    public function deactivateLocationsAndProceed(Request $request, $package_id)
    {
        if (! auth()->user()->can('superadmin.access_package_subscriptions')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $package = Package::active()->find($package_id);
        
        if (!$package) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => 'Package not found']);
        }

        // Get selected locations to deactivate
        $locations_to_deactivate = $request->input('locations_to_deactivate', []);
        
        if (!empty($locations_to_deactivate)) {
            \App\BusinessLocation::whereIn('id', $locations_to_deactivate)
                                ->where('business_id', $business_id)
                                ->update(['is_active' => 0]);
        }

        // Redirect to payment page
        return redirect()->action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'pay'], $package_id);
    }

    /**
     * Validate location reactivation against subscription limits
     */
    public function validateLocationReactivation(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $location_id = $request->input('location_id');

        // Get current active subscription
        $active_subscription = Subscription::active_subscription($business_id);
        
        if (!$active_subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found']);
        }

        $package = Package::find($active_subscription->package_id);
        
        if (!$package) {
            return response()->json(['success' => false, 'message' => 'Package not found']);
        }

        // Count current active locations
        $current_active_count = \App\BusinessLocation::where('business_id', $business_id)
                                                   ->where('is_active', 1)
                                                   ->count();

        // Check if reactivating this location would exceed the limit
        if (($current_active_count + 1) > $package->location_count) {
            return response()->json([
                'success' => false, 
                'requires_upgrade' => true,
                'message' => "Your current plan allows {$package->location_count} active locations. You have {$current_active_count} active locations. Please upgrade your subscription to activate more locations.",
                'current_package' => $package->name,
                'current_limit' => $package->location_count,
                'current_active' => $current_active_count
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Location can be activated']);
    }
}
