<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'period_name',
        'closing_date',
        'snapshot_data',
    ];

    // Paksa agar kolom JSON di-convert jadi Array di PHP
    protected $casts = [
        'snapshot_data' => 'array',
        'closing_date' => 'date',
    ];
}