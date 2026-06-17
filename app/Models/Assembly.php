<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'product_id',
    'product_name',
    'number',
    'date',
    'quantity',
    'total_cost',
    'notes',
    'created_by',
])]
class Assembly extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quantity' => 'integer',
            'total_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Nama produk jadi: dari master produk bila ada, jika tidak pakai isian manual.
     */
    public function finishedName(): string
    {
        return $this->product?->name ?: ($this->product_name ?: '—');
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssemblyComponent::class);
    }
}
