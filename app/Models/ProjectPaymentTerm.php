<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'sort_order',
    'name',
    'amount',
    'due_date',
    'is_paid',
    'paid_date',
    'note',
])]
class ProjectPaymentTerm extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'is_paid' => 'boolean',
            'paid_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
