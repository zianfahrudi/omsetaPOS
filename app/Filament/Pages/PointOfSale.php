<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Services\CheckoutService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PointOfSale extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Kasir';

    protected static ?string $title = 'Kasir';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.point-of-sale';

    public ?int $storeId = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $productQuery = '';

    public string $scanCode = '';

    public string $paymentMethod = 'cash';

    public float $paidAmount = 0;

    /** @var array<int, array{product_id:int, name:string, code:?string, image_url:?string, product_type:string, price:float, stock:int, quantity:int}> */
    public array $cart = [];

    public ?string $lastSaleNumber = null;

    public function mount(): void
    {
        $this->storeId = $this->stores()->first()?->id;
    }

    public function stores(): Collection
    {
        $user = Auth::user();

        if ($user?->isSuperuser()) {
            return Store::query()->where('is_active', true)->orderBy('name')->get();
        }

        return $user?->stores()->where('stores.is_active', true)->orderBy('name')->get() ?? collect();
    }

    public function products(): Collection
    {
        if (! $this->storeId) {
            return collect();
        }

        $term = trim($this->productQuery);

        return Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';

                $query->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like));
            })
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    public function updatedStoreId(): void
    {
        $this->resetCart();
    }

    public function scanProduct(): void
    {
        if (! $this->storeId || trim($this->scanCode) === '') {
            return;
        }

        $code = trim($this->scanCode);
        $product = Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            Notification::make()->title('Produk tidak ditemukan')->danger()->send();

            return;
        }

        $this->addProduct($product->id);
        $this->scanCode = '';
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()
            ->where('store_id', $this->storeId)
            ->where('is_active', true)
            ->find($productId);

        if (! $product) {
            Notification::make()->title('Produk tidak valid')->danger()->send();

            return;
        }

        $currentQuantity = $this->cart[$product->id]['quantity'] ?? 0;

        if ($product->tracksStock() && $currentQuantity + 1 > $product->stock) {
            Notification::make()->title("Stok {$product->name} tidak cukup")->danger()->send();

            return;
        }

        $this->cart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'code' => $product->barcode ?: $product->sku,
            'image_url' => $product->image_url,
            'product_type' => $product->product_type,
            'price' => $product->unitSalePrice(),
            'stock' => $product->stock,
            'quantity' => $currentQuantity + 1,
        ];

        $this->productQuery = '';
    }

    public function incrementItem(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if (($this->cart[$productId]['product_type'] ?? 'goods') !== 'service' && $this->cart[$productId]['quantity'] >= $this->cart[$productId]['stock']) {
            Notification::make()->title('Qty melebihi stok')->warning()->send();

            return;
        }

        $this->cart[$productId]['quantity']++;
    }

    public function decrementItem(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantity']--;

        if ($this->cart[$productId]['quantity'] <= 0) {
            unset($this->cart[$productId]);
        }
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function resetCart(): void
    {
        $this->cart = [];
        $this->paidAmount = 0;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->lastSaleNumber = null;
    }

    public function subtotal(): float
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }

    public function changeAmount(): float
    {
        if ($this->paymentMethod === 'qris') {
            return 0;
        }

        return max(0, $this->paidAmount - $this->subtotal());
    }

    public function checkout(): void
    {
        try {
            /** @var Sale $sale */
            $sale = app(CheckoutService::class)->checkout(
                storeId: (int) $this->storeId,
                cashierId: (int) Auth::id(),
                items: array_values($this->cart),
                paymentMethod: $this->paymentMethod,
                paidAmount: $this->paymentMethod === 'qris' ? $this->subtotal() : $this->paidAmount,
                customerName: $this->customerName ?: null,
                customerPhone: $this->customerPhone ?: null,
            );

            $this->lastSaleNumber = $sale->number;
            $this->cart = [];
            $this->paidAmount = 0;
            $this->customerName = '';
            $this->customerPhone = '';

            Notification::make()->title("Order {$sale->number} selesai")->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        }
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
