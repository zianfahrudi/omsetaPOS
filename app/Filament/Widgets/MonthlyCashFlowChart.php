<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Company;
use App\Models\JournalLine;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyCashFlowChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $maxHeight = '280px';

    protected ?string $heading = 'Arus Kas 6 Bulan';

    protected function getData(): array
    {
        $company = Company::query()->first();

        if (! $company) {
            return ['datasets' => [], 'labels' => []];
        }

        $cashIds = Account::query()
            ->where('company_id', $company->id)
            ->whereIn('subtype', ['cash', 'bank'])
            ->pluck('id');

        $labels = [];
        $inflow = [];
        $outflow = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $lines = JournalLine::query()
                ->whereIn('account_id', $cashIds)
                ->whereHas('journal', fn ($q) => $q
                    ->where('status', 'posted')
                    ->whereYear('date', $month->year)
                    ->whereMonth('date', $month->month));

            $inflow[] = round((float) (clone $lines)->sum('debit'), 2);
            $outflow[] = round((float) (clone $lines)->sum('credit'), 2);
        }

        return [
            'datasets' => [
                ['label' => 'Kas Masuk', 'data' => $inflow, 'backgroundColor' => 'rgba(16, 185, 129, 0.6)'],
                ['label' => 'Kas Keluar', 'data' => $outflow, 'backgroundColor' => 'rgba(239, 68, 68, 0.6)'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
