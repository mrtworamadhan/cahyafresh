<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoBatch extends Model
{
    use HasFactory;

    // Mendaftarkan kolom apa saja yang diizinkan untuk diisi/disimpan
    protected $fillable = [
        'business_id',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * Relasi ke tabel Bisnis (Sistem Tenancy)
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relasi ke tabel Pesanan (Satu PO bisa punya banyak pesanan)
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}