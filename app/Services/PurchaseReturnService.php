<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Returns purchased goods to a supplier: reduces stock and inventory value,
 * reduces the payable.
 *
 *   Dr Hutang Usaha   Cr Persediaan
 */
class PurchaseReturnService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int|float, unit_cost?:float|int|null}>  $items
     */
    public function create(
        Purchase $purchase,
        array $items,
        Carbon|string|null $date = null,
        ?string $reason = null,
        ?int $createdBy = null,
    ): PurchaseReturn {
        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pilih minimal 1 item yang diretur.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($purchase, $items, $date, $reason, $createdBy) {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $company = $purchase->company;

            $return = PurchaseReturn::create([
                'company_id' => $company->id,
                'purchase_id' => $purchase->id,
                'contact_id' => $purchase->contact_id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'reason' => $reason,
                'created_by' => $createdBy,
                'posted_at' => now(),
            ]);

            $total = 0.0;

            foreach ($items as $line) {
                $product = Product::query()->lockForUpdate()->find($line['product_id']);

                if (! $product) {
                    throw new InvalidArgumentException('Produk retur tidak ditemukan.');
                }

                $quantity = (int) $line['quantity'];
                $unitCost = isset($line['unit_cost']) && $line['unit_cost'] !== null
                    ? round((float) $line['unit_cost'], 2)
                    : (float) $product->cost_price;
                $lineTotal = round($unitCost * $quantity, 2);

                if ($product->tracksStock()) {
                    if ($product->stock < $quantity) {
                        throw new InvalidArgumentException("Stok {$product->name} tidak cukup untuk diretur.");
                    }

                    $before = (int) $product->stock;
                    $product->decrement('stock', $quantity);

                    StockMovement::create([
                        'store_id' => $purchase->store_id ?? $product->store_id,
                        'product_id' => $product->id,
                        'user_id' => $createdBy,
                        'type' => 'purchase_return',
                        'quantity' => -$quantity,
                        'stock_before' => $before,
                        'stock_after' => $before - $quantity,
                        'reference_type' => PurchaseReturn::class,
                        'reference_id' => $return->id,
                        'notes' => "Retur pembelian {$return->number}",
                    ]);
                }

                $return->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $total = round($total, 2);
            $return->forceFill(['total' => $total])->save();

            // Reduce payable: lower outstanding on the purchase and supplier balance.
            $reduce = min($total, (float) $purchase->outstanding_amount);
            if ($reduce > 0) {
                $purchase->forceFill([
                    'outstanding_amount' => round((float) $purchase->outstanding_amount - $reduce, 2),
                ])->save();
            }
            $purchase->supplier?->forceFill([
                'payable_balance' => max(0, round((float) $purchase->supplier->payable_balance - $total, 2)),
            ])->save();

            if ($total > 0) {
                $this->posting->post(
                    company: $company,
                    date: $date,
                    lines: [
                        ['account_id' => $this->account($company, 'accounts_payable'), 'debit' => $total, 'contact_id' => $purchase->contact_id, 'memo' => 'Retur pembelian'],
                        ['account_id' => $this->account($company, 'inventory'), 'credit' => $total, 'memo' => 'Barang diretur ke supplier'],
                    ],
                    type: 'purchase',
                    description: "Retur pembelian {$return->number}",
                    reference: $return->number,
                    source: $return,
                    createdBy: $createdBy,
                );
            }

            ActivityLogger::log('purchase.returned', "Retur pembelian {$return->number}", $purchase->store_id, $return, [
                'total' => $total,
            ]);

            return $return->load('items', 'supplier');
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
        $sequence = PurchaseReturn::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('RTB/%s/%04d', $period, $sequence);
            $sequence++;
        } while (PurchaseReturn::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
