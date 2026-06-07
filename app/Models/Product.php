<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'sku', 'name', 'description', 
        'base_unit', 'base_price', 'selling_price', 'stock'
    ];

    // Relasi Tenancy (Wajib)
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Relasi ke Satuan Alternatif
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }
}
