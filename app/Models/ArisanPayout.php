<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'arisan_period_id',
    'employee_id',
    'amount',
    'payout_date',
    'notes',
])]
class ArisanPayout extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payout_date' => 'date',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(ArisanPeriod::class, 'arisan_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
