<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class);
    }

    // --- Syarat dari Filament Tenancy ---
    
    public function getTenants(Panel $panel): Collection
    {
        return $this->businesses;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->businesses->contains($tenant);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->latestBusiness(); 
    }

    public function latestBusiness()
    {
        return $this->businesses()->first();
    }
}
