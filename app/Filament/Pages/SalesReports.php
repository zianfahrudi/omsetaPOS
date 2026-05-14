<?php

namespace App\Filament\Pages;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SalesReports extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Report';

    protected static ?string $title = 'Report Penjualan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.sales-reports';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $paymentMethod = '';

    public string $storeId = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function stores(): Collection
    {
        $user = Auth::user();

        if ($user?->isSuperuser()) {
            return Store::query()->orderBy('name')->get();
        }

        return $user?->stores()->orderBy('name')->get() ?? collect();
    }

    public function query(): Builder
    {
        return Sale::query()
            ->with(['store', 'cashier'])
            ->when($this->storeId !== '', fn (Builder $query) => $query->where('store_id', $this->storeId))
            ->when($this->paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $this->paymentMethod))
            ->whereBetween('created_at', [
                now()->parse($this->dateFrom)->startOfDay(),
                now()->parse($this->dateTo)->endOfDay(),
            ]);
    }

    public function totals(): array
    {
        $sales = (clone $this->query())->get();
        $refunds = $this->refundQuery()->get();
        $refundAdjustment = (float) $refunds->sum('refund_amount') - (float) $refunds->sum('additional_payment_amount');
        $netRevenue = (float) $sales->sum('grand_total') - $refundAdjustment;

        return [
            'revenue' => $netRevenue,
            'paid' => (float) $sales->sum('paid_amount'),
            'debt' => (float) $sales->sum('debt_amount'),
            'transactions' => $sales->count(),
            'average' => $sales->count() > 0 ? $netRevenue / $sales->count() : 0,
            'refunds' => $refunds->count(),
        ];
    }

    public function storeBreakdown(): Collection
    {
        $refundsByStore = $this->refundQuery()
            ->selectRaw('store_id, sum(refund_amount) - sum(additional_payment_amount) as adjustment')
            ->groupBy('store_id')
            ->pluck('adjustment', 'store_id');

        return (clone $this->query())
            ->selectRaw('store_id, count(*) as transactions, sum(grand_total) as revenue, sum(debt_amount) as debt')
            ->groupBy('store_id')
            ->with('store')
            ->orderByDesc('revenue')
            ->get()
            ->map(function (Sale $row) use ($refundsByStore) {
                $row->revenue = (float) $row->revenue - (float) ($refundsByStore[$row->store_id] ?? 0);

                return $row;
            })
            ->sortByDesc('revenue')
            ->values();
    }

    public function recentSales(): Collection
    {
        return (clone $this->query())
            ->latest()
            ->limit(12)
            ->get();
    }

    public function rupiah(float|int|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    private function refundQuery(): Builder
    {
        return Refund::query()
            ->when($this->storeId !== '', fn (Builder $query) => $query->where('store_id', $this->storeId))
            ->when($this->paymentMethod !== '', fn (Builder $query) => $query->whereHas('sale', fn (Builder $query) => $query->where('payment_method', $this->paymentMethod)))
            ->whereBetween('created_at', [
                now()->parse($this->dateFrom)->startOfDay(),
                now()->parse($this->dateTo)->endOfDay(),
            ]);
    }
}
