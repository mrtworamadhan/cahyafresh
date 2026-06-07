<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ledger extends Model
{
    protected $fillable = [
        'business_id',
        'wallet_id',
        'finance_category_id',
        'transaction_date',
        'description',
        'type',
        'amount',
        'contact_type',
        'contact_id',
        'reference_type',
        'reference_id'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function contact(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function financeCategory()
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }
}