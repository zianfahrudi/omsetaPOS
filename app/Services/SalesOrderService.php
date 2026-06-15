<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Sales order pipeline document (no ledger impact). Can be converted into a
 * posted sales invoice via SalesInvoiceService.
 */
class SalesOrderService
{
    public function __construct(private readonly SalesInvoiceService $invoices) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, line_type?:string, quantity:int|float, unit_price:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        Carbon|string|null $expectedDate = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): SalesOrder {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pesanan harus memiliki minimal 1 item.');
        }

        $customer = Contact::query()
            ->where('company_id', $company->id)
            ->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Pelanggan tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $customer, $items, $date, $expectedDate, $notes, $createdBy) {
            $order = SalesOrder::create([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
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
                $unitPrice = round((float) $line['unit_price'], 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitPrice * $quantity, 2);

                $order->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'line_type' => $product && ! $product->tracksStock() ? 'service' : 'goods',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
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

            ActivityLogger::log('sales_order.created', "Pesanan penjualan {$order->number}", null, $order, [
                'customer' => $customer->name,
                'grand_total' => $order->grand_total,
            ]);

            return $order->load('items', 'customer');
        });
    }

    public function convertToInvoice(SalesOrder $order, ?int $createdBy = null): SalesOrder
    {
        if ($order->isInvoiced()) {
            throw new InvalidArgumentException('Pesanan sudah dikonversi menjadi faktur.');
        }

        return DB::transaction(function () use ($order, $createdBy) {
            $order = SalesOrder::query()->with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->isInvoiced()) {
                throw new InvalidArgumentException('Pesanan sudah dikonversi menjadi faktur.');
            }

            $items = $order->items->map(fn ($i) => [
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'line_type' => $i->line_type,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'tax_amount' => (float) $i->tax_amount,
            ])->all();

            $invoice = $this->invoices->create(
                company: $order->company,
                contactId: $order->contact_id,
                items: $items,
                date: now(),
                notes: "Dari pesanan {$order->number}",
                createdBy: $createdBy,
            );

            $order->forceFill([
                'status' => 'invoiced',
                'sales_invoice_id' => $invoice->id,
            ])->save();

            ActivityLogger::log('sales_order.invoiced', "Pesanan {$order->number} jadi faktur {$invoice->number}", null, $order, [
                'invoice' => $invoice->number,
            ]);

            return $order->fresh(['items', 'customer', 'invoice']);
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = SalesOrder::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('SO/%s/%04d', $period, $sequence);
            $sequence++;
        } while (SalesOrder::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
