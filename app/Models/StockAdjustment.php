<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'product_id', 'type', 'quantity', 'reason'];

    // Relasi Tenancy
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    // Relasi ke Produk
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}