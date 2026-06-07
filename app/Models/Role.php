<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends SpatieRole
{
    // Ini fungsi ajaib yang dicari sama Filament tadi!
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}