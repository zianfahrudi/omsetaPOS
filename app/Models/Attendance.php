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
    'hourly_rate',
    'status',
    'source',
    'check_in_location_id',
    'check_in_latitude',
    'check_in_longitude',
    'check_in_accuracy',
    'check_in_distance',
    'check_in_is_mock',
    'check_out_location_id',
    'check_out_latitude',
    'check_out_longitude',
    'check_out_accuracy',
    'check_out_distance',
    'check_out_is_mock',
    'device_id',
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
            'hourly_rate' => 'decimal:2',
            'check_in_latitude' => 'decimal:7',
            'check_in_longitude' => 'decimal:7',
            'check_in_accuracy' => 'decimal:2',
            'check_in_distance' => 'decimal:2',
            'check_in_is_mock' => 'boolean',
            'check_out_latitude' => 'decimal:7',
            'check_out_longitude' => 'decimal:7',
            'check_out_accuracy' => 'decimal:2',
            'check_out_distance' => 'decimal:2',
            'check_out_is_mock' => 'boolean',
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

    public function checkInLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_in_location_id');
    }

    public function checkOutLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_out_location_id');
    }

    /**
     * Jam dibayar: pakai paid_hours bila sudah di-review, jika tidak pakai total_hours.
     */
    public function payableHours(): float
    {
        return (float) ($this->paid_hours ?? $this->total_hours);
    }

    /**
     * Nominal gaji baris ini = jam dibayar × tarif snapshot.
     * Pakai tarif snapshot bila ada, jika tidak fallback ke tarif karyawan saat ini.
     */
    public function payableAmount(float $fallbackRate = 0.0): float
    {
        $rate = $this->hourly_rate !== null ? (float) $this->hourly_rate : $fallbackRate;

        return $this->payableHours() * $rate;
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
