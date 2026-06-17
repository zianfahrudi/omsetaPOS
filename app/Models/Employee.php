<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'code',
    'name',
    'phone',
    'position',
    'hourly_rate',
    'earning_type',
    'join_date',
    'is_active',
])]
class Employee extends Model
{
    public const EARNING_TYPES = ['hourly', 'piecework'];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'join_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function loanRepayments(): HasMany
    {
        return $this->hasMany(EmployeeLoanRepayment::class);
    }

    public function outstandingLoanTotal(): float
    {
        return (float) $this->loans->sum(fn (EmployeeLoan $l) => (float) $l->outstanding);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(EmployeeWorkItem::class);
    }

    public function isPiecework(): bool
    {
        return $this->earning_type === 'piecework';
    }

    public function arisan(): HasMany
    {
        return $this->hasMany(EmployeeArisan::class);
    }

    public function arisanMemberships(): HasMany
    {
        return $this->hasMany(ArisanMember::class);
    }

    public function savings(): HasMany
    {
        return $this->hasMany(EmployeeSaving::class);
    }

    public function savingEntries(): HasMany
    {
        return $this->hasMany(EmployeeSavingEntry::class);
    }

    public function savingBalance(): float
    {
        return (float) $this->savingEntries
            ->reduce(fn (float $carry, EmployeeSavingEntry $e) => $carry + $e->signedAmount(), 0.0);
    }
}
