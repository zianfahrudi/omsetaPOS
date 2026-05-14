<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'tax_percentage',
    'service_fee_percentage',
    'is_tax_active',
    'is_service_fee_active',
])]
class StoreCharge extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tax_percentage' => 'decimal:2',
            'service_fee_percentage' => 'decimal:2',
            'is_tax_active' => 'boolean',
            'is_service_fee_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
