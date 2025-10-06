<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlasticBagPurchaseLine extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price_per_bag' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function purchase()
    {
        return $this->belongsTo('App\PlasticBagPurchase', 'plastic_bag_purchase_id');
    }

    public function plasticBagType()
    {
        return $this->belongsTo('App\PlasticBagType');
    }
}
