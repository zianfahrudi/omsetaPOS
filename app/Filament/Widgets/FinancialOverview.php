<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Accounting\LedgerService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $company = Company::query()->first();

        if (! $company) {
            return [];
        }

        $ledger = app(LedgerService::class);

        $salesMonth = (float) Sale::query()
            ->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');

        $purchaseMonth = (float) Purchase::query()
            ->where('company_id', $company->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');

        $cashBank = Account::query()
            ->where('company_id', $company->id)
            ->whereIn('subtype', ['cash', 'bank'])
            ->get()
            ->sum(fn (Account $a) => $ledger->balance($a));

        $receivable = ($ar = $company->account('accounts_receivable')) ? $ledger->balance($ar) : 0.0;
        $payable = ($ap = $company->account('accounts_payable')) ? $ledger->balance($ap) : 0.0;

        return [
            Stat::make('Penjualan Bulan Ini', $this->rupiah($salesMonth))
                ->description('Total faktur penjualan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pembelian Bulan Ini', $this->rupiah($purchaseMonth))
                ->description('Total faktur pembelian')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),
            Stat::make('Saldo Kas & Bank', $this->rupiah($cashBank))
                ->description('Total seluruh akun kas & bank')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Piutang Usaha', $this->rupiah($receivable))
                ->description('Tagihan pelanggan belum terbayar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
            Stat::make('Hutang Usaha', $this->rupiah($payable))
                ->description('Kewajiban ke supplier')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('danger'),
        ];
    }

    private function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
