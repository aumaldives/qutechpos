<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlasticBagStockTransfer extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'transfer_date' => 'date',
        'quantity' => 'decimal:4',
        'received_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo('App\Business');
    }

    public function plasticBagType()
    {
        return $this->belongsTo('App\PlasticBagType');
    }

    public function fromLocation()
    {
        return $this->belongsTo('App\BusinessLocation', 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo('App\BusinessLocation', 'to_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo('App\User', 'received_by');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
