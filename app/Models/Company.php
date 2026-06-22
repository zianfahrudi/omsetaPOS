<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'name',
    'code',
    'currency',
    'default_overhead_percent',
    'default_profit_percent',
    'invoice_prefix',
    'invoice_due_days',
    'invoice_bank_name',
    'invoice_bank_account',
    'invoice_bank_holder',
    'invoice_signature_name',
    'invoice_note',
    'phone',
    'email',
    'address',
    'book_opened_at',
    'is_active',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_overhead_percent' => 'decimal:2',
            'default_profit_percent' => 'decimal:2',
            'invoice_due_days' => 'integer',
            'book_opened_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return $this->warehouses()->where('is_default', true)->first()
            ?? $this->warehouses()->orderBy('id')->first();
    }

    /**
     * Resolve a system account by its subtype (e.g. 'cash', 'sales', 'cogs').
     */
    public function account(string $subtype): ?Account
    {
        return $this->accounts()
            ->where('subtype', $subtype)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Akun kas/bank aktif & postable yang dapat dipilih saat transaksi
     * (mis. Kas, Kas Besar, Bank BCA, Bank BNI).
     *
     * @return Collection<int, Account>
     */
    public function cashBankAccounts(): Collection
    {
        return $this->accounts()
            ->whereIn('subtype', ['cash', 'bank'])
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();
    }

    /**
     * Pilih akun pembayaran: gunakan akun yang dipilih bila valid (kas/bank
     * milik company ini), jika tidak fallback ke akun sistem default $fallbackSubtype.
     */
    public function resolvePaymentAccount(?int $accountId, string $fallbackSubtype): ?Account
    {
        if ($accountId !== null) {
            $account = $this->accounts()
                ->whereKey($accountId)
                ->whereIn('subtype', ['cash', 'bank'])
                ->where('is_active', true)
                ->where('is_postable', true)
                ->first();

            if ($account) {
                return $account;
            }
        }

        return $this->account($fallbackSubtype);
    }
}
