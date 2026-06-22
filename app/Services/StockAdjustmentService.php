<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Adjusts product stock to a counted/actual quantity (stock opname, damage,
 * loss, correction) and posts the inventory variance journal.
 *
 *   gain: Dr Persediaan        Cr Pendapatan Lain
 *   loss: Dr Beban (selisih)   Cr Persediaan
 */
class StockAdjustmentService
{
    public function __construct(private readonly PostingService $posting) {}

    public function adjust(
        Company $company,
        int $productId,
        int $quantityAfter,
        string $reason = 'opname',
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): StockAdjustment {
        if ($quantityAfter < 0) {
            throw new InvalidArgumentException('Jumlah aktual tidak boleh negatif.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $productId, $quantityAfter, $reason, $date, $notes, $createdBy) {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);

            if (! $product->tracksStock()) {
                throw new InvalidArgumentException('Produk jasa tidak memiliki stok untuk disesuaikan.');
            }

            $before = (int) $product->stock;
            $difference = $quantityAfter - $before;
            $unitCost = (float) $product->cost_price;
            $value = round(abs($difference) * $unitCost, 2);

            $adjustment = StockAdjustment::create([
                'company_id' => $company->id,
                'store_id' => $product->store_id,
                'warehouse_id' => $company->defaultWarehouse()?->id,
                'product_id' => $product->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'reason' => $reason,
                'quantity_before' => $before,
                'quantity_after' => $quantityAfter,
                'difference' => $difference,
                'unit_cost' => $unitCost,
                'value' => $value,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            if ($difference !== 0) {
                $product->forceFill(['stock' => $quantityAfter])->save();

                app(WarehouseStockService::class)->adjustDefault($product, $difference);

                StockMovement::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $createdBy,
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'stock_before' => $before,
                    'stock_after' => $quantityAfter,
                    'reference_type' => StockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'notes' => "Penyesuaian {$adjustment->number} ({$reason})",
                ]);

                if ($value > 0) {
                    $this->postJournal($company, $adjustment, $difference, $value);
                }
            }

            ActivityLogger::log('stock.adjusted', "Penyesuaian stok {$adjustment->number}", $product->store_id, $adjustment, [
                'product' => $product->name,
                'difference' => $difference,
                'value' => $value,
            ]);

            return $adjustment;
        });
    }

    private function postJournal(Company $company, StockAdjustment $adjustment, int $difference, float $value): void
    {
        $inventory = $this->account($company, 'inventory');

        if ($difference > 0) {
            // Stock gain: inventory up, recognise as other income.
            $lines = [
                ['account_id' => $inventory, 'debit' => $value, 'memo' => 'Penyesuaian stok (lebih)'],
                ['account_id' => $this->account($company, 'other_income'), 'credit' => $value, 'memo' => 'Selisih persediaan'],
            ];
        } else {
            // Stock loss: expense up, inventory down.
            $lines = [
                ['account_id' => $this->account($company, 'operating_expense'), 'debit' => $value, 'memo' => 'Selisih persediaan'],
                ['account_id' => $inventory, 'credit' => $value, 'memo' => 'Penyesuaian stok (kurang)'],
            ];
        }

        $this->posting->post(
            company: $company,
            date: $adjustment->date,
            lines: $lines,
            type: 'inventory',
            description: "Penyesuaian stok {$adjustment->number}",
            reference: $adjustment->number,
            source: $adjustment,
            createdBy: $adjustment->created_by,
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
        $sequence = StockAdjustment::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('ADJ/%s/%04d', $period, $sequence);
            $sequence++;
        } while (StockAdjustment::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
