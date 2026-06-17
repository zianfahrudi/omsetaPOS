<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'employee_id',
    'period_start',
    'period_end',
    'total_hours',
    'gross_salary',
    'total_bonus',
    'total_loan',
    'total_arisan',
    'total_savings',
    'take_home_pay',
    'status',
])]
class Payroll extends Model
{
    public const STATUSES = ['draft', 'approved', 'paid'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_hours' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'total_loan' => 'decimal:2',
            'total_arisan' => 'decimal:2',
            'total_savings' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
