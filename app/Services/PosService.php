<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Shared read/creation operations for the point of sale, consumed by both the
 * web cashier and the API so behaviour stays identical across clients.
 */
class PosService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @return Collection<int, Product>
     */
    public function products(int $storeId, string $term = '', ?int $productId = null): Collection
    {
        return Product::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->when($productId, fn ($query) => $query->whereKey($productId))
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like));
            })
            ->orderBy('name')
            ->limit(80)
            ->get();
    }

    /**
     * @return Collection<int, Customer>
     */
    public function customers(int $storeId, string $term = ''): Collection
    {
        return Customer::query()
            ->where('store_id', $storeId)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('vehicles', fn ($query) => $query->where('plate_number', 'like', $like)));
            })
            ->with('vehicles')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, CustomerVehicle>
     */
    public function vehicles(int $storeId, string $term = ''): Collection
    {
        return CustomerVehicle::query()
            ->with('customer')
            ->where('store_id', $storeId)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('plate_number', 'like', $like)
                    ->orWhereHas('customer', fn ($query) => $query
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)));
            })
            ->orderBy('plate_number')
            ->limit(20)
            ->get();
    }

    public function customerExists(int $storeId, string $name, string $phone): bool
    {
        if ($name === '' && $phone === '') {
            return false;
        }

        return Customer::query()
            ->where('store_id', $storeId)
            ->where(function ($query) use ($name, $phone) {
                if ($phone === '' && $name !== '') {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }

                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }
            })
            ->exists();
    }

    /**
     * Daftar petugas (Employee aktif) pada perusahaan toko, untuk pemilih
     * mekanik/salesman di kasir. Aman saat toko belum punya company.
     *
     * @return Collection<int, Employee>
     */
    public function employees(int $storeId, string $term = ''): Collection
    {
        $companyId = Store::query()->whereKey($storeId)->value('company_id');

        if ($companyId === null) {
            return new Collection;
        }

        return Employee::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('code', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code', 'position']);
    }

    /**
     * @return Collection<int, Sale>
     */
    public function transactions(int $storeId, int $cashierId, string $term = ''): Collection
    {
        return Sale::query()
            ->with(['items', 'store', 'customer', 'cashier'])
            ->where('store_id', $storeId)
            ->where('cashier_id', $cashierId)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(function ($query) use ($like) {
                    $query
                        ->where('number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('vehicle_plate_number', 'like', $like)
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', $like))
                        ->orWhereHas('items', fn ($query) => $query->where('product_name', 'like', $like));
                });
            })
            ->latest()
            ->limit(30)
            ->get();
    }

    public function markTransactionPaid(Sale $sale, ?float $amount = null, string $method = 'cash'): Sale
    {
        if (! $sale->is_debt || (float) $sale->debt_amount <= 0) {
            throw new InvalidArgumentException('Transaksi sudah lunas.');
        }

        if (! in_array($method, Sale::PAYMENT_METHODS, true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        DB::transaction(function () use ($sale, $amount, $method) {
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            $outstanding = (float) $locked->debt_amount;
            if (! $locked->is_debt || $outstanding <= 0) {
                throw new InvalidArgumentException('Transaksi sudah lunas.');
            }

            // Default: lunasi seluruh sisa. Bila $amount diberikan, bayar sebagian (maks sisa).
            $pay = $amount === null ? $outstanding : round(min(max(0, $amount), $outstanding), 2);
            if ($pay <= 0) {
                throw new InvalidArgumentException('Nominal pembayaran harus lebih dari Rp 0.');
            }

            $locked->increment('paid_amount', $pay);
            $locked->forceFill([
                'debt_amount' => round($outstanding - $pay, 2),
                'change_amount' => 0,
            ])->save();

            $locked->payments()->create([
                'method' => $method,
                'amount' => $pay,
                'is_settlement' => true,
                'paid_at' => now(),
            ]);

            if ($locked->customer_id) {
                $customer = Customer::query()->lockForUpdate()->find($locked->customer_id);
                $customer?->forceFill([
                    'outstanding_debt' => max(0, (float) $customer->outstanding_debt - $pay),
                ])->save();
            }

            $remaining = (float) $locked->fresh()->debt_amount;
            $label = $remaining > 0 ? 'dibayar sebagian' : 'ditandai lunas';
            ActivityLogger::log('sale.debt_paid', "Transaksi {$locked->number} {$label}", $locked->store_id, $locked, [
                'paid_debt' => $pay,
                'method' => $method,
                'remaining_debt' => $remaining,
            ]);

            $this->postDebtPayment($locked, $pay, $method);
        });

        return $sale->fresh(['items', 'store', 'customer', 'cashier', 'payments']);
    }

    /**
     * Post a cash/bank receipt journal for a debt settlement: Dr Kas/Bank, Cr Piutang.
     */
    private function postDebtPayment(Sale $sale, float $amount, string $method = 'cash'): void
    {
        $company = $sale->loadMissing('store')->store?->company;

        if (! $company || $amount <= 0) {
            return;
        }

        $subtype = $method === 'cash' ? 'cash' : 'bank';
        $debitAccount = $company->account($subtype);
        $receivable = $company->account('accounts_receivable');

        if (! $debitAccount || ! $receivable) {
            return;
        }

        $this->posting->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $debitAccount->id, 'debit' => $amount, 'store_id' => $sale->store_id, 'memo' => 'Pelunasan piutang ('.$method.')'],
                ['account_id' => $receivable->id, 'credit' => $amount, 'contact_id' => $sale->customer_id, 'store_id' => $sale->store_id, 'memo' => 'Pelunasan piutang'],
            ],
            type: 'cash_receipt',
            description: "Pelunasan piutang {$sale->number}",
            reference: $sale->number,
            source: $sale,
        );
    }

    /**
     * @param  array{name:string, phone?:string|null, vehicle_plate_number?:string|null, vehicle_mileage?:int|null}  $data
     */
    public function createCustomer(int $storeId, array $data): Customer
    {
        $name = trim($data['name']);
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($this->customerExists($storeId, $phone === '' ? $name : '', $phone)) {
            throw new InvalidArgumentException('Pelanggan sudah terdaftar. Pilih dari database pelanggan.');
        }

        $customer = Customer::create([
            'store_id' => $storeId,
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        $plateNumber = $this->normalizePlateNumber($data['vehicle_plate_number'] ?? null);
        if ($plateNumber !== null) {
            $customer->vehicles()->create([
                'store_id' => $storeId,
                'plate_number' => $plateNumber,
                'mileage' => $data['vehicle_mileage'] ?? null,
            ]);
        }

        return $customer->load('vehicles');
    }

    /**
     * @param  array{customer_id?:int|null, owner_name:string, owner_phone?:string|null, vehicle_name?:string|null, plate_number:string, mileage?:int|null}  $data
     */
    public function createOrUpdateVehicle(int $storeId, array $data): CustomerVehicle
    {
        $ownerName = trim($data['owner_name']);
        $ownerPhone = trim((string) ($data['owner_phone'] ?? ''));
        $plateNumber = $this->normalizePlateNumber($data['plate_number']);

        if ($plateNumber === null) {
            throw new InvalidArgumentException('Nomor plat wajib diisi.');
        }

        $customer = $this->resolveVehicleOwner($storeId, $data['customer_id'] ?? null, $ownerName, $ownerPhone);

        $vehicle = CustomerVehicle::query()
            ->where('customer_id', $customer->id)
            ->where('plate_number', $plateNumber)
            ->first() ?? new CustomerVehicle(['plate_number' => $plateNumber]);

        $vehicle->forceFill([
            'store_id' => $storeId,
            'customer_id' => $customer->id,
            'plate_number' => $plateNumber,
            'name' => trim((string) ($data['vehicle_name'] ?? '')) ?: null,
            'mileage' => $data['mileage'] ?? null,
        ])->save();

        return $vehicle->load('customer');
    }

    private function resolveVehicleOwner(int $storeId, ?int $customerId, string $ownerName, string $ownerPhone): Customer
    {
        if ($customerId) {
            $customer = Customer::query()->where('store_id', $storeId)->whereKey($customerId)->firstOrFail();
            $customer->forceFill([
                'name' => $ownerName,
                'phone' => $ownerPhone !== '' ? $ownerPhone : null,
            ])->save();

            return $customer;
        }

        $existing = $ownerPhone !== ''
            ? Customer::query()->where('store_id', $storeId)->where('phone', $ownerPhone)->first()
            : Customer::query()->where('store_id', $storeId)->whereRaw('LOWER(name) = ?', [mb_strtolower($ownerName)])->first();

        return $existing ?? Customer::create([
            'store_id' => $storeId,
            'name' => $ownerName,
            'phone' => $ownerPhone !== '' ? $ownerPhone : null,
        ]);
    }

    private function normalizePlateNumber(?string $plateNumber): ?string
    {
        $plateNumber = mb_strtoupper(trim((string) $plateNumber));
        $plateNumber = preg_replace('/\s+/', ' ', $plateNumber);

        return $plateNumber !== '' ? $plateNumber : null;
    }
}
