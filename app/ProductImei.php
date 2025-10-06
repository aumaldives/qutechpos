<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductImei extends Model
{
    use HasFactory, SoftDeletes;
    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'imei',
        'is_sold',
        'sale_date',
        'invoice_number',
        'transaction_id',
        'purchase_line_id',
        'location_id',
        'sell_line_id',
        'business_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseLine()
    {
        return $this->belongsTo(\App\PurchaseLine::class, 'purchase_line_id');
    }

    public function sellLine()
    {
        return $this->belongsTo(\App\TransactionSellLine::class, 'sell_line_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    /**
     * Get available IMEIs for a product at a location
     */
    public static function getAvailableImeis($product_id, $location_id, $business_id)
    {
        return static::where('product_id', $product_id)
            ->where('location_id', $location_id)
            ->where('business_id', $business_id)
            ->where('is_sold', 0)
            ->whereNotNull('imei')
            ->where('imei', '!=', '')
            ->pluck('imei', 'id')
            ->toArray();
    }

}
