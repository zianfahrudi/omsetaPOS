<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'material_id',
    'date',
    'type',
    'quantity',
    'stock_before',
    'stock_after',
    'unit_cost',
    'reference_type',
    'reference_id',
    'note',
    'created_by',
])]
class MaterialMovement extends Model
{
    public const TYPES = ['purchase_in', 'assembly_out', 'adjustment'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'quantity' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
