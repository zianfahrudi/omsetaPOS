<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'name',
    'contribution_amount',
    'start_date',
    'end_date',
    'total_members',
    'draw_method',
    'status',
    'notes',
])]
class ArisanGroup extends Model
{
    protected function casts(): array
    {
        return [
            'contribution_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'total_members' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ArisanMember::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(ArisanPeriod::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function totalCollected(): float
    {
        return (float) $this->periods()->sum('total_collected');
    }

    public function currentPeriod(): ?ArisanPeriod
    {
        return $this->periods()->where('status', 'pending')->orderByDesc('period_no')->first();
    }
}
