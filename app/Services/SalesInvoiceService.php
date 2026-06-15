<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Credit sales invoice (non-POS): bills a customer, reduces stock for goods
 * (with COGS), books the receivable, and posts the journal.
 *
 *   Dr Piutang Usaha   Cr Penjualan / PPN Keluaran
 *   Dr HPP             Cr Persediaan   (goods)
 */
class SalesInvoiceService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @param  array<int, array{product_id?:int|null, product_name?:string|null, line_type?:string, quantity:int|float, unit_price:float|int, tax_amount?:float|int}>  $items
     */
    public function create(
        Company $company,
        int $contactId,
        array $items,
        Carbon|string|null $date = null,
        ?int $warehouseId = null,
        ?int $storeId = null,
        ?string $customerRef = null,
        Carbon|string|null $dueDate = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): SalesInvoice {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Faktur penjualan harus memiliki minimal 1 item.');
        }

        $customer = Contact::query()
            ->where('company_id', $company->id)
            ->whereKey($contactId)
            ->firstOr(fn () => throw new InvalidArgumentException('Pelanggan tidak ditemukan.'));

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $customer, $items, $date, $warehouseId, $storeId, $customerRef, $dueDate, $notes, $createdBy) {
            $invoice = SalesInvoice::create([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'warehouse_id' => $warehouseId ?? $company->defaultWarehouse()?->id,
                'store_id' => $storeId,
                'number' => $this->number($company, $date),
                'customer_ref' => $customerRef,
                'date' => $date,
                'due_date' => $dueDate ? Carbon::parse($dueDate) : null,
                'status' => 'posted',
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;
            $cogsTotal = 0.0;

            foreach ($items as $line) {
                $product = isset($line['product_id']) && $line['product_id'] ? Product::find($line['product_id']) : null;
                $quantity = (int) $line['quantity'];
                $unitPrice = round((float) $line['unit_price'], 2);
                $taxAmount = round((float) ($line['tax_amount'] ?? 0), 2);
                $lineTotal = round($unitPrice * $quantity, 2);
                $isGoods = $product && $product->tracksStock() && ($line['line_type'] ?? 'goods') !== 'service';
                $cost = $isGoods ? (float) $product->cost_price : 0.0;

                if ($isGoods && $product->stock < $quantity) {
                    throw new InvalidArgumentException("Stok {$product->name} tidak cukup.");
                }

                $invoice->items()->create([
                    'product_id' => $product?->id,
                    'product_name' => $line['product_name'] ?? $product?->name ?? 'Item',
                    'line_type' => $isGoods ? 'goods' : 'service',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $cost,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;

                if ($isGoods) {
                    $cogsTotal += $cost * $quantity;
                    $this->releaseStock($product, $quantity, $invoice, $createdBy);
                }
            }

            $subtotal = round($subtotal, 2);
            $taxTotal = round($taxTotal, 2);
            $grandTotal = round($subtotal + $taxTotal, 2);

            $invoice->forceFill([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'outstanding_amount' => $grandTotal,
            ])->save();

            $customer->increment('receivable_balance', $grandTotal);

            $this->postJournal($company, $invoice, $subtotal, $taxTotal, round($cogsTotal, 2));

            ActivityLogger::log('sales_invoice.created', "Faktur penjualan {$invoice->number}", $storeId, $invoice, [
                'customer' => $customer->name,
                'grand_total' => $grandTotal,
            ]);

            return $invoice->load('items', 'customer');
        });
    }

    private function releaseStock(Product $product, int $quantity, SalesInvoice $invoice, ?int $userId): void
    {
        $before = (int) $product->stock;
        $product->decrement('stock', $quantity);

        app(\App\Services\WarehouseStockService::class)->adjustDefault($product, -$quantity);

        StockMovement::create([
            'store_id' => $invoice->store_id ?? $product->store_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => 'sale',
            'quantity' => -$quantity,
            'stock_before' => $before,
            'stock_after' => $before - $quantity,
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'notes' => "Faktur penjualan {$invoice->number}",
        ]);
    }

    private function postJournal(Company $company, SalesInvoice $invoice, float $subtotal, float $tax, float $cogs): void
    {
        $lines = [
            ['account_id' => $this->account($company, 'accounts_receivable'), 'debit' => (float) $invoice->grand_total, 'contact_id' => $invoice->contact_id, 'memo' => 'Piutang penjualan'],
            ['account_id' => $this->account($company, 'sales'), 'credit' => $subtotal, 'memo' => 'Penjualan'],
        ];

        if ($tax > 0) {
            $lines[] = ['account_id' => $this->account($company, 'tax_output'), 'credit' => $tax, 'memo' => 'PPN Keluaran'];
        }

        if ($cogs > 0) {
            $lines[] = ['account_id' => $this->account($company, 'cogs'), 'debit' => $cogs, 'memo' => 'Harga pokok penjualan'];
            $lines[] = ['account_id' => $this->account($company, 'inventory'), 'credit' => $cogs, 'memo' => 'Pengurangan persediaan'];
        }

        $this->posting->post(
            company: $company,
            date: $invoice->date,
            lines: $lines,
            type: 'sales',
            description: "Faktur penjualan {$invoice->number}",
            reference: $invoice->number,
            source: $invoice,
            createdBy: $invoice->created_by,
        );
    }

    private function account(Company $company, string $subtype): int
    {
        $account = $company->account($subtype);

        if (! $account) {
            throw new InvalidArgumentException("Akun sistem '{$subtype}' belum dikonfigurasi.");
        }

        return $account->id;
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = SalesInvoice::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('INV/%s/%04d', $period, $sequence);
            $sequence++;
        } while (SalesInvoice::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
