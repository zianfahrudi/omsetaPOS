<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $todayRevenue = (float) Sale::query()
            ->whereDate('created_at', today())
            ->sum('grand_total');
        $todayRefundAdjustment = (float) Refund::query()
            ->whereDate('created_at', today())
            ->selectRaw('coalesce(sum(refund_amount), 0) - coalesce(sum(additional_payment_amount), 0) as total')
            ->value('total');

        $monthRevenue = (float) Sale::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');
        $monthRefundAdjustment = (float) Refund::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('coalesce(sum(refund_amount), 0) - coalesce(sum(additional_payment_amount), 0) as total')
            ->value('total');

        $outstandingDebt = (float) Customer::query()->sum('outstanding_debt');

        $todayTransactions = Sale::query()->whereDate('created_at', today())->count();
        $monthTransactions = Sale::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        return [
            Stat::make('Omzet Hari Ini', 'Rp '.number_format($todayRevenue - $todayRefundAdjustment, 0, ',', '.'))
                ->description('Total pendapatan bersih hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            Stat::make('Omzet Bulan Ini', 'Rp '.number_format($monthRevenue - $monthRefundAdjustment, 0, ',', '.'))
                ->description('Total pendapatan bersih bulan ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->chart([15, 4, 10, 2, 12, 4, 12]),
            Stat::make('Hutang Customer', 'Rp '.number_format($outstandingDebt, 0, ',', '.'))
                ->description('Total piutang yang belum terbayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
            Stat::make('Transaksi Hari Ini', $todayTransactions)
                ->description($monthTransactions . ' total bulan ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
            Stat::make('Refund Bulan Ini', Refund::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count())
                ->description('Jumlah transaksi yang direfund')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),
            Stat::make('Stok Rendah', Product::query()->whereColumn('stock', '<=', 'minimum_stock')->count())
                ->description('Produk yang perlu restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color(Product::query()->whereColumn('stock', '<=', 'minimum_stock')->count() > 0 ? 'danger' : 'success'),
        ];
    }
}
