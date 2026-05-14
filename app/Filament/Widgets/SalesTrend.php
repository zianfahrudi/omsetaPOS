<?php

namespace App\Filament\Widgets;

use App\Models\Refund;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;

class SalesTrend extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    protected ?string $maxHeight = '300px';

    protected ?string $heading = 'Tren Omzet 14 Hari';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString());
        $sales = Sale::query()
            ->selectRaw('date(created_at) as sale_date, sum(grand_total) as revenue')
            ->whereDate('created_at', '>=', $days->first())
            ->groupBy('sale_date')
            ->pluck('revenue', 'sale_date');
        $refundAdjustments = Refund::query()
            ->selectRaw('date(created_at) as refund_date, sum(refund_amount) - sum(additional_payment_amount) as adjustment')
            ->whereDate('created_at', '>=', $days->first())
            ->groupBy('refund_date')
            ->pluck('adjustment', 'refund_date');

        return [
            'datasets' => [
                [
                    'label' => 'Omzet',
                    'data' => $days->map(fn (string $day) => (float) ($sales[$day] ?? 0) - (float) ($refundAdjustments[$day] ?? 0))->all(),
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'fill' => 'start',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn (string $day) => now()->parse($day)->format('d M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
