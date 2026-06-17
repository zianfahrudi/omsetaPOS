<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'amount',
    'outstanding',
    'date',
    'description',
    'status',
])]
class EmployeeLoan extends Model
{
    public const STATUSES = ['pending', 'paid', 'deducted'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'outstanding' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(EmployeeLoanRepayment::class);
    }

    public function repaidTotal(): float
    {
        return round((float) $this->amount - (float) $this->outstanding, 2);
    }

    /**
     * Sinkronkan sisa utang dari total cicilan & set status.
     */
    public function recalcOutstanding(): void
    {
        $repaid = (float) $this->repayments()->sum('amount');
        $this->outstanding = max(0, round((float) $this->amount - $repaid, 2));
        $this->status = $this->outstanding <= 0 ? 'paid' : 'pending';
        $this->save();
    }
}
