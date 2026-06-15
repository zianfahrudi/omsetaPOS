<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StoreCharge;
use App\Services\Accounting\SalePoster;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutService
{
    public function __construct(private readonly SalePoster $salePoster) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int, service_fee_amount?:float|null, tax_amount?:float|null}>  $items
     */
    public function checkout(
        int $storeId,
        int $cashierId,
        array $items,
        string $paymentMethod,
        float $paidAmount,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $paymentProof = null,
        ?int $customerId = null,
        ?string $discountCode = null,
        bool $isDebt = false,
        ?string $vehiclePlateNumber = null,
        ?int $vehicleMileage = null,
    ): Sale {
        if ($items === []) {
            throw new InvalidArgumentException('Keranjang masih kosong.');
        }

        if (! in_array($paymentMethod, ['cash', 'qris'], true)) {
            throw new InvalidArgumentException('Metode pembayaran tidak valid.');
        }

        return DB::transaction(function () use ($storeId, $cashierId, $items, $paymentMethod, $paidAmount, $customerName, $customerPhone, $paymentProof, $customerId, $discountCode, $isDebt, $vehiclePlateNumber, $vehicleMileage) {
            $cart = collect($items)
                ->groupBy('product_id')
                ->map(function ($rows) {
                    $row = $rows->last();

                    return [
                        'quantity' => (int) $rows->sum('quantity'),
                        'service_fee_amount' => array_key_exists('service_fee_amount', $row) ? max(0, (float) $row['service_fee_amount']) : null,
                        'tax_amount' => array_key_exists('tax_amount', $row) ? max(0, (float) $row['tax_amount']) : null,
                    ];
                })
                ->filter(fn (array $item) => $item['quantity'] > 0);

            $products = Product::query()
                ->where('store_id', $storeId)
                ->whereIn('id', $cart->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $cart->count()) {
                throw new InvalidArgumentException('Ada produk yang tidak ditemukan di toko ini.');
            }

            $subtotal = 0.0;

            foreach ($cart as $productId => $cartItem) {
                $quantity = $cartItem['quantity'];
                $product = $products[$productId];

                if (! $product->is_active) {
                    throw new InvalidArgumentException("Produk {$product->name} tidak aktif.");
                }

                if ($product->tracksStock() && $product->stock < $quantity) {
                    throw new InvalidArgumentException("Stok {$product->name} tidak cukup.");
                }

                $subtotal += $this->unitPrice($product, $cartItem) * $quantity;
            }

            $totals = $this->calculateTotals($storeId, $subtotal, $discountCode, true);
            $grandTotal = $totals['grand_total'];
            $actualPaidAmount = $isDebt ? min($paidAmount, $grandTotal) : ($paymentMethod === 'qris' ? $grandTotal : $paidAmount);
            $debtAmount = $isDebt ? max(0, $grandTotal - $actualPaidAmount) : 0.0;

            if (! $isDebt && $actualPaidAmount < $grandTotal) {
                throw new InvalidArgumentException('Nominal bayar kurang dari total.');
            }

            $customer = $this->resolveCustomer($storeId, $customerId, $customerName, $customerPhone);
            $vehicle = $this->resolveVehicle($storeId, $customer, $vehiclePlateNumber, $vehicleMileage);

            if ($isDebt && ! $customer) {
                throw new InvalidArgumentException('Transaksi hutang wajib memilih atau membuat pelanggan.');
            }

            if ($isDebt && $debtAmount <= 0) {
                throw new InvalidArgumentException('Nominal hutang harus lebih dari Rp 0.');
            }

            $sale = Sale::create([
                'store_id' => $storeId,
                'cashier_id' => $cashierId,
                'customer_id' => $customer?->id,
                'customer_vehicle_id' => $vehicle?->id,
                'number' => $this->number('TRX'),
                'customer_name' => $customer?->name ?? $customerName,
                'customer_phone' => $customer?->phone ?? $customerPhone,
                'vehicle_plate_number' => $vehicle?->plate_number ?? $this->normalizePlateNumber($vehiclePlateNumber),
                'vehicle_mileage' => $vehicle?->mileage ?? $vehicleMileage,
                'status' => 'completed',
                'payment_method' => $paymentMethod,
                'payment_proof' => $paymentProof,
                'is_debt' => $isDebt,
                'subtotal' => $subtotal,
                'discount_id' => $totals['discount']?->id,
                'discount_code' => $totals['discount']?->code,
                'discount_type' => $totals['discount']?->type,
                'discount_value' => $totals['discount']?->value ?? 0,
                'discount_total' => $totals['discount_total'],
                'tax_percentage' => $totals['tax_percentage'],
                'tax_total' => $totals['tax_total'],
                'service_fee_percentage' => $totals['service_fee_percentage'],
                'service_fee_total' => $totals['service_fee_total'],
                'grand_total' => $grandTotal,
                'paid_amount' => $actualPaidAmount,
                'change_amount' => $isDebt ? 0 : max(0, $actualPaidAmount - $grandTotal),
                'debt_amount' => $debtAmount,
                'paid_at' => now(),
            ]);

            if ($totals['discount']) {
                $totals['discount']->increment('used_count');
            }

            if ($customer) {
                $customer->increment('visit_count');
                $customer->increment('total_spent', $grandTotal);
                $customer->forceFill(['last_purchase_at' => now()])->save();

                if ($debtAmount > 0) {
                    $customer->increment('outstanding_debt', $debtAmount);
                    $customer->increment('debt_total', $debtAmount);
                    $customer->forceFill(['last_debt_at' => now()])->save();
                }
            }

            foreach ($cart as $productId => $cartItem) {
                $quantity = $cartItem['quantity'];
                $product = $products[$productId];
                $stockBefore = $product->stock;
                $serviceFeeAmount = $this->serviceFeeAmount($product, $cartItem);
                $taxAmount = $this->taxAmount($product, $cartItem);
                $unitPrice = $this->unitPrice($product, $cartItem);
                $lineTotal = $unitPrice * $quantity;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->barcode ?: $product->sku,
                    'product_type' => $product->product_type,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $product->cost_price,
                    'fee_amount' => $product->fee_amount,
                    'service_fee_amount' => $serviceFeeAmount,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                if ($product->tracksStock()) {
                    $product->decrement('stock', $quantity);
                    $product->refresh();

                    StockMovement::create([
                        'store_id' => $storeId,
                        'product_id' => $product->id,
                        'user_id' => $cashierId,
                        'type' => 'sale',
                        'quantity' => -$quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->stock,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'notes' => "Penjualan {$sale->number}",
                    ]);
                }
            }

            ActivityLogger::log('sale.completed', "Order {$sale->number} selesai", $storeId, $sale, [
                'payment_method' => $paymentMethod,
                'grand_total' => $grandTotal,
                'discount_code' => $totals['discount']?->code,
                'is_debt' => $isDebt,
                'debt_amount' => $debtAmount,
            ]);

            $this->salePoster->post($sale);

            return $sale->load(['items', 'cashier', 'store']);
        });
    }

    /**
     * @return array{subtotal:float, discount:Discount|null, discount_total:float, tax_percentage:float, tax_total:float, service_fee_percentage:float, service_fee_total:float, grand_total:float}
     */
    public function calculateTotals(int $storeId, float $subtotal, ?string $discountCode = null, bool $lockDiscount = false): array
    {
        $charge = StoreCharge::query()->where('store_id', $storeId)->first();
        $taxPercentage = $charge?->is_tax_active ? (float) $charge->tax_percentage : 0.0;
        $serviceFeePercentage = $charge?->is_service_fee_active ? (float) $charge->service_fee_percentage : 0.0;

        $discount = $this->resolveDiscount($storeId, $subtotal, $discountCode, $lockDiscount);
        $discountTotal = $discount ? $this->discountAmount($discount, $subtotal) : 0.0;
        $taxableSubtotal = max(0, $subtotal - $discountTotal);
        $serviceFeeTotal = round($taxableSubtotal * $serviceFeePercentage / 100, 2);
        $taxTotal = round($taxableSubtotal * $taxPercentage / 100, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'discount_total' => round($discountTotal, 2),
            'tax_percentage' => round($taxPercentage, 2),
            'tax_total' => $taxTotal,
            'service_fee_percentage' => round($serviceFeePercentage, 2),
            'service_fee_total' => $serviceFeeTotal,
            'grand_total' => round($taxableSubtotal + $serviceFeeTotal + $taxTotal, 2),
        ];
    }

    /**
     * @param  array{service_fee_amount:float|null, tax_amount:float|null}  $cartItem
     */
    private function unitPrice(Product $product, array $cartItem): float
    {
        return $product->baseSalePrice()
            + $this->serviceFeeAmount($product, $cartItem)
            + $this->taxAmount($product, $cartItem);
    }

    /**
     * @param  array{service_fee_amount:float|null, tax_amount:float|null}  $cartItem
     */
    private function serviceFeeAmount(Product $product, array $cartItem): float
    {
        return $cartItem['service_fee_amount'] ?? $product->productServiceFeeAmount();
    }

    /**
     * @param  array{service_fee_amount:float|null, tax_amount:float|null}  $cartItem
     */
    private function taxAmount(Product $product, array $cartItem): float
    {
        return $cartItem['tax_amount'] ?? $product->productTaxAmount();
    }

    private function resolveDiscount(int $storeId, float $subtotal, ?string $discountCode, bool $lockDiscount): ?Discount
    {
        $discountCode = mb_strtoupper(trim((string) $discountCode));

        if ($discountCode === '') {
            return null;
        }

        $query = Discount::query()
            ->where('store_id', $storeId)
            ->whereRaw('UPPER(code) = ?', [$discountCode]);

        if ($lockDiscount) {
            $query->lockForUpdate();
        }

        $discount = $query->first();

        if (! $discount || ! $discount->is_active) {
            throw new InvalidArgumentException('Kode diskon tidak valid.');
        }

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            throw new InvalidArgumentException('Kode diskon belum aktif.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            throw new InvalidArgumentException('Kode diskon sudah kedaluwarsa.');
        }

        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
            throw new InvalidArgumentException('Kuota kode diskon sudah habis.');
        }

        if ($subtotal < (float) $discount->minimum_spend) {
            throw new InvalidArgumentException('Belanja belum memenuhi minimum kode diskon.');
        }

        return $discount;
    }

    private function discountAmount(Discount $discount, float $subtotal): float
    {
        $value = max(0, (float) $discount->value);
        $amount = match ($discount->type) {
            'percentage' => $subtotal * min($value, 100) / 100,
            default => $value,
        };

        return min($subtotal, round($amount, 2));
    }

    private function resolveCustomer(int $storeId, ?int $customerId, ?string $customerName, ?string $customerPhone): ?Customer
    {
        if ($customerId !== null) {
            $customer = Customer::query()->where('store_id', $storeId)->find($customerId);
            if ($customer) {
                return $customer;
            }
        }

        $customerName = trim((string) $customerName);
        $customerPhone = trim((string) $customerPhone);

        if ($customerName === '' && $customerPhone === '') {
            return null;
        }

        $duplicateExists = Customer::query()
            ->where('store_id', $storeId)
            ->where(function ($query) use ($customerName, $customerPhone) {
                if ($customerPhone === '' && $customerName !== '') {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($customerName)]);
                }

                if ($customerPhone !== '') {
                    $query->where('phone', $customerPhone);
                }
            })
            ->exists();

        if ($duplicateExists) {
            throw new InvalidArgumentException('Pelanggan sudah terdaftar. Pilih dari database pelanggan.');
        }

        return Customer::create([
            'store_id' => $storeId,
            'name' => $customerName !== '' ? $customerName : $customerPhone,
            'phone' => $customerPhone !== '' ? $customerPhone : null,
        ]);
    }

    private function resolveVehicle(int $storeId, ?Customer $customer, ?string $plateNumber, ?int $mileage): ?CustomerVehicle
    {
        if (! $customer) {
            return null;
        }

        $plateNumber = $this->normalizePlateNumber($plateNumber);

        if ($plateNumber === null) {
            return null;
        }

        $vehicle = CustomerVehicle::query()
            ->where('customer_id', $customer->id)
            ->whereRaw('UPPER(plate_number) = ?', [$plateNumber])
            ->lockForUpdate()
            ->first();

        if (! $vehicle) {
            return CustomerVehicle::create([
                'store_id' => $storeId,
                'customer_id' => $customer->id,
                'plate_number' => $plateNumber,
                'mileage' => $mileage,
            ]);
        }

        $vehicle->forceFill([
            'mileage' => $mileage ?? $vehicle->mileage,
        ])->save();

        return $vehicle;
    }

    private function normalizePlateNumber(?string $plateNumber): ?string
    {
        $plateNumber = mb_strtoupper(trim((string) $plateNumber));

        return $plateNumber !== '' ? preg_replace('/\s+/', ' ', $plateNumber) : null;
    }

    private function number(string $prefix): string
    {
        do {
            $number = $prefix.'-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        } while (Sale::where('number', $number)->exists());

        return $number;
    }
}
