<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class PaymentMethodStats extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Distribusi Metode Pembayaran (30 Hari)';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = Sale::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('payment_method, count(*) as count')
            ->groupBy('payment_method')
            ->pluck('count', 'payment_method');

        $labels = [
            'cash' => 'Tunai',
            'qris' => 'QRIS / Transfer',
            'debt' => 'Hutang',
        ];

        $datasetData = [];
        $datasetLabels = [];
        $colors = [
            'cash' => '#10b981',
            'qris' => '#3b82f6',
            'debt' => '#ef4444',
        ];
        $backgroundColors = [];

        foreach ($labels as $key => $label) {
            $datasetLabels[] = $label;
            $datasetData[] = $data[$key] ?? 0;
            $backgroundColors[] = $colors[$key];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $datasetData,
                    'backgroundColor' => $backgroundColors,
                ],
            ],
            'labels' => $datasetLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
