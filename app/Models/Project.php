<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'contact_id',
    'name',
    'code',
    'budget',
    'contract_value',
    'down_payment',
    'start_date',
    'end_date',
    'status',
    'is_active',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'contract_value' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function costs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectCost::class);
    }

    public function costByType(string $type): float
    {
        return (float) $this->costs->where('type', $type)->sum('amount');
    }

    public function totalCost(): float
    {
        return round((float) $this->costs->sum('amount'), 2);
    }

    public function remainingBill(): float
    {
        return round((float) $this->contract_value - (float) $this->down_payment, 2);
    }

    public function tentativeProfit(): float
    {
        return round((float) $this->contract_value - $this->totalCost(), 2);
    }
}
