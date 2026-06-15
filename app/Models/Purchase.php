<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'contact_id',
    'warehouse_id',
    'store_id',
    'number',
    'supplier_invoice_no',
    'date',
    'due_date',
    'status',
    'subtotal',
    'tax_total',
    'grand_total',
    'paid_amount',
    'outstanding_amount',
    'notes',
    'created_by',
    'posted_at',
])]
class Purchase extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function paymentStatus(): string
    {
        if ((float) $this->outstanding_amount <= 0) {
            return 'lunas';
        }

        return (float) $this->paid_amount > 0 ? 'sebagian' : 'belum_lunas';
    }
}
