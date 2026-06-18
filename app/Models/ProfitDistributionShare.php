<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profit_distribution_id',
    'sort_order',
    'name',
    'percent',
    'amount',
])]
class ProfitDistributionShare extends Model
{
    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(ProfitDistribution::class, 'profit_distribution_id');
    }
}
