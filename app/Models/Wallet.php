<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = ['business_id', 'name', 'balance', 'is_active', 'type', 'account_number'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }
}