<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Per-warehouse stock tracking layer. products.stock remains the total across
 * all warehouses; this keeps the per-warehouse breakdown in sync and powers
 * inter-warehouse transfers.
 */
class WarehouseStockService
{
    /**
     * Apply a stock delta to a product's default warehouse. Called by every
     * stock-changing flow that does not specify a warehouse, so the sum of
     * warehouse_stocks always equals products.stock.
     */
    public function adjustDefault(Product $product, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $company = $product->loadMissing('store')->store?->company;
        $warehouse = $company?->defaultWarehouse();

        if (! $warehouse) {
            return;
        }

        $row = WarehouseStock::query()->firstOrCreate(
            ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
            ['quantity' => 0],
        );
        $row->increment('quantity', $delta);
    }

    public function quantity(int $warehouseId, int $productId): int
    {
        return (int) (WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);
    }

    /**
     * Move stock between two warehouses. Total company stock is unchanged, so
     * there is no journal entry.
     *
     * @param  array<int, array{product_id:int, quantity:int|float}>  $items
     */
    public function transfer(
        Company $company,
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        Carbon|string|null $date = null,
        ?string $notes = null,
        ?int $createdBy = null,
    ): StockTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('Gudang asal dan tujuan tidak boleh sama.');
        }

        $items = array_values(array_filter($items, fn ($i) => (int) ($i['quantity'] ?? 0) > 0));

        if ($items === []) {
            throw new InvalidArgumentException('Pilih minimal 1 item untuk dipindahkan.');
        }

        $this->assertWarehouse($company, $fromWarehouseId);
        $this->assertWarehouse($company, $toWarehouseId);

        $date = $date ? Carbon::parse($date) : now();

        return DB::transaction(function () use ($company, $fromWarehouseId, $toWarehouseId, $items, $date, $notes, $createdBy) {
            $transfer = StockTransfer::create([
                'company_id' => $company->id,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'number' => $this->number($company, $date),
                'date' => $date,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);

            foreach ($items as $line) {
                $product = Product::query()->find($line['product_id']);
                if (! $product) {
                    throw new InvalidArgumentException('Produk tidak ditemukan.');
                }

                $quantity = (int) $line['quantity'];
                $available = $this->quantity($fromWarehouseId, $product->id);

                if ($available < $quantity) {
                    throw new InvalidArgumentException("Stok {$product->name} di gudang asal tidak cukup.");
                }

                $this->moveRow($fromWarehouseId, $product->id, -$quantity);
                $this->moveRow($toWarehouseId, $product->id, $quantity);

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                ]);

                StockMovement::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $createdBy,
                    'type' => 'transfer_out',
                    'quantity' => -$quantity,
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock, // total unchanged
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Pindah ke gudang via {$transfer->number}",
                ]);
                StockMovement::create([
                    'store_id' => $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $createdBy,
                    'type' => 'transfer_in',
                    'quantity' => $quantity,
                    'stock_before' => $product->stock,
                    'stock_after' => $product->stock,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Terima dari gudang via {$transfer->number}",
                ]);
            }

            ActivityLogger::log('stock.transferred', "Transfer stok {$transfer->number}", null, $transfer, []);

            return $transfer->load('items', 'fromWarehouse', 'toWarehouse');
        });
    }

    private function moveRow(int $warehouseId, int $productId, int $delta): void
    {
        $row = WarehouseStock::query()->firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => 0],
        );
        $row->increment('quantity', $delta);
    }

    private function assertWarehouse(Company $company, int $warehouseId): void
    {
        $exists = Warehouse::query()->where('company_id', $company->id)->whereKey($warehouseId)->exists();

        if (! $exists) {
            throw new InvalidArgumentException('Gudang tidak ditemukan di perusahaan ini.');
        }
    }

    private function number(Company $company, Carbon $date): string
    {
        $period = $date->format('Ym');
        $sequence = StockTransfer::query()
            ->where('company_id', $company->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->count() + 1;

        do {
            $number = sprintf('TRF/%s/%04d', $period, $sequence);
            $sequence++;
        } while (StockTransfer::query()->where('company_id', $company->id)->where('number', $number)->exists());

        return $number;
    }
}
