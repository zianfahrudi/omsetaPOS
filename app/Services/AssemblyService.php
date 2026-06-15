<?php

namespace App\Services;

use App\Models\Assembly;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Assembly: consumes component stock and produces a finished good. Cost rolls
 * from components into the finished product (inventory value preserved, so no
 * journal). Weighted-average cost on the finished product.
 */
class AssemblyService
{
    public function __construct(private readonly WarehouseStockService $warehouse) {}

    /**
     * @param  array<int, array{product_id:int, quantity:int|float}>  $components
     */
    public function create(
        Company $company,
        int $finishedProductId,
        int $quantity,
        array $components,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): Assembly {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Jumlah produk jadi harus lebih dari nol.');
        }

        $components = array_values(array_filter($components, fn ($c) => (int) ($c['quantity'] ?? 0) > 0));
        if ($components === []) {
            throw new InvalidArgumentException('Perakitan harus punya minimal 1 komponen.');
        }

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $finishedProductId, $quantity, $components, $date, $notes, $createdBy) {
            $finished = Product::query()->lockForUpdate()->findOrFail($finishedProductId);

            if (! $finished->tracksStock()) {
                throw new InvalidArgumentException('Produk jadi harus berupa barang berstok.');
            }

            $assembly = Assembly::create([
                'company_id' => $company->id,
                'product_id' => $finished->id,
                'number' => $this->number($company, $date),
                'date' => $date,
                'quantity' => $quantity,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            $totalCost = 0.0;

            foreach ($components as $line) {
                $product = Product::query()->lockForUpdate()->find($line['product_id']);
                if (! $product || ! $product->tracksStock()) {
                    throw new InvalidArgumentException('Komponen tidak valid.');
                }
                if ($product->id === $finished->id) {
                    throw new InvalidArgumentException('Komponen tidak boleh sama dengan produk jadi.');
                }

                $qty = (int) $line['quantity'];
                if ($product->stock < $qty) {
                    throw new InvalidArgumentException("Stok komponen {$product->name} tidak cukup.");
                }

                $unitCost = (float) $product->cost_price;
                $lineTotal = round($unitCost * $qty, 2);
                $before = (int) $product->stock;

                $product->decrement('stock', $qty);
                $this->warehouse->adjustDefault($product, -$qty);

                StockMovement::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $createdBy,
                    'type' => 'assembly_out',
                    'quantity' => -$qty,
                    'stock_before' => $before,
                    'stock_after' => $before - $qty,
                    'reference_type' => Assembly::class,
                    'reference_id' => $assembly->id,
                    'notes' => "Komponen perakitan {$assembly->number}",
                ]);

                $assembly->components()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);

                $totalCost += $lineTotal;
            }

            $totalCost = round($totalCost, 2);

            // Roll cost into finished good (weighted average).
            $oldStock = (int) $finished->stock;
            $oldCost = (float) $finished->cost_price;
            $newStock = $oldStock + $quantity;
            $newCost = $newStock > 0
                ? round((($oldStock * $oldCost) + $totalCost) / $newStock, 2)
                : round($totalCost / $quantity, 2);

            $finished->forceFill(['stock' => $newStock, 'cost_price' => $newCost])->save();
            $this->warehouse->adjustDefault($finished, $quantity);

            StockMovement::create([
                'store_id' => $finished->store_id,
                'product_id' => $finished->id,
                'user_id' => $createdBy,
                'type' => 'assembly_in',
                'quantity' => $quantity,
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'reference_type' => Assembly::class,
                'reference_id' => $assembly->id,
                'notes' => "Hasil perakitan {$assembly->number}",
            ]);

            $assembly->forceFill(['total_cost' => $totalCost])->save();

            ActivityLogger::log('assembly.created', "Perakitan {$assembly->number}", null, $assembly, [
                'product' => $finished->name,
                'quantity' => $quantity,
                'total_cost' => $totalCost,
            ]);

            return $assembly->load('components', 'product');
        });
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = Assembly::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('ASM/%s/%04d', $period, $sequence);
            $sequence++;
        } while (Assembly::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
