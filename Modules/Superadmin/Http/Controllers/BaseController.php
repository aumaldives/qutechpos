<?php

namespace Modules\Superadmin\Http\Controllers;

use App\System;
use App\Utils\Util;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Notifications\NewSubscriptionNotification;
use Modules\Superadmin\Utils\CurrencyUtil;
use Notification;

class BaseController extends Controller
{
    /**
     * Returns the list of all configured payment gateway
     *
     * @return Response
     */
    public function _payment_gateways()
    {
        $gateways = [];

        //Check if stripe is configured or not
        if (env('STRIPE_PUB_KEY') && env('STRIPE_SECRET_KEY')) {
            $gateways['stripe'] = 'Stripe';
        }

        //Check if paypal is configured or not
        if ((env('PAYPAL_SANDBOX_API_USERNAME') && env('PAYPAL_SANDBOX_API_PASSWORD') && env('PAYPAL_SANDBOX_API_SECRET')) || (env('PAYPAL_LIVE_API_USERNAME') && env('PAYPAL_LIVE_API_PASSWORD') && env('PAYPAL_LIVE_API_SECRET'))) {
            $gateways['paypal'] = 'PayPal';
        }

        //Check if Razorpay is configured or not
        if ((env('RAZORPAY_KEY_ID') && env('RAZORPAY_KEY_SECRET'))) {
            $gateways['razorpay'] = 'Razor Pay';
        }

        //Check if Pesapal is configured or not
        if ((config('pesapal.consumer_key') && config('pesapal.consumer_secret'))) {
            $gateways['pesapal'] = 'PesaPal';
        }

        //check if Paystack is configured or not
        $system = System::getCurrency();
        if (in_array($system->country, ['Nigeria', 'Ghana']) && (config('paystack.publicKey') && config('paystack.secretKey'))) {
            $gateways['paystack'] = 'Paystack';
        }

        //check if Flutterwave is configured or not
        if (env('FLUTTERWAVE_PUBLIC_KEY') && env('FLUTTERWAVE_SECRET_KEY') && env('FLUTTERWAVE_ENCRYPTION_KEY')) {
            $gateways['flutterwave'] = 'Flutterwave';
        }

        // check if offline payment is enabled or not
        $is_offline_payment_enabled = System::getProperty('enable_offline_payment');

        if ($is_offline_payment_enabled) {
            $gateways['offline'] = 'Offline';
        }

        return $gateways;
    }

    /**
     * Enter details for subscriptions
     *
     * @return object
     */
    public function _add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id, $is_superadmin = false)
    {
        if (! is_object($package)) {
            $package = Package::active()->find($package);
        }

        // Check for existing active/future subscriptions and handle package switching
        $current_subscriptions = Subscription::where('business_id', $business_id)
                                            ->whereIn('status', ['approved', 'waiting'])
                                            ->where('end_date', '>', now())
                                            ->get();

        if ($current_subscriptions->count() > 0) {
            // Get the currently active subscription (if any)
            $active_subscription = $current_subscriptions->where('start_date', '<=', now())->first();
            $latest_subscription = $current_subscriptions->sortByDesc('created_at')->first();
            
            // Check if switching to different package
            if ($latest_subscription->package_id != $package->id) {
                // Different package - expire ALL current/future subscriptions immediately
                foreach ($current_subscriptions as $sub) {
                    $sub->end_date = now()->subDay();
                    $sub->save();
                    \Log::info("Expired subscription ID {$sub->id} (Package {$sub->package_id}) due to package switch to {$package->id} for business {$business_id}");
                }
            } else {
                // Same package - check if it's a per-location package with different quantity
                $selected_location_quantity = session('selected_location_quantity');
                
                if ($package->is_per_location_pricing && 
                    $selected_location_quantity && 
                    $latest_subscription->location_quantity != $selected_location_quantity) {
                    
                    // Same package but different location quantity - expire all and create new
                    foreach ($current_subscriptions as $sub) {
                        $sub->end_date = now()->subDay();
                        $sub->save();
                        \Log::info("Expired subscription ID {$sub->id} due to location quantity change from {$sub->location_quantity} to {$selected_location_quantity} for business {$business_id}");
                    }
                } else {
                    // Same package and same quantity - allow stacking (normal renewal behavior)
                    \Log::info("Creating renewal subscription for same package {$package->id} for business {$business_id} - will stack after current subscription");
                    // Continue with normal subscription creation to stack after existing ones
                }
            }
        }

        $subscription = ['business_id' => $business_id,
            'package_id' => $package->id,
            'paid_via' => $gateway,
            'payment_transaction_id' => $payment_transaction_id,
        ];

        // Handle Stripe subscriptions differently
        if ($gateway === 'stripe') {
            $subscription['stripe_subscription_id'] = $payment_transaction_id;
            $subscription['stripe_customer_id'] = session('stripe_customer_id');
            $subscription['is_recurring'] = true;
            $subscription['auto_renewal'] = true;
            $subscription['status'] = 'waiting'; // Will be updated by webhook when subscription is active
            
            // For Stripe, dates will be set by webhook events
            $subscription['start_date'] = null;
            $subscription['end_date'] = null;
            $subscription['trial_end_date'] = null;
        } elseif ($package->price != 0 && (in_array($gateway, ['offline', 'pesapal']) && ! $is_superadmin)) {
            // Handle offline payment data (bank transfer receipt)
            $offline_payment_data = session('offline_payment_data');
            $has_receipt = $offline_payment_data && $gateway === 'offline' && !empty($offline_payment_data['receipt_file_path']);
            
            if ($has_receipt) {
                // NEW BEHAVIOR: If receipt is uploaded, start subscription immediately but keep in waiting status
                // This allows immediate access to features while waiting for admin approval
                $dates = $this->_get_package_dates($business_id, $package);
                $subscription['start_date'] = $dates['start'];
                $subscription['end_date'] = $dates['end'];
                $subscription['trial_end_date'] = $dates['trial'];
                $subscription['status'] = 'waiting'; // Started but waiting for approval
            } else {
                // No receipt uploaded, dates will be decided when approved by superadmin
                $subscription['start_date'] = null;
                $subscription['end_date'] = null;
                $subscription['trial_end_date'] = null;
                $subscription['status'] = 'waiting';
            }
            
            if ($offline_payment_data && $gateway === 'offline') {
                $subscription['receipt_file_path'] = $offline_payment_data['receipt_file_path'];
                $subscription['selected_bank'] = $offline_payment_data['selected_bank'];
                $subscription['selected_currency'] = $offline_payment_data['selected_currency'];
                
                // Clear session data after use
                session()->forget('offline_payment_data');
            }
        } else {
            $dates = $this->_get_package_dates($business_id, $package);

            $subscription['start_date'] = $dates['start'];
            $subscription['end_date'] = $dates['end'];
            $subscription['trial_end_date'] = $dates['trial'];
            $subscription['status'] = 'approved';
        }

        // Handle per-location pricing
        $selected_location_quantity = session('selected_location_quantity');
        $actual_price = $package->is_per_location_pricing && $selected_location_quantity 
                       ? $package->calculatePrice($selected_location_quantity) 
                       : $package->price;
        
        $subscription['package_price'] = $actual_price;
        
        // Store per-location details if applicable
        if ($package->is_per_location_pricing && $selected_location_quantity) {
            $subscription['location_quantity'] = $selected_location_quantity;
            $subscription['price_per_location'] = $package->price_per_location;
        }
        
        // Get USD to MVR exchange rate and calculate MVR amount
        $usd_to_mvr_rate = CurrencyUtil::getUsdToMvrRate();
        $mvr_amount = CurrencyUtil::convertUsdToMvr($actual_price, $usd_to_mvr_rate);
        
        $subscription['usd_to_mvr_rate'] = $usd_to_mvr_rate;
        $subscription['mvr_amount'] = $mvr_amount;
        
        $subscription['package_details'] = [
            'location_count' => $package->location_count,
            'user_count' => $package->user_count,
            'product_count' => $package->product_count,
            'invoice_count' => $package->invoice_count,
            'name' => $package->name,
        ];
        //Custom permissions.
        if (! empty($package->custom_permissions)) {
            foreach ($package->custom_permissions as $name => $value) {
                $subscription['package_details'][$name] = $value;
            }
        }

        $subscription['created_id'] = $user_id;
        $subscription = Subscription::create($subscription);

        if (! $is_superadmin) {
            $email = System::getProperty('email');
            $is_notif_enabled = System::getProperty('enable_new_subscription_notification');

            if (! empty($email) && $is_notif_enabled == 1 && (new Util())->IsMailConfigured()) {
                Notification::route('mail', $email)
                ->notify(new NewSubscriptionNotification($subscription));
            }
        }

        return $subscription;
    }

    /**
     * The function returns the start/end/trial end date for a package.
     *
     * @param  int  $business_id
     * @param  object  $package
     * @return array
     */
    protected function _get_package_dates($business_id, $package)
    {
        $output = ['start' => '', 'end' => '', 'trial' => ''];

        //calculate start date
        $start_date = Subscription::end_date($business_id);
        $output['start'] = $start_date->toDateString();

        //Calculate end date
        if ($package->interval == 'days') {
            $output['end'] = $start_date->addDays($package->interval_count)->toDateString();
        } elseif ($package->interval == 'months') {
            $output['end'] = $start_date->addMonths($package->interval_count)->toDateString();
        } elseif ($package->interval == 'years') {
            $output['end'] = $start_date->addYears($package->interval_count)->toDateString();
        }

        $output['trial'] = $start_date->addDays($package->trial_days);

        return $output;
    }
}
