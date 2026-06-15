<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'store_id',
    'warehouse_id',
    'product_id',
    'number',
    'date',
    'reason',
    'quantity_before',
    'quantity_after',
    'difference',
    'unit_cost',
    'value',
    'notes',
    'created_by',
])]
class StockAdjustment extends Model
{
    public const REASONS = ['opname', 'damaged', 'lost', 'expired', 'correction'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'unit_cost' => 'decimal:2',
            'value' => 'decimal:2',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
