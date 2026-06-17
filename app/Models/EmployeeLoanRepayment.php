<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_loan_id',
    'employee_id',
    'date',
    'amount',
    'note',
    'payroll_id',
])]
class EmployeeLoanRepayment extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EmployeeLoan::class, 'employee_loan_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
