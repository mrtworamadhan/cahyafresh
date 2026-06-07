<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    protected $fillable = ['business_id', 'supplier_id', 'invoice_number', 'purchase_date', 'total_amount', 'status', 'is_stock_received'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'reference');
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->ledgers()->where('type', 'out')->sum('amount');
    }


    public function getRemainingBalanceAttribute(): float
    {
        return (float) $this->total_amount - $this->paid_amount;
    }
}
