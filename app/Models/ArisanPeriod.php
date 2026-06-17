<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'arisan_group_id',
    'period_no',
    'period_date',
    'total_collected',
    'winner_employee_id',
    'status',
])]
class ArisanPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'period_no' => 'integer',
            'period_date' => 'date',
            'total_collected' => 'decimal:2',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ArisanGroup::class, 'arisan_group_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'winner_employee_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(ArisanContribution::class);
    }

    public function payout(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ArisanPayout::class);
    }

    public function allCollected(): bool
    {
        return $this->contributions()->where('status', '!=', 'paid')->where('status', '!=', 'cancelled')->count() === 0
            && $this->contributions()->count() > 0;
    }
}
