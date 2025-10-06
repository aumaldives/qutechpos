<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlasticBagPurchase extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:4',
    ];

    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    public function supplier()
    {
        return $this->belongsTo('App\Contact', 'supplier_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function purchaseLines()
    {
        return $this->hasMany('App\PlasticBagPurchaseLine');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
