<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'store_id',
    'user_id',
    'number',
    'opened_at',
    'closed_at',
    'opening_cash',
    'cash_sales_total',
    'expected_cash',
    'closing_cash',
    'cash_difference',
    'status',
    'notes',
])]
class CashierSession extends Model
{
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'cash_sales_total' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
