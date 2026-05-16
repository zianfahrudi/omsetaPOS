<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Services\CheckoutService;
use App\Services\RefundService;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class CashierController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect('/admin/login');
        }

        return view('cashier.index', [
            'stores' => $this->stores(),
            'user' => Auth::user(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $storeId = (int) $request->query('store_id');

        abort_unless($this->canAccessStore($storeId), 403);

        $term = trim((string) $request->query('q', ''));
        $productId = (int) $request->query('product_id');

        $products = Product::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->when($productId > 0, fn ($query) => $query->whereKey($productId))
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like));
            })
            ->orderBy('name')
            ->limit(80)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->barcode ?: $product->sku,
                'barcode' => $product->barcode,
                'sku' => $product->sku,
                'image_url' => $product->image_url,
                'product_type' => $product->product_type,
                'price' => $product->unitSalePrice(),
                'fee_amount' => (float) $product->fee_amount,
                'product_service_fee' => $product->productServiceFeeAmount(),
                'product_service_fee_type' => $product->product_service_fee_type,
                'product_service_fee_value' => (float) $product->product_service_fee,
                'product_tax_type' => $product->product_tax_type,
                'product_tax_value' => (float) $product->product_tax_value,
                'product_tax_amount' => $product->productTaxAmount(),
                'base_price' => $product->baseSalePrice(),
                'unit_price' => $product->unitSalePrice(),
                'stock' => $product->stock,
                'unit' => $product->unit,
            ]);

        return response()->json(['products' => $products]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $storeId = (int) $data['store_id'];

        abort_unless($this->canAccessStore($storeId), 403);

        $term = trim((string) ($data['q'] ?? ''));

        $sales = Sale::query()
            ->with(['items', 'store', 'customer', 'cashier'])
            ->where('store_id', $storeId)
            ->where('cashier_id', Auth::id())
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
            ->get()
            ->map(fn (Sale $sale) => $this->salePayload($sale));

        return response()->json(['transactions' => $sales]);
    }

    public function markTransactionPaid(Sale $sale): JsonResponse
    {
        abort_unless($this->canAccessStore((int) $sale->store_id), 403);
        abort_unless((int) $sale->cashier_id === (int) Auth::id(), 403);

        if (! $sale->is_debt || (float) $sale->debt_amount <= 0) {
            return response()->json(['message' => 'Transaksi sudah lunas.'], 422);
        }

        $paidDebt = (float) $sale->debt_amount;

        DB::transaction(function () use ($sale, $paidDebt) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! $sale->is_debt || (float) $sale->debt_amount <= 0) {
                throw new InvalidArgumentException('Transaksi sudah lunas.');
            }

            $sale->increment('paid_amount', $paidDebt);
            $sale->forceFill([
                'debt_amount' => 0,
                'change_amount' => 0,
            ])->save();

            if ($sale->customer_id) {
                $customer = Customer::query()->lockForUpdate()->find($sale->customer_id);
                $customer?->forceFill([
                    'outstanding_debt' => max(0, (float) $customer->outstanding_debt - $paidDebt),
                ])->save();
            }

            ActivityLogger::log('sale.debt_paid', "Transaksi {$sale->number} ditandai lunas", $sale->store_id, $sale, [
                'paid_debt' => $paidDebt,
            ]);
        });

        return response()->json([
            'sale' => $this->salePayload($sale->fresh(['items', 'store', 'customer', 'cashier'])),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $storeId = (int) $request->query('store_id');

        abort_unless($this->canAccessStore($storeId), 403);

        $term = trim((string) $request->query('q', ''));

        $customers = Customer::query()
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
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'vehicles' => $customer->vehicles->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'mileage' => $vehicle->mileage,
                ])->values(),
            ]);

        return response()->json(['customers' => $customers]);
    }

    public function vehicles(Request $request): JsonResponse
    {
        $storeId = (int) $request->query('store_id');

        abort_unless($this->canAccessStore($storeId), 403);

        $term = trim((string) $request->query('q', ''));

        $vehicles = CustomerVehicle::query()
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
            ->get()
            ->map(fn (CustomerVehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'mileage' => $vehicle->mileage,
                'customer' => [
                    'id' => $vehicle->customer?->id,
                    'name' => $vehicle->customer?->name,
                    'phone' => $vehicle->customer?->phone,
                ],
            ]);

        return response()->json(['vehicles' => $vehicles]);
    }

    public function checkCustomer(Request $request): JsonResponse
    {
        $storeId = (int) $request->query('store_id');

        abort_unless($this->canAccessStore($storeId), 403);

        $name = trim((string) $request->query('name', ''));
        $phone = trim((string) $request->query('phone', ''));

        if ($name === '' && $phone === '') {
            return response()->json(['exists' => false]);
        }

        $exists = Customer::query()
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

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Pelanggan sudah terdaftar. Pilih dari database pelanggan.' : null,
        ]);
    }

    public function refund(Request $request, RefundService $refundService): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'sale_id' => ['required', 'integer', 'exists:sales,id'],
            'type' => ['required', 'in:full,exchange'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'additional_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'evidence_photos' => ['required', 'array', 'min:1', 'max:6'],
            'evidence_photos.*' => ['image', 'max:5120'],
            'returned_items' => ['nullable', 'array'],
            'returned_items.*.sale_item_id' => ['required_with:returned_items', 'integer', 'exists:sale_items,id'],
            'returned_items.*.quantity' => ['required_with:returned_items', 'integer', 'min:1'],
            'replacement_items' => ['nullable', 'array'],
            'replacement_items.*.product_id' => ['required_with:replacement_items', 'integer', 'exists:products,id'],
            'replacement_items.*.quantity' => ['required_with:replacement_items', 'integer', 'min:1'],
        ]);

        $storeId = (int) $data['store_id'];

        abort_unless($this->canAccessStore($storeId), 403);

        $sale = Sale::query()
            ->with('items')
            ->whereKey((int) $data['sale_id'])
            ->where('store_id', $storeId)
            ->where('cashier_id', Auth::id())
            ->firstOrFail();

        if ((float) $sale->debt_amount > 0) {
            return response()->json(['message' => 'Transaksi belum lunas tidak bisa direfund.'], 422);
        }

        $returnedItems = $data['type'] === 'full'
            ? $sale->items
                ->map(fn ($item) => [
                    'sale_item_id' => $item->id,
                    'quantity' => max(0, $item->quantity - $item->refunded_quantity),
                ])
                ->filter(fn (array $item) => $item['quantity'] > 0)
                ->values()
                ->all()
            : ($data['returned_items'] ?? []);

        if ($returnedItems === []) {
            return response()->json(['message' => 'Tidak ada item yang bisa direfund.'], 422);
        }

        $evidencePhotos = [];
        foreach ($request->file('evidence_photos', []) as $photo) {
            $evidencePhotos[] = $photo->store('refund-proofs', 'public');
        }

        $additionalPaidAmount = (float) ($data['additional_payment_amount'] ?? 0);

        try {
            $refund = $refundService->refund(
                saleId: $sale->id,
                handledById: (int) Auth::id(),
                type: $data['type'],
                returnedItems: $returnedItems,
                replacementItems: $data['type'] === 'exchange' ? ($data['replacement_items'] ?? []) : [],
                reason: $data['reason'] ?? null,
                additionalPaymentAmount: $additionalPaidAmount,
                evidencePhotos: $evidencePhotos,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $refund->load(['items', 'sale', 'handledBy', 'store']);
        $additionalRequired = (float) $refund->additional_payment_amount;

        return response()->json([
            'refund' => [
                'receipt_type' => 'refund',
                'id' => $refund->id,
                'number' => $refund->number,
                'sale_number' => $refund->sale?->number,
                'store_name' => $refund->store?->name,
                'handled_by_name' => $refund->handledBy?->name,
                'customer_name' => $refund->sale?->customer_name ?? 'Pelanggan Umum',
                'type' => $refund->type,
                'status' => $refund->status,
                'reason' => $refund->reason,
                'returned_total' => (float) $refund->returned_total,
                'replacement_total' => (float) $refund->replacement_total,
                'refund_amount' => (float) $refund->refund_amount,
                'additional_payment_amount' => $additionalRequired,
                'additional_paid_amount' => $additionalPaidAmount,
                'change_amount' => max(0, $additionalPaidAmount - $additionalRequired),
                'evidence_photos' => $refund->evidence_photos ?? [],
                'created_at' => $refund->created_at->format('d M Y H:i'),
                'items' => $refund->items->map(fn ($item) => [
                    'direction' => $item->direction,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
            ],
            'sale_status' => $refund->sale->refresh()->status,
        ]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:30'],
            'vehicle_mileage' => ['nullable', 'integer', 'min:0'],
        ]);

        $storeId = (int) $data['store_id'];

        abort_unless($this->canAccessStore($storeId), 403);

        $name = trim($data['name']);
        $phone = trim((string) ($data['phone'] ?? ''));

        $duplicateExists = Customer::query()
            ->where('store_id', $storeId)
            ->where(function ($query) use ($name, $phone) {
                if ($phone === '') {
                    $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }

                if ($phone !== '') {
                    $query->where('phone', $phone);
                }
            })
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'message' => 'Pelanggan sudah terdaftar. Pilih dari database pelanggan.',
            ], 422);
        }

        $customer = Customer::create([
            'store_id' => $storeId,
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        $plateNumber = mb_strtoupper(trim((string) ($data['vehicle_plate_number'] ?? '')));
        if ($plateNumber !== '') {
            $customer->vehicles()->create([
                'store_id' => $storeId,
                'plate_number' => preg_replace('/\s+/', ' ', $plateNumber),
                'mileage' => $data['vehicle_mileage'] ?? null,
            ]);
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'vehicles' => $customer->vehicles()->get(['id', 'plate_number', 'mileage']),
            ],
        ], 201);
    }

    public function storeVehicle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'vehicle_name' => ['nullable', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:30'],
            'mileage' => ['nullable', 'integer', 'min:0'],
        ]);

        $storeId = (int) $data['store_id'];

        abort_unless($this->canAccessStore($storeId), 403);

        $ownerName = trim($data['owner_name']);
        $ownerPhone = trim((string) ($data['owner_phone'] ?? ''));
        $plateNumber = $this->normalizePlateNumber($data['plate_number']);

        if ($plateNumber === null) {
            return response()->json(['message' => 'Nomor plat wajib diisi.'], 422);
        }

        $customer = null;

        if (! empty($data['customer_id'])) {
            $customer = Customer::query()
                ->where('store_id', $storeId)
                ->whereKey((int) $data['customer_id'])
                ->firstOrFail();

            $customer->forceFill([
                'name' => $ownerName,
                'phone' => $ownerPhone !== '' ? $ownerPhone : null,
            ])->save();
        } elseif ($ownerPhone !== '') {
            $customer = Customer::query()
                ->where('store_id', $storeId)
                ->where('phone', $ownerPhone)
                ->first();
        } else {
            $customer = Customer::query()
                ->where('store_id', $storeId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($ownerName)])
                ->first();
        }

        if (! $customer) {
            $customer = Customer::create([
                'store_id' => $storeId,
                'name' => $ownerName,
                'phone' => $ownerPhone !== '' ? $ownerPhone : null,
            ]);
        }

        $vehicle = CustomerVehicle::query()
            ->where('customer_id', $customer->id)
            ->where('plate_number', $plateNumber)
            ->first();

        if (! $vehicle) {
            $vehicle = new CustomerVehicle([
                'store_id' => $storeId,
                'customer_id' => $customer->id,
                'plate_number' => $plateNumber,
            ]);
        }

        $vehicle->forceFill([
            'store_id' => $storeId,
            'customer_id' => $customer->id,
            'name' => trim((string) ($data['vehicle_name'] ?? '')) ?: null,
            'mileage' => $data['mileage'] ?? null,
        ])->save();

        return response()->json([
            'vehicle' => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'plate_number' => $vehicle->plate_number,
                'mileage' => $vehicle->mileage,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ],
            ],
        ], $vehicle->wasRecentlyCreated ? 201 : 200);
    }

    public function pricing(Request $request, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_code' => ['nullable', 'string', 'max:60'],
        ]);

        abort_unless($this->canAccessStore((int) $data['store_id']), 403);

        try {
            $totals = $checkoutService->calculateTotals(
                storeId: (int) $data['store_id'],
                subtotal: (float) ($data['subtotal'] ?? 0),
                discountCode: $data['discount_code'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'pricing' => [
                'discount_code' => $totals['discount']?->code,
                'discount_name' => $totals['discount']?->name,
                'discount_type' => $totals['discount']?->type,
                'discount_value' => $totals['discount'] ? (float) $totals['discount']->value : 0,
                'discount_total' => $totals['discount_total'],
                'tax_percentage' => $totals['tax_percentage'],
                'tax_total' => $totals['tax_total'],
                'service_fee_percentage' => $totals['service_fee_percentage'],
                'service_fee_total' => $totals['service_fee_total'],
                'grand_total' => $totals['grand_total'],
            ],
        ]);
    }

    public function checkout(Request $request, CheckoutService $checkoutService): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'vehicle_plate_number' => ['nullable', 'string', 'max:30'],
            'vehicle_mileage' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['required', 'in:cash,qris'],
            'payment_proof' => ['nullable', 'required_if:payment_method,qris', 'image', 'max:5120'],
            'is_debt' => ['nullable', 'boolean'],
            'discount_code' => ['nullable', 'string', 'max:60'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.service_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        abort_unless($this->canAccessStore((int) $data['store_id']), 403);

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
        }

        try {
            /** @var Sale $sale */
            $sale = $checkoutService->checkout(
                storeId: (int) $data['store_id'],
                cashierId: (int) Auth::id(),
                items: $data['items'],
                paymentMethod: $data['payment_method'],
                paidAmount: (float) $data['paid_amount'],
                customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                customerName: $data['customer_name'] ?? null,
                customerPhone: $data['customer_phone'] ?? null,
                paymentProof: $paymentProofPath,
                discountCode: $data['discount_code'] ?? null,
                isDebt: (bool) ($data['is_debt'] ?? false),
                vehiclePlateNumber: $data['vehicle_plate_number'] ?? null,
                vehicleMileage: isset($data['vehicle_mileage']) ? (int) $data['vehicle_mileage'] : null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'sale' => [
                'id' => $sale->id,
                'number' => $sale->number,
                'store_name' => $sale->store?->name,
                'cashier_name' => $sale->cashier?->name,
                'grand_total' => (float) $sale->grand_total,
                'subtotal' => (float) $sale->subtotal,
                'discount_code' => $sale->discount_code,
                'discount_total' => (float) $sale->discount_total,
                'tax_total' => (float) $sale->tax_total,
                'service_fee_total' => (float) $sale->service_fee_total,
                'is_debt' => (bool) $sale->is_debt,
                'debt_amount' => (float) $sale->debt_amount,
                'payment_status' => ((bool) $sale->is_debt && (float) $sale->debt_amount > 0) ? 'belum_lunas' : 'lunas',
                'payment_status_label' => ((bool) $sale->is_debt && (float) $sale->debt_amount > 0) ? 'Belum lunas' : 'Lunas',
                'paid_amount' => (float) $sale->paid_amount,
                'change_amount' => (float) $sale->change_amount,
                'payment_method' => $sale->payment_method,
                'payment_proof' => $sale->payment_proof,
                'customer_name' => $sale->customer_name,
                'customer_phone' => $sale->customer_phone,
                'vehicle_plate_number' => $sale->vehicle_plate_number,
                'vehicle_mileage' => $sale->vehicle_mileage,
                'paid_at' => $sale->paid_at?->format('d M Y H:i'),
                'items' => $sale->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'product_type' => $item->product_type,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'fee_amount' => (float) $item->fee_amount,
                    'service_fee_amount' => (float) $item->service_fee_amount,
                    'tax_amount' => (float) $item->tax_amount,
                    'line_total' => (float) $item->line_total,
                ])->values(),
            ],
        ]);
    }

    private function stores(): Collection
    {
        $user = Auth::user();

        if ($user?->isSuperuser()) {
            return Store::query()->where('is_active', true)->orderBy('name')->get();
        }

        return $user?->stores()->where('stores.is_active', true)->orderBy('name')->get() ?? collect();
    }

    private function canAccessStore(int $storeId): bool
    {
        if (! Auth::check()) {
            return false;
        }

        if (Auth::user()->isSuperuser()) {
            return Store::query()->whereKey($storeId)->where('is_active', true)->exists();
        }

        return Auth::user()
            ->stores()
            ->where('stores.id', $storeId)
            ->where('stores.is_active', true)
            ->exists();
    }

    private function normalizePlateNumber(?string $plateNumber): ?string
    {
        $plateNumber = mb_strtoupper(trim((string) $plateNumber));
        $plateNumber = preg_replace('/\s+/', ' ', $plateNumber);

        return $plateNumber !== '' ? $plateNumber : null;
    }

    private function salePayload(Sale $sale): array
    {
        $debtAmount = (float) $sale->debt_amount;

        return [
            'id' => $sale->id,
            'number' => $sale->number,
            'store_name' => $sale->store?->name,
            'cashier_name' => $sale->cashier?->name,
            'customer_name' => $sale->customer?->name ?? $sale->customer_name ?? 'Pelanggan Umum',
            'customer_phone' => $sale->customer?->phone ?? $sale->customer_phone,
            'vehicle_plate_number' => $sale->vehicle_plate_number,
            'vehicle_mileage' => $sale->vehicle_mileage,
            'payment_method' => $sale->payment_method,
            'status' => $sale->status,
            'payment_status' => ((bool) $sale->is_debt && $debtAmount > 0) ? 'belum_lunas' : 'lunas',
            'payment_status_label' => ((bool) $sale->is_debt && $debtAmount > 0) ? 'Belum lunas' : 'Lunas',
            'subtotal' => (float) $sale->subtotal,
            'discount_total' => (float) $sale->discount_total,
            'tax_total' => (float) $sale->tax_total,
            'service_fee_total' => (float) $sale->service_fee_total,
            'grand_total' => (float) $sale->grand_total,
            'paid_amount' => (float) $sale->paid_amount,
            'change_amount' => (float) $sale->change_amount,
            'is_debt' => (bool) $sale->is_debt,
            'debt_amount' => $debtAmount,
            'paid_at' => $sale->paid_at?->format('d M Y H:i') ?? $sale->created_at->format('d M Y H:i'),
            'items' => $sale->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'product_type' => $item->product_type,
                'quantity' => $item->quantity,
                'refunded_quantity' => $item->refunded_quantity,
                'refundable_quantity' => max(0, $item->quantity - $item->refunded_quantity),
                'unit_price' => (float) $item->unit_price,
                'fee_amount' => (float) $item->fee_amount,
                'service_fee_amount' => (float) $item->service_fee_amount,
                'tax_amount' => (float) $item->tax_amount,
                'line_total' => (float) $item->line_total,
            ])->values(),
        ];
    }
}
