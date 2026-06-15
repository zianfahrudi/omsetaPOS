<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'contact_id',
    'number',
    'date',
    'status',
    'total_cost',
    'total_sold',
    'notes',
    'created_by',
])]
class Consignment extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_cost' => 'decimal:2',
            'total_sold' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consignee(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentItem::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
