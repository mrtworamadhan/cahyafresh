<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'qty_billed',
        'qty_bonus',
        'unit_price',
        'base_price',
        'product_unit_id',
        'commission_per_unit',
        'subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
