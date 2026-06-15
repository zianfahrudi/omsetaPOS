<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesQuotation;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Sales quotation (penawaran harga). No ledger impact. Can be converted into a
 * sales order via SalesOrderService.
 */
class SalesQuotationService
{
    public function __construct(private readonly SalesOrderService $orders) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, quantity:int|float, unit_price:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        Carbon|string|null $validUntil = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): SalesQuotation {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Penawaran harus memiliki minimal 1 item.');
        }

        $customer = Contact::query()->where('company_id', $company->id)->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Pelanggan tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $customer, $items, $date, $validUntil, $notes, $createdBy) {
            $quotation = SalesQuotation::create([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'valid_until' => $validUntil ? Carbon::parse($validUntil) : null,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($items as $line) {
                $product = isset($line['product_id']) && $line['product_id'] ? Product::find($line['product_id']) : null;
                $quantity = (int) $line['quantity'];
                $unitPrice = round((float) $line['unit_price'], 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitPrice * $quantity, 2);

                $quotation->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;
            }

            $quotation->forceFill([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($taxTotal, 2),
                'grand_total' => round($subtotal + $taxTotal, 2),
            ])->save();

            ActivityLogger::log('sales_quotation.created', "Penawaran {$quotation->number}", null, $quotation, [
                'customer' => $customer->name,
            ]);

            return $quotation->load('items', 'customer');
        });
    }

    public function convertToOrder(SalesQuotation $quotation, ?int $createdBy = null): SalesQuotation
    {
        if ($quotation->isOrdered()) {
            throw new InvalidArgumentException('Penawaran sudah dikonversi menjadi pesanan.');
        }

        return DB::transaction(function () use ($quotation, $createdBy) {
            $quotation = SalesQuotation::query()->with('items')->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($quotation->isOrdered()) {
                throw new InvalidArgumentException('Penawaran sudah dikonversi menjadi pesanan.');
            }

            $items = $quotation->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'tax_amount' => (float) $i->tax_amount,
            ])->all();

            $order = $this->orders->create(
                company: $quotation->company,
                contactId: $quotation->contact_id,
                items: $items,
                date: now(),
                notes: "Dari penawaran {$quotation->number}",
                createdBy: $createdBy,
            );

            $quotation->forceFill(['status' => 'ordered', 'sales_order_id' => $order->id])->save();

            ActivityLogger::log('sales_quotation.ordered', "Penawaran {$quotation->number} jadi pesanan {$order->number}", null, $quotation, []);

            return $quotation->fresh(['items', 'customer', 'order']);
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = SalesQuotation::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('QUO/%s/%04d', $period, $sequence);
            $sequence++;
        } while (SalesQuotation::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
