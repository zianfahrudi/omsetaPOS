<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\SaleItem;
use Filament\Widgets\ChartWidget;

class SalesDistributionChart extends ChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $maxHeight = '280px';

    protected ?string $heading = 'Distribusi Penjualan per Kategori (Bulan Ini)';

    protected function getData(): array
    {
        $company = Company::query()->first();

        if (! $company) {
            return ['datasets' => [], 'labels' => []];
        }

        $storeIds = $company->stores()->pluck('id');

        $rows = SaleItem::query()
            ->selectRaw('coalesce(categories.name, ?) as label, sum(sale_items.line_total) as total', ['Tanpa Kategori'])
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('sales.store_id', $storeIds)
            ->whereBetween('sales.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'label');

        $palette = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'];

        return [
            'datasets' => [[
                'data' => $rows->values()->map(fn ($v) => round((float) $v, 2))->all(),
                'backgroundColor' => array_slice($palette, 0, $rows->count()),
            ]],
            'labels' => $rows->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
