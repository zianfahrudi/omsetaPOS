<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'date',
    'type',
    'amount',
    'note',
    'payroll_id',
])]
class EmployeeSavingEntry extends Model
{
    public const TYPES = ['deposit', 'withdraw'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Nilai bertanda untuk saldo: deposit positif, withdraw negatif.
     */
    public function signedAmount(): float
    {
        return $this->type === 'withdraw' ? -(float) $this->amount : (float) $this->amount;
    }
}
