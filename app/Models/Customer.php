<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'name',
    'phone',
    'email',
    'address',
    'visit_count',
    'total_spent',
    'outstanding_debt',
    'debt_total',
    'last_purchase_at',
    'last_debt_at',
    'notes',
])]
class Customer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_spent' => 'decimal:2',
            'outstanding_debt' => 'decimal:2',
            'debt_total' => 'decimal:2',
            'last_purchase_at' => 'datetime',
            'last_debt_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
