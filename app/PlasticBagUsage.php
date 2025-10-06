<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlasticBagUsage extends Model
{
    use HasFactory;

    protected $table = 'plastic_bag_usage';

    protected $fillable = [
        'business_id',
        'transaction_id',
        'plastic_bag_type_id',
        'location_id',
        'quantity',
        'selling_price',
        'usage_date',
        'usage_type',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'usage_date' => 'date'
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function plasticBagType()
    {
        return $this->belongsTo(PlasticBagType::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('usage_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [$startDate, $endDate]);
    }
}
