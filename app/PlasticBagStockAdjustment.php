<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlasticBagStockAdjustment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'adjustment_date' => 'date',
        'quantity' => 'decimal:4',
    ];

    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    public function plasticBagType()
    {
        return $this->belongsTo('App\PlasticBagType');
    }

    public function location()
    {
        return $this->belongsTo('App\BusinessLocation', 'location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
