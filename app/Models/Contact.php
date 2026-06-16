<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'code',
    'name',
    'type',
    'phone',
    'email',
    'tax_number',
    'address',
    'receivable_balance',
    'payable_balance',
    'is_active',
    'notes',
])]
class Contact extends Model
{
    public const TYPES = ['customer', 'supplier', 'employee', 'other'];

    protected function casts(): array
    {
        return [
            'receivable_balance' => 'decimal:2',
            'payable_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeSuppliers($query)
    {
        return $query->where('type', 'supplier');
    }

    public function scopeCustomers($query)
    {
        return $query->where('type', 'customer');
    }

    public function scopeEmployees($query)
    {
        return $query->where('type', 'employee');
    }
}
