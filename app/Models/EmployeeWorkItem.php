<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'date',
    'description',
    'qty',
    'unit_price',
    'amount',
])]
class EmployeeWorkItem extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
