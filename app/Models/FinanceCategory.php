<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'is_system',
        'is_active',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    // Relasi Induk Kategori
    public function parent()
    {
        return $this->belongsTo(FinanceCategory::class, 'parent_id');
    }

    // Relasi Anak Kategori (Sub-kategori yang dibuat user)
    public function children()
    {
        return $this->hasMany(FinanceCategory::class, 'parent_id');
    }

    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }
}