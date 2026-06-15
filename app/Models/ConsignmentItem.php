<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consignment_id',
    'product_id',
    'product_name',
    'quantity',
    'sold_quantity',
    'returned_quantity',
    'unit_price',
    'unit_cost',
])]
class ConsignmentItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sold_quantity' => 'integer',
            'returned_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function remaining(): int
    {
        return (int) $this->quantity - (int) $this->sold_quantity - (int) $this->returned_quantity;
    }
}
