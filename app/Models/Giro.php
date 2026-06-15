<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'contact_id',
    'sales_invoice_id',
    'cleared_bank_account_id',
    'number',
    'giro_number',
    'bank_name',
    'date',
    'due_date',
    'amount',
    'status',
    'created_by',
])]
class Giro extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['received', 'deposited'], true);
    }
}
