<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'arisan_group_id',
    'employee_id',
    'join_date',
    'sequence_number',
    'has_won',
    'status',
])]
class ArisanMember extends Model
{
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'sequence_number' => 'integer',
            'has_won' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ArisanGroup::class, 'arisan_group_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
