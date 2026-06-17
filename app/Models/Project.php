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
    'tax_percent',
    'rounding_unit',
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
            'tax_percent' => 'decimal:2',
            'rounding_unit' => 'decimal:2',
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
        return $this->hasMany(ProjectCost::class)->orderBy('sort_order')->orderBy('id');
    }

    public function expenses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectExpense::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function paymentTerms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectPaymentTerm::class)->orderBy('sort_order')->orderBy('id');
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
     * Dasar pengenaan PPN = subtotal + overhead + profit.
     */
    public function taxBase(): float
    {
        return round($this->penawaranSubtotal() + $this->overheadAmount() + $this->profitAmount(), 2);
    }

    public function taxAmount(): float
    {
        return round($this->taxBase() * (float) $this->tax_percent / 100, 2);
    }

    /**
     * Total sebelum pembulatan = subtotal + overhead + profit + PPN.
     */
    public function totalBeforeRounding(): float
    {
        return round($this->taxBase() + $this->taxAmount(), 2);
    }

    public function roundingAmount(): float
    {
        $unit = (float) $this->rounding_unit;
        if ($unit <= 0) {
            return 0.0;
        }

        return round(ceil($this->totalBeforeRounding() / $unit) * $unit - $this->totalBeforeRounding(), 2);
    }

    /**
     * Total Penawaran = subtotal + overhead + profit + PPN + pembulatan.
     */
    public function totalPenawaran(): float
    {
        return round($this->totalBeforeRounding() + $this->roundingAmount(), 2);
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

        // Bila ada termin, sisa = nilai kontrak − total termin dibayar.
        if ($this->paymentTerms->isNotEmpty()) {
            return round($this->effectiveContractValue() - $this->totalPaidTerms(), 2);
        }

        return round($this->effectiveContractValue() - (float) $this->down_payment, 2);
    }

    // ---- Realisasi biaya (anggaran RAB vs aktual) ----

    /**
     * Biaya aktual yang sudah dikeluarkan (realisasi).
     */
    public function actualCostTotal(): float
    {
        return round((float) $this->expenses->sum('amount'), 2);
    }

    public function actualByCategory(string $category): float
    {
        return (float) $this->expenses->where('category', $category)->sum('amount');
    }

    /**
     * Laba kotor estimasi (berbasis RAB): nilai kontrak − total RAB.
     */
    public function estimatedGrossProfit(): float
    {
        return round($this->effectiveContractValue() - $this->totalCost(), 2);
    }

    /**
     * Laba kotor aktual: nilai kontrak − realisasi biaya.
     */
    public function actualGrossProfit(): float
    {
        return round($this->effectiveContractValue() - $this->actualCostTotal(), 2);
    }

    // ---- Termin pembayaran ----

    public function totalTerms(): float
    {
        return round((float) $this->paymentTerms->sum('amount'), 2);
    }

    public function totalPaidTerms(): float
    {
        return round((float) $this->paymentTerms->where('is_paid', true)->sum('amount'), 2);
    }

    public function tentativeProfit(): float
    {
        return round((float) $this->contract_value - $this->totalCost(), 2);
    }
}
