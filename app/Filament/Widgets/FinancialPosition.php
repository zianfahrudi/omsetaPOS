<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Services\Accounting\ReportService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialPosition extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $company = Company::query()->first();

        if (! $company) {
            return [];
        }

        $bs = app(ReportService::class)->balanceSheet($company, now()->endOfDay());

        return [
            Stat::make('Total Aset', $this->rupiah($bs['total_assets']))
                ->description('Posisi per '.now()->format('d M Y'))
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),
            Stat::make('Total Liabilitas', $this->rupiah($bs['total_liabilities']))
                ->description('Seluruh kewajiban')
                ->descriptionIcon('heroicon-m-scale')
                ->color('danger'),
            Stat::make('Total Ekuitas', $this->rupiah($bs['total_equity']))
                ->description($bs['balanced'] ? 'Neraca seimbang' : 'Neraca tidak seimbang')
                ->descriptionIcon($bs['balanced'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($bs['balanced'] ? 'primary' : 'danger'),
        ];
    }

    private function rupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
