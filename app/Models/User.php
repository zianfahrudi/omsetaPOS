<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot(['role', 'is_default'])
            ->withTimestamps();
    }

    public function ownedStores(): HasMany
    {
        return $this->hasMany(Store::class, 'owner_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function handledRefunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'handled_by_id');
    }

    public function isSuperuser(): bool
    {
        return $this->role === 'superuser';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superuser'], true);
    }

    /**
     * Stores this user may operate (superuser sees all active stores).
     *
     * @return Collection<int, Store>
     */
    public function accessibleStores(): Collection
    {
        if ($this->isSuperuser()) {
            return Store::query()->where('is_active', true)->orderBy('name')->get();
        }

        return $this->stores()->where('stores.is_active', true)->orderBy('name')->get();
    }

    public function canAccessStore(int $storeId): bool
    {
        if ($this->isSuperuser()) {
            return Store::query()->whereKey($storeId)->where('is_active', true)->exists();
        }

        return $this->stores()
            ->where('stores.id', $storeId)
            ->where('stores.is_active', true)
            ->exists();
    }
}
