<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'code',
    'currency',
    'default_overhead_percent',
    'default_profit_percent',
    'phone',
    'email',
    'address',
    'book_opened_at',
    'is_active',
])]
class Company extends Model
{
    protected function casts(): array
    {
        return [
            'default_overhead_percent' => 'decimal:2',
            'default_profit_percent' => 'decimal:2',
            'book_opened_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return $this->warehouses()->where('is_default', true)->first()
            ?? $this->warehouses()->orderBy('id')->first();
    }

    /**
     * Resolve a system account by its subtype (e.g. 'cash', 'sales', 'cogs').
     */
    public function account(string $subtype): ?Account
    {
        return $this->accounts()
            ->where('subtype', $subtype)
            ->where('is_active', true)
            ->first();
    }
}
