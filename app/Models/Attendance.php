<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'shift_id',
    'work_date',
    'check_in',
    'check_out',
    'total_minutes',
    'total_hours',
    'paid_hours',
    'status',
])]
class Attendance extends Model
{
    public const STATUSES = ['present', 'late', 'absent', 'leave', 'sick', 'holiday'];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'total_minutes' => 'integer',
            'total_hours' => 'decimal:2',
            'paid_hours' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Jam dibayar: pakai paid_hours bila sudah di-review, jika tidak pakai total_hours.
     */
    public function payableHours(): float
    {
        return (float) ($this->paid_hours ?? $this->total_hours);
    }

    public function recalculate(): void
    {
        if ($this->check_in && $this->check_out) {
            $minutes = (int) abs($this->check_in->diffInMinutes($this->check_out));
            $this->total_minutes = $minutes;
            $this->total_hours = round($minutes / 60, 2);
        } else {
            $this->total_minutes = 0;
            $this->total_hours = 0;
        }
    }
}
