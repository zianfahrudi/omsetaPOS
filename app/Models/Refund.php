<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'sale_id',
    'handled_by_id',
    'number',
    'type',
    'status',
    'reason',
    'evidence_photos',
    'returned_total',
    'replacement_total',
    'refund_amount',
    'additional_payment_amount',
])]
class Refund extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'returned_total' => 'decimal:2',
            'replacement_total' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'additional_payment_amount' => 'decimal:2',
            'evidence_photos' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }
}
