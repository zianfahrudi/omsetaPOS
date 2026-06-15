<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Accounting\RefundPoster;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RefundService
{
    public function __construct(private readonly RefundPoster $refundPoster) {}

    /**
     * @param  array<int, array{sale_item_id:int, quantity:int}>  $returnedItems
     * @param  array<int, array{product_id:int, quantity:int}>  $replacementItems
     */
    public function refund(
        int $saleId,
        int $handledById,
        string $type,
        array $returnedItems,
        array $replacementItems = [],
        ?string $reason = null,
        float $additionalPaymentAmount = 0,
        array $evidencePhotos = [],
    ): Refund {
        if ($returnedItems === []) {
            throw new InvalidArgumentException('Pilih minimal 1 item yang dikembalikan.');
        }

        return DB::transaction(function () use (
            $saleId,
            $handledById,
            $type,
            $returnedItems,
            $replacementItems,
            $reason,
            $additionalPaymentAmount,
            $evidencePhotos,
        ) {
            $sale = Sale::with('items')->lockForUpdate()->findOrFail($saleId);
            $returns = collect($returnedItems)
                ->groupBy('sale_item_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'))
                ->filter(fn (int $quantity) => $quantity > 0);

            $saleItems = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->whereIn('id', $returns->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($saleItems->count() !== $returns->count()) {
                throw new InvalidArgumentException('Item refund tidak ditemukan pada transaksi ini.');
            }

            $returnedTotal = 0.0;

            foreach ($returns as $saleItemId => $quantity) {
                $saleItem = $saleItems[$saleItemId];
                $availableQuantity = $saleItem->quantity - $saleItem->refunded_quantity;

                if ($quantity > $availableQuantity) {
                    throw new InvalidArgumentException("Qty refund {$saleItem->product_name} melebihi pembelian.");
                }

                $returnedTotal += (float) $saleItem->unit_price * $quantity;
            }

            $replacements = collect($replacementItems)
                ->groupBy('product_id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'))
                ->filter(fn (int $quantity) => $quantity > 0);

            $products = Product::query()
                ->where('store_id', $sale->store_id)
                ->whereIn('id', $replacements->keys()->merge($saleItems->pluck('product_id')->filter())->unique())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $replacementTotal = 0.0;

            foreach ($replacements as $productId => $quantity) {
                $product = $products[$productId] ?? null;

                if (! $product || ! $product->is_active) {
                    throw new InvalidArgumentException('Produk pengganti tidak valid.');
                }

                if ($product->tracksStock() && $product->stock < $quantity) {
                    throw new InvalidArgumentException("Stok {$product->name} tidak cukup untuk pengganti.");
                }

                $replacementTotal += $product->unitSalePrice() * $quantity;
            }

            $difference = $returnedTotal - $replacementTotal;
            $refundAmount = max(0, $difference);
            $expectedAdditional = max(0, -$difference);

            if ($additionalPaymentAmount < $expectedAdditional) {
                throw new InvalidArgumentException('Tambahan pembayaran untuk barang pengganti masih kurang.');
            }

            $refund = Refund::create([
                'store_id' => $sale->store_id,
                'sale_id' => $sale->id,
                'handled_by_id' => $handledById,
                'number' => $this->number(),
                'type' => $type,
                'status' => 'completed',
                'reason' => $reason,
                'evidence_photos' => $evidencePhotos,
                'returned_total' => $returnedTotal,
                'replacement_total' => $replacementTotal,
                'refund_amount' => $refundAmount,
                'additional_payment_amount' => $expectedAdditional,
            ]);

            foreach ($returns as $saleItemId => $quantity) {
                $saleItem = $saleItems[$saleItemId];
                $lineTotal = (float) $saleItem->unit_price * $quantity;

                $refund->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'direction' => 'returned',
                    'product_name' => $saleItem->product_name,
                    'product_code' => $saleItem->product_code,
                    'quantity' => $quantity,
                    'unit_price' => $saleItem->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $saleItem->increment('refunded_quantity', $quantity);

                if ($saleItem->product_id && $products->has($saleItem->product_id) && $saleItem->product_type !== 'service') {
                    $product = $products[$saleItem->product_id];
                    $stockBefore = $product->stock;
                    $product->increment('stock', $quantity);
                    $product->refresh();

                    app(\App\Services\WarehouseStockService::class)->adjustDefault($product, $quantity);

                    StockMovement::create([
                        'store_id' => $sale->store_id,
                        'product_id' => $product->id,
                        'user_id' => $handledById,
                        'type' => 'refund_return',
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->stock,
                        'reference_type' => Refund::class,
                        'reference_id' => $refund->id,
                        'notes' => "Barang kembali dari refund {$refund->number}",
                    ]);
                }
            }

            foreach ($replacements as $productId => $quantity) {
                $product = $products[$productId];
                $stockBefore = $product->stock;
                $unitPrice = $product->unitSalePrice();
                $lineTotal = $unitPrice * $quantity;

                $refund->items()->create([
                    'product_id' => $product->id,
                    'direction' => 'replacement',
                    'product_name' => $product->name,
                    'product_code' => $product->barcode ?: $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                if ($product->tracksStock()) {
                    $product->decrement('stock', $quantity);
                    $product->refresh();

                    app(\App\Services\WarehouseStockService::class)->adjustDefault($product, -$quantity);

                    StockMovement::create([
                        'store_id' => $sale->store_id,
                        'product_id' => $product->id,
                        'user_id' => $handledById,
                        'type' => 'refund_replacement',
                        'quantity' => -$quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $product->stock,
                        'reference_type' => Refund::class,
                        'reference_id' => $refund->id,
                        'notes' => "Barang pengganti refund {$refund->number}",
                    ]);
                }
            }

            $sale->load('items');
            $allRefunded = $sale->items->every(fn (SaleItem $item) => $item->refunded_quantity >= $item->quantity);
            $sale->update(['status' => $allRefunded ? 'refunded' : 'partially_refunded']);

            ActivityLogger::log('refund.completed', "Refund {$refund->number} diproses", $sale->store_id, $refund, [
                'sale_number' => $sale->number,
                'refund_amount' => $refundAmount,
                'additional_payment_amount' => $expectedAdditional,
            ]);

            $this->refundPoster->post($refund);

            return $refund->load(['items', 'sale', 'handledBy', 'store']);
        });
    }

    private function number(): string
    {
        do {
            $number = 'RFN-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(4));
        } while (Refund::where('number', $number)->exists());

        return $number;
    }
}
