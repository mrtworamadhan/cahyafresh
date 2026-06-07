<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'slug',
        'phone',
        'address',
        'referral_code',
        'referred_by_id',
        'commission_balance',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->slug)) {
                $customer->slug = strtoupper(Str::random(8));
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_by_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Customer::class, 'referred_by_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ledgers(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'contact');
    }
}