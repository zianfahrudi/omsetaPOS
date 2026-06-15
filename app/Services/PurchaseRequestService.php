<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Purchase request (permintaan pembelian). Internal request, no ledger impact.
 * Converts into a purchase order via PurchaseOrderService.
 */
class PurchaseRequestService
{
    public function __construct(private readonly PurchaseOrderService $orders) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, quantity:int|float, unit_cost?:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        Carbon|string|null $neededDate = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): PurchaseRequest {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Permintaan harus memiliki minimal 1 item.');
        }

        $supplier = Contact::query()->where('company_id', $company->id)->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Supplier tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $supplier, $items, $date, $neededDate, $notes, $createdBy) {
            $request = PurchaseRequest::create([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'needed_date' => $neededDate ? Carbon::parse($neededDate) : null,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($items as $line) {
                $product = isset($line['product_id']) && $line['product_id'] ? Product::find($line['product_id']) : null;
                $quantity = (int) $line['quantity'];
                $unitCost = round((float) ($line['unit_cost'] ?? $product?->cost_price ?? 0), 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitCost * $quantity, 2);

                $request->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;
            }

            $request->forceFill([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($subtotal + $taxTotal, 2),
            ])->save();

            ActivityLogger::log('purchase_request.created', "Permintaan pembelian {$request->number}", null, $request, [
                'supplier' => $supplier->name,
            ]);

            return $request->load('items', 'supplier');
        });
    }

    public function convertToOrder(PurchaseRequest $request, ?int $createdBy = null): PurchaseRequest
    {
        if ($request->isOrdered()) {
            throw new InvalidArgumentException('Permintaan sudah dikonversi menjadi pesanan.');
        }

        return DB::transaction(function () use ($request, $createdBy) {
            $request = PurchaseRequest::query()->with('items')->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->isOrdered()) {
                throw new InvalidArgumentException('Permintaan sudah dikonversi menjadi pesanan.');
            }

            $items = $request->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'unit_cost' => (float) $i->unit_cost,
                'tax_amount' => (float) $i->tax_amount,
            ])->all();

            $order = $this->orders->create(
                company: $request->company,
                contactId: $request->contact_id,
                items: $items,
                date: now(),
                notes: "Dari permintaan {$request->number}",
                createdBy: $createdBy,
            );

            $request->forceFill(['status' => 'ordered', 'purchase_order_id' => $order->id])->save();

            ActivityLogger::log('purchase_request.ordered', "Permintaan {$request->number} jadi pesanan {$order->number}", null, $request, []);

            return $request->fresh(['items', 'supplier', 'order']);
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = PurchaseRequest::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('PR/%s/%04d', $period, $sequence);
            $sequence++;
        } while (PurchaseRequest::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
