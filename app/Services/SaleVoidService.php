<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\Accounting\PostingService;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Voids (cancels) a completed POS sale: reverses its accounting journal,
 * returns sold goods back to stock, and reverts the customer's debt.
 * Blocked if the sale already has refunds or is already void.
 */
class SaleVoidService
{
    public function __construct(private readonly PostingService $posting) {}

    public function void(Sale $sale, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($sale, $userId) {
            $sale = Sale::query()->with('items')->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === 'void') {
                throw new InvalidArgumentException('Transaksi sudah dibatalkan.');
            }

            if ($sale->refunds()->exists()) {
                throw new InvalidArgumentException('Transaksi sudah memiliki refund, tidak bisa di-void. Gunakan refund.');
            }

            // 1. Reverse the accounting journal (if any).
            $journal = Journal::query()
                ->where('source_type', $sale->getMorphClass())
                ->where('source_id', $sale->getKey())
                ->where('type', 'sales')
                ->first();

            if ($journal) {
                $this->posting->reverse($journal, now()->toDateString(), "Pembatalan penjualan {$sale->number}");
            }

            // 2. Return goods to stock (net of any already-refunded qty).
            foreach ($sale->items as $item) {
                if ($item->product_type === 'service' || ! $item->product_id) {
                    continue;
                }

                $qty = (int) $item->quantity - (int) $item->refunded_quantity;
                if ($qty <= 0) {
                    continue;
                }

                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product) {
                    continue;
                }

                $before = (int) $product->stock;
                $product->increment('stock', $qty);
                app(WarehouseStockService::class)->adjustDefault($product, $qty);

                StockMovement::create([
                    'store_id' => $sale->store_id ?? $product->store_id,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'void',
                    'quantity' => $qty,
                    'stock_before' => $before,
                    'stock_after' => $before + $qty,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'notes' => "Pembatalan penjualan {$sale->number}",
                ]);
            }

            // 3. Revert customer debt for the unpaid (debt) portion.
            if ($sale->customer_id && (float) $sale->debt_amount > 0) {
                $customer = Customer::query()->lockForUpdate()->find($sale->customer_id);
                $customer?->forceFill([
                    'outstanding_debt' => max(0, round((float) $customer->outstanding_debt - (float) $sale->debt_amount, 2)),
                ])->save();
            }

            $sale->forceFill(['status' => 'void'])->save();

            ActivityLogger::log('sale.voided', "Pembatalan penjualan {$sale->number}", $sale->store_id, $sale, [
                'grand_total' => (float) $sale->grand_total,
            ]);

            return $sale->fresh();
        });
    }
}
