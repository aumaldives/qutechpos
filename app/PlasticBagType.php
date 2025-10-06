<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlasticBagType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:4',
        'stock_quantity' => 'decimal:4',
        'alert_quantity' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the business that owns the plastic bag type.
     */
    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    /**
     * Get the purchases for this plastic bag type.
     */
    public function purchases()
    {
        return $this->hasMany('App\PlasticBagPurchaseLine');
    }

    /**
     * Get the stock adjustments for this plastic bag type.
     */
    public function stockAdjustments()
    {
        return $this->hasMany('App\PlasticBagStockAdjustment');
    }

    /**
     * Get the stock transfers for this plastic bag type.
     */
    public function stockTransfers()
    {
        return $this->hasMany('App\PlasticBagStockTransfer');
    }

    /**
     * Get the usage records for this plastic bag type.
     */
    public function usage()
    {
        return $this->hasMany('App\PlasticBagUsage');
    }

    /**
     * Scope a query to only include active plastic bag types.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope a query to filter by business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
