<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sale_id',
    'account_id',
    'method',
    'amount',
    'proof',
    'is_settlement',
    'paid_at',
])]
class SalePayment extends Model
{
    public const METHODS = ['cash', 'qris', 'transfer'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_settlement' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Subtipe akun untuk posting jurnal: cash → kas, selain itu → bank.
     */
    public function accountSubtype(): string
    {
        return $this->method === 'cash' ? 'cash' : 'bank';
    }
}
