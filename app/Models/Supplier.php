<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Supplier extends Model
{
    protected $fillable = ['business_id', 'name', 'phone', 'address'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'contact');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
