<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'parent_id',
    'code',
    'name',
    'type',
    'subtype',
    'normal_balance',
    'is_postable',
    'is_system',
    'is_active',
    'opening_balance',
    'description',
])]
class Account extends Model
{
    public const TYPES = ['asset', 'liability', 'equity', 'revenue', 'expense'];

    /** Account types whose normal balance is on the debit side. */
    public const DEBIT_TYPES = ['asset', 'expense'];

    protected function casts(): array
    {
        return [
            'is_postable' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'opening_balance' => 'decimal:2',
        ];
    }

    public static function normalBalanceFor(string $type): string
    {
        return in_array($type, self::DEBIT_TYPES, true) ? 'debit' : 'credit';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function isDebitNormal(): bool
    {
        return $this->normal_balance === 'debit';
    }
}
