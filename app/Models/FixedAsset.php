<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'asset_account_id',
    'accumulated_account_id',
    'expense_account_id',
    'code',
    'name',
    'acquisition_date',
    'acquisition_cost',
    'salvage_value',
    'useful_life_months',
    'accumulated_depreciation',
    'status',
    'last_depreciated_at',
    'notes',
    'created_by',
])]
class FixedAsset extends Model
{
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'last_depreciated_at' => 'date',
            'acquisition_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bookValue(): float
    {
        return round((float) $this->acquisition_cost - (float) $this->accumulated_depreciation, 2);
    }

    public function monthlyDepreciation(): float
    {
        if ($this->useful_life_months <= 0) {
            return 0.0;
        }

        return round(((float) $this->acquisition_cost - (float) $this->salvage_value) / $this->useful_life_months, 2);
    }

    public function remainingDepreciable(): float
    {
        return round(((float) $this->acquisition_cost - (float) $this->salvage_value) - (float) $this->accumulated_depreciation, 2);
    }
}
