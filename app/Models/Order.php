<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $fillable = [
        'business_id', 
        'customer_id', 
        'po_batch_id', 
        'order_number', 
        'order_date', 
        'delivery_date',
        'total_amount',
        'discount_amount',
        'commission_amount', 
        'status', 
        'payment_status', 
        'delivery_type', 
        'shipping_fee_billed', 
        'shipping_cost_actual',
        'notes',
        'commission_recipient_id',
        'commission_note'
        ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function poBatch()
    {
        return $this->belongsTo(PoBatch::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function ledgers(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'reference');
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->ledgers()->where('type', 'in')->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->total_amount - $this->paid_amount;
    }
}
