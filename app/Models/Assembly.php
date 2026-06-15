<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'product_id',
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

    public function components(): HasMany
    {
        return $this->hasMany(AssemblyComponent::class);
    }
}
