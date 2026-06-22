<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'company_id',
    'number',
    'date',
    'type',
    'reference',
    'description',
    'status',
    'source_type',
    'source_id',
    'total_debit',
    'total_credit',
    'created_by',
    'posted_at',
])]
class Journal extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    /**
     * Jurnal manual (jurnal umum yang dibuat user), bukan hasil posting otomatis
     * dari dokumen lain (penjualan, pembelian, dll). Hanya jurnal manual yang
     * boleh diedit/dihapus langsung.
     */
    public function isManual(): bool
    {
        return $this->source_id === null && $this->type === 'general';
    }
}
