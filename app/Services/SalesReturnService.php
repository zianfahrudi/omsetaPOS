<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Customer returns goods against a sales invoice: stock returns, inventory and
 * COGS reverse, revenue is contra'd, and the receivable is reduced.
 *
 *   Dr Retur Penjualan   Cr Piutang Usaha
 *   Dr Persediaan        Cr HPP            (goods, at cost)
 */
class SalesReturnService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int|float, unit_price?:float|int|null, cost_price?:float|int|null}>  $items
     */
    public function create(
        SalesInvoice $invoice,
        array $items,
        Carbon|string|null $date = null,
        ?string $reason = null,
        ?int $createdBy = null,
    ): SalesReturn {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pilih minimal 1 item yang diretur.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($invoice, $items, $date, $reason, $createdBy) {
            $invoice = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $company = $invoice->company;

            $return = SalesReturn::create([
                'company_id' => $company->id,
                'sales_invoice_id' => $invoice->id,
                'contact_id' => $invoice->contact_id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'reason' => $reason,
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            $total = 0.0;
            $costTotal = 0.0;

            foreach ($items as $line) {
                $product = Product::query()->lockForUpdate()->find($line['product_id']);
                if (! $product) {
                    throw new InvalidArgumentException('Produk retur tidak ditemukan.');
                }

                $invoiceItem = $invoice->items()->where('product_id', $product->id)->first();
                $quantity = (int) $line['quantity'];
                $unitPrice = isset($line['unit_price']) && $line['unit_price'] !== null
                    ? round((float) $line['unit_price'], 2)
                    : (float) ($invoiceItem->unit_price ?? $product->sell_price);
                $cost = isset($line['cost_price']) && $line['cost_price'] !== null
                    ? round((float) $line['cost_price'], 2)
                    : (float) ($invoiceItem->cost_price ?? $product->cost_price);
                $lineTotal = round($unitPrice * $quantity, 2);

                if ($product->tracksStock()) {
                    $before = (int) $product->stock;
                    $product->increment('stock', $quantity);

                    app(WarehouseStockService::class)->adjustDefault($product, $quantity);

                    StockMovement::create([
                        'store_id' => $invoice->store_id ?? $product->store_id,
                        'product_id' => $product->id,
                        'user_id' => $createdBy,
                        'type' => 'sales_return',
                        'quantity' => $quantity,
                        'stock_before' => $before,
                        'stock_after' => $before + $quantity,
                        'reference_type' => SalesReturn::class,
                        'reference_id' => $return->id,
                        'notes' => "Retur penjualan {$return->number}",
                    ]);

                    $costTotal += $cost * $quantity;
                }

                $return->items()->create([
                    'sales_invoice_item_id' => $invoiceItem?->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'cost_price' => $cost,
                    'line_total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $total = round($total, 2);
            $costTotal = round($costTotal, 2);
            $return->forceFill(['total' => $total])->save();

            $reduce = min($total, (float) $invoice->outstanding_amount);
            if ($reduce > 0) {
                $invoice->forceFill([
                    'outstanding_amount' => round((float) $invoice->outstanding_amount - $reduce, 2),
                ])->save();
            }
            $invoice->customer?->forceFill([
                'receivable_balance' => max(0, round((float) $invoice->customer->receivable_balance - $total, 2)),
            ])->save();

            if ($total > 0) {
                $lines = [
                    ['account_id' => $this->account($company, 'sales_return'), 'debit' => $total, 'memo' => 'Retur penjualan'],
                    ['account_id' => $this->account($company, 'accounts_receivable'), 'credit' => $total, 'contact_id' => $invoice->contact_id, 'memo' => 'Pengurangan piutang'],
                ];

                if ($costTotal > 0) {
                    $lines[] = ['account_id' => $this->account($company, 'inventory'), 'debit' => $costTotal, 'memo' => 'Barang kembali ke stok'];
                    $lines[] = ['account_id' => $this->account($company, 'cogs'), 'credit' => $costTotal, 'memo' => 'Pembalikan HPP retur'];
                }

                $this->posting->post(
                    company: $company,
                    date: $date,
                    lines: $lines,
                    type: 'sales',
                    description: "Retur penjualan {$return->number}",
                    reference: $return->number,
                    source: $return,
                    createdBy: $createdBy,
                );
            }

            ActivityLogger::log('sales.returned', "Retur penjualan {$return->number}", $invoice->store_id, $return, [
                'total' => $total,
            ]);

            return $return->load('items', 'customer');
        });
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
        $sequence = SalesReturn::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('RTJ/%s/%04d', $period, $sequence);
            $sequence++;
        } while (SalesReturn::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
