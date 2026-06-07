<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'logo',
        'theme_color',
        'invoice_template',
        'invoice_color',
        'invoice_footer_text',
        'is_tax_enabled',
        'signature',
        'signer_name',
        'signer_title',
        'tax_rate',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}