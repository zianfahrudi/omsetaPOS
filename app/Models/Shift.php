<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'name',
    'start_time',
    'end_time',
    'duration_hours',
    'is_active',
])]
class Shift extends Model
{
    protected function casts(): array
    {
        return [
            'duration_hours' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Hitung durasi (jam) dari start_time & end_time (format HH:MM).
     */
    public static function calcDuration(string $start, string $end): float
    {
        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));
        $minutes = ($eh * 60 + $em) - ($sh * 60 + $sm);
        if ($minutes < 0) {
            $minutes += 24 * 60; // lewat tengah malam
        }

        return round($minutes / 60, 2);
    }
}
