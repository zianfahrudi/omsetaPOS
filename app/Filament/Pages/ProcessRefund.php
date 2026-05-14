<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Sale;
use App\Services\RefundService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcessRefund extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptRefund;

    protected static ?string $navigationLabel = 'Refund';

    protected static ?string $title = 'Refund';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.process-refund';

    public string $saleNumber = '';

    public ?int $saleId = null;

    public string $type = 'partial';

    public string $reason = '';

    public string $productQuery = '';

    public float $additionalPaymentAmount = 0;

    /** @var array<int, int> */
    public array $returnQuantities = [];

    /** @var array<int, array{product_id:int, name:string, code:?string, price:float, stock:int, quantity:int}> */
    public array $replacementCart = [];

    public ?string $lastRefundNumber = null;

    public function findSale(): void
    {
        $sale = Sale::query()
            ->with('items')
            ->where('number', trim($this->saleNumber))
            ->first();

        if (! $sale) {
            Notification::make()->title('Transaksi tidak ditemukan')->danger()->send();
            $this->saleId = null;

            return;
        }

        $this->saleId = $sale->id;
        $this->returnQuantities = $sale->items
            ->mapWithKeys(fn ($item) => [$item->id => 0])
            ->all();
        $this->replacementCart = [];
        $this->lastRefundNumber = null;
    }

    public function sale(): ?Sale
    {
        if (! $this->saleId) {
            return null;
        }

        return Sale::query()
            ->with(['store', 'cashier', 'items'])
            ->find($this->saleId);
    }

    public function replacementResults(): Collection
    {
        $sale = $this->sale();

        if (! $sale || trim($this->productQuery) === '') {
            return collect();
        }

        $term = '%'.trim($this->productQuery).'%';

        return Product::query()
            ->where('store_id', $sale->store_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('barcode', 'like', $term))
            ->orderBy('name')
            ->limit(8)
            ->get();
    }

    public function addReplacement(int $productId): void
    {
        $sale = $this->sale();

        if (! $sale) {
            return;
        }

        $product = Product::query()
            ->where('store_id', $sale->store_id)
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            Notification::make()->title('Produk pengganti tidak valid')->danger()->send();

            return;
        }

        $currentQuantity = $this->replacementCart[$product->id]['quantity'] ?? 0;

        if ($currentQuantity + 1 > $product->stock) {
            Notification::make()->title("Stok {$product->name} tidak cukup")->danger()->send();

            return;
        }

        $this->replacementCart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'code' => $product->barcode ?: $product->sku,
            'price' => (float) $product->sell_price,
            'stock' => $product->stock,
            'quantity' => $currentQuantity + 1,
        ];

        $this->productQuery = '';
    }

    public function incrementReplacement(int $productId): void
    {
        if (! isset($this->replacementCart[$productId])) {
            return;
        }

        if ($this->replacementCart[$productId]['quantity'] >= $this->replacementCart[$productId]['stock']) {
            Notification::make()->title('Qty melebihi stok')->warning()->send();

            return;
        }

        $this->replacementCart[$productId]['quantity']++;
    }

    public function decrementReplacement(int $productId): void
    {
        if (! isset($this->replacementCart[$productId])) {
            return;
        }

        $this->replacementCart[$productId]['quantity']--;

        if ($this->replacementCart[$productId]['quantity'] <= 0) {
            unset($this->replacementCart[$productId]);
        }
    }

    public function returnedTotal(): float
    {
        $sale = $this->sale();

        if (! $sale) {
            return 0;
        }

        return $sale->items->sum(fn ($item) => (float) $item->unit_price * (int) ($this->returnQuantities[$item->id] ?? 0));
    }

    public function replacementTotal(): float
    {
        return collect($this->replacementCart)->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }

    public function refundAmount(): float
    {
        return max(0, $this->returnedTotal() - $this->replacementTotal());
    }

    public function expectedAdditionalPayment(): float
    {
        return max(0, $this->replacementTotal() - $this->returnedTotal());
    }

    public function process(): void
    {
        try {
            $returnedItems = collect($this->returnQuantities)
                ->map(fn ($quantity, $saleItemId) => ['sale_item_id' => (int) $saleItemId, 'quantity' => (int) $quantity])
                ->filter(fn (array $item) => $item['quantity'] > 0)
                ->values()
                ->all();

            $refund = app(RefundService::class)->refund(
                saleId: (int) $this->saleId,
                handledById: (int) Auth::id(),
                type: $this->type,
                returnedItems: $returnedItems,
                replacementItems: array_values($this->replacementCart),
                reason: $this->reason ?: null,
                additionalPaymentAmount: $this->additionalPaymentAmount,
            );

            $this->lastRefundNumber = $refund->number;
            $this->findSale();
            $this->lastRefundNumber = $refund->number;

            Notification::make()->title("Refund {$refund->number} selesai")->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
