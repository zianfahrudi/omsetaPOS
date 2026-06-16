<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'contact_id',
    'name',
    'location',
    'province_id',
    'regency_id',
    'district_id',
    'code',
    'budget',
    'contract_value',
    'overhead_percent',
    'profit_percent',
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
            'overhead_percent' => 'decimal:2',
            'profit_percent' => 'decimal:2',
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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
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

    /**
     * Subtotal bahan/biaya = dasar perhitungan penawaran (RAB).
     */
    public function penawaranSubtotal(): float
    {
        return $this->totalCost();
    }

    public function overheadAmount(): float
    {
        return round($this->penawaranSubtotal() * (float) $this->overhead_percent / 100, 2);
    }

    public function profitAmount(): float
    {
        return round($this->penawaranSubtotal() * (float) $this->profit_percent / 100, 2);
    }

    /**
     * Total Penawaran = subtotal + overhead + profit.
     */
    public function totalPenawaran(): float
    {
        return round($this->penawaranSubtotal() + $this->overheadAmount() + $this->profitAmount(), 2);
    }

    /**
     * Nilai kontrak efektif: pakai nilai kontrak bila diisi, jika tidak pakai total penawaran.
     */
    public function effectiveContractValue(): float
    {
        return (float) $this->contract_value > 0 ? (float) $this->contract_value : $this->totalPenawaran();
    }

    public function remainingBill(): float
    {
        if ($this->status === 'paid') {
            return 0.0;
        }

        return round($this->effectiveContractValue() - (float) $this->down_payment, 2);
    }

    public function tentativeProfit(): float
    {
        return round((float) $this->contract_value - $this->totalCost(), 2);
    }
}
