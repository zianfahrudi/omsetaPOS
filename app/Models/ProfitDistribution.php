<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'number',
    'date',
    'period_from',
    'period_to',
    'net_income',
    'base_amount',
    'notes',
    'created_by',
])]
class ProfitDistribution extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'net_income' => 'decimal:2',
            'base_amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProfitDistributionShare::class)->orderBy('sort_order')->orderBy('id');
    }
}
