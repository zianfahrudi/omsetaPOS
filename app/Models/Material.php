<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'category',
    'name',
    'unit',
    'price',
    'stock',
    'min_stock',
    'is_active',
])]
class Material extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MaterialMovement::class);
    }

    public function stockValue(): float
    {
        return round((float) $this->stock * (float) $this->price, 2);
    }
}
