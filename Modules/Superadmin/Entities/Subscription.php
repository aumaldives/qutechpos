<?php

namespace Modules\Superadmin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Subscription extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trial_end_date' => 'datetime',
        'package_details' => 'array',
        'is_recurring' => 'boolean',
        'auto_renewal' => 'boolean',
    ];

    /**
     * The attributes that should be fillable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'package_id',
        'start_date',
        'trial_end_date',
        'end_date',
        'package_price',
        'package_details',
        'created_id',
        'paid_via',
        'payment_transaction_id',
        'receipt_file_path',
        'selected_bank',
        'selected_currency',
        'stripe_subscription_id',
        'stripe_customer_id',
        'is_recurring',
        'auto_renewal',
        'status',
        'usd_to_mvr_rate',
        'mvr_amount',
        'location_quantity',
        'price_per_location'
    ];

    /**
     * Scope a query to only include approved subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'waiting']);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Get the package that belongs to the subscription.
     */
    public function package()
    {
        return $this->belongsTo('\Modules\Superadmin\Entities\Package')
            ->withTrashed();
    }

    /**
     * Returns the active subscription details for a business
     *
     * @param $business_id int
     * @return Response
     */
    public static function active_subscription($business_id)
    {
        $date_today = \Carbon::today()->toDateString();

        $subscription = Subscription::where('business_id', $business_id)
                            ->whereDate('start_date', '<=', $date_today)
                            ->whereDate('end_date', '>=', $date_today)
                            ->active() // Allow waiting subscriptions that have started
                            ->orderBy('status', 'desc') // Prefer approved over waiting
                            ->first();

        return $subscription;
    }

    /**
     * Returns the upcoming subscription details for a business
     *
     * @param $business_id int
     * @return Response
     */
    public static function upcoming_subscriptions($business_id)
    {
        $date_today = \Carbon::today();

        $subscription = Subscription::where('business_id', $business_id)
                            ->whereDate('start_date', '>', $date_today)
                            ->approved()
                            ->get();

        return $subscription;
    }

    /**
     * Returns the subscriptions waiting for approval for superadmin
     *
     * @param $business_id int
     * @return Response
     */
    public static function waiting_approval($business_id)
    {
        $subscriptions = Subscription::where('business_id', $business_id)
                            ->whereNull('start_date')
                            ->waiting()
                            ->get();

        return $subscriptions;
    }

    public static function end_date($business_id)
    {
        $date_today = \Carbon::today();

        $subscription = Subscription::where('business_id', $business_id)
                            ->approved()
                            ->select(DB::raw('MAX(end_date) as end_date'))
                            ->first();

        if (empty($subscription->end_date)) {
            return $date_today;
        } else {
            $end_date = $subscription->end_date->addDay();
            if ($date_today->lte($end_date)) {
                return $end_date;
            } else {
                return $date_today;
            }
        }
    }

    /**
     * Returns the list of packages status
     *
     * @return array
     */
    public static function package_subscription_status()
    {
        return ['approved' => trans('superadmin::lang.approved'), 'declined' => trans('superadmin::lang.declined'), 'waiting' => trans('superadmin::lang.waiting')];
    }

    /**
     * Get the created_by.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_id');
    }

    /**
     * Get the subscription business relationship.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get effective location count for this subscription
     * For per-location subscriptions, use the purchased quantity
     * For fixed packages, use the package location_count
     *
     * @return int
     */
    public function getEffectiveLocationCount()
    {
        if (!empty($this->location_quantity)) {
            return $this->location_quantity;
        }

        // Fallback to package details or package model
        if (isset($this->package_details['location_count'])) {
            return $this->package_details['location_count'];
        }

        return $this->package ? $this->package->location_count : 0;
    }

    /**
     * Check if this subscription allows unlimited locations
     *
     * @return bool
     */
    public function allowsUnlimitedLocations()
    {
        return $this->getEffectiveLocationCount() == 0;
    }
}
