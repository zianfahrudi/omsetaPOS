<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\StockMovement;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class StockCardReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Persediaan';

    protected static ?string $navigationLabel = 'Kartu Stok';

    protected static ?string $title = 'Kartu Stok';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.stock-card';

    public string $productId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->productId = (string) (Product::query()->where('product_type', '!=', 'service')->orderBy('name')->value('id') ?? '');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function products(): Collection
    {
        return Product::query()
            ->where('product_type', '!=', 'service')
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);
    }

    public function product(): ?Product
    {
        return Product::find($this->productId);
    }

    public function movements(): Collection
    {
        if ($this->productId === '') {
            return collect();
        }

        return StockMovement::query()
            ->where('product_id', $this->productId)
            ->whereBetween('created_at', [
                now()->parse($this->dateFrom)->startOfDay(),
                now()->parse($this->dateTo)->endOfDay(),
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function typeLabel(string $type): string
    {
        return [
            'sale' => 'Penjualan',
            'purchase' => 'Pembelian',
            'refund_return' => 'Retur Masuk',
            'refund_replacement' => 'Barang Pengganti',
            'adjustment' => 'Penyesuaian',
        ][$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
