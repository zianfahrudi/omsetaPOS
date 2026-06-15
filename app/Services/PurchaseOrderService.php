<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Purchase order pipeline document (no ledger impact). Can be converted into a
 * posted purchase invoice via PurchaseService.
 */
class PurchaseOrderService
{
    public function __construct(private readonly PurchaseService $purchases) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, quantity:int|float, unit_cost:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        Carbon|string|null $expectedDate = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): PurchaseOrder {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pesanan pembelian harus memiliki minimal 1 item.');
        }

        $supplier = Contact::query()
            ->where('company_id', $company->id)
            ->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Supplier tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $supplier, $items, $date, $expectedDate, $notes, $createdBy) {
            $order = PurchaseOrder::create([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'expected_date' => $expectedDate ? Carbon::parse($expectedDate) : null,
                'status' => 'confirmed',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($items as $line) {
                $product = isset($line['product_id']) && $line['product_id'] ? Product::find($line['product_id']) : null;
                $quantity = (int) $line['quantity'];
                $unitCost = round((float) $line['unit_cost'], 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitCost * $quantity, 2);

                $order->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'line_type' => $product && ! $product->tracksStock() ? 'expense' : 'goods',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;
            }

            $order->forceFill([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($subtotal + $taxTotal, 2),
            ])->save();

            ActivityLogger::log('purchase_order.created', "Pesanan pembelian {$order->number}", null, $order, [
                'supplier' => $supplier->name,
                'grand_total' => $order->grand_total,
            ]);

            return $order->load('items', 'supplier');
        });
    }

    public function convertToPurchase(PurchaseOrder $order, ?int $createdBy = null): PurchaseOrder
    {
        if ($order->isReceived()) {
            throw new InvalidArgumentException('Pesanan sudah dikonversi menjadi faktur.');
        }

        return DB::transaction(function () use ($order, $createdBy) {
            $order = PurchaseOrder::query()->with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isReceived()) {
                throw new InvalidArgumentException('Pesanan sudah dikonversi menjadi faktur.');
            }

            $items = $order->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'line_type' => $i->line_type,
                'quantity' => $i->quantity,
                'unit_cost' => (float) $i->unit_cost,
                'tax_amount' => (float) $i->tax_amount,
            ])->all();

            $purchase = $this->purchases->create(
                company: $order->company,
                contactId: $order->contact_id,
                items: $items,
                date: now(),
                notes: "Dari pesanan {$order->number}",
                createdBy: $createdBy,
            );

            $order->forceFill([
                'status' => 'received',
                'purchase_id' => $purchase->id,
            ])->save();

            ActivityLogger::log('purchase_order.received', "Pesanan {$order->number} jadi faktur {$purchase->number}", null, $order, [
                'purchase' => $purchase->number,
            ]);

            return $order->fresh(['items', 'supplier', 'purchase']);
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = PurchaseOrder::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('PO/%s/%04d', $period, $sequence);
            $sequence++;
        } while (PurchaseOrder::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
