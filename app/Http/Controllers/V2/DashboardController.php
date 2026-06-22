<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(LedgerService $ledger, ReportService $reports): View
    {
        $company = Company::query()->first();

        $metrics = [
            'sales' => 0.0, 'purchases' => 0.0, 'cash_bank' => 0.0,
            'receivable' => 0.0, 'payable' => 0.0,
        ];
        $balanceSheet = ['total_assets' => 0, 'total_liabilities' => 0, 'total_equity' => 0, 'balanced' => true];

        if ($company) {
            $metrics['sales'] = (float) Sale::query()
                ->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('grand_total');
            $metrics['purchases'] = (float) Purchase::query()
                ->where('company_id', $company->id)
                ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('grand_total');
            $metrics['cash_bank'] = (float) Account::query()
                ->where('company_id', $company->id)
                ->whereIn('subtype', ['cash', 'bank'])
                ->get()
                ->sum(fn (Account $a) => $ledger->balance($a));
            $metrics['receivable'] = ($ar = $company->account('accounts_receivable')) ? $ledger->balance($ar) : 0.0;
            $metrics['payable'] = ($ap = $company->account('accounts_payable')) ? $ledger->balance($ap) : 0.0;

            $balanceSheet = $reports->balanceSheet($company, now()->endOfDay());
        }

        $recentSales = Sale::query()->with('store')->latest()->limit(8)->get();

        $trend = $this->salesTrend($company);
        $distribution = $this->salesDistribution($company);

        return view('v2.dashboard', compact('company', 'metrics', 'balanceSheet', 'recentSales', 'trend', 'distribution'));
    }

    /**
     * Penjualan vs Pembelian 6 bulan terakhir.
     *
     * @return array{labels: array<int, string>, sales: array<int, float>, purchases: array<int, float>}
     */
    private function salesTrend(?Company $company): array
    {
        $labels = [];
        $sales = [];
        $purchases = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            if (! $company) {
                $sales[] = 0.0;
                $purchases[] = 0.0;

                continue;
            }

            $sales[] = (float) Sale::query()
                ->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('grand_total');
            $purchases[] = (float) Purchase::query()
                ->where('company_id', $company->id)
                ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('grand_total');
        }

        return ['labels' => $labels, 'sales' => $sales, 'purchases' => $purchases];
    }

    /**
     * Distribusi penjualan POS bulan ini per produk (top 6).
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function salesDistribution(?Company $company): array
    {
        if (! $company) {
            return ['labels' => [], 'values' => []];
        }

        $storeIds = $company->stores()->pluck('id');

        $rows = SaleItem::query()
            ->selectRaw('product_name, SUM(line_total) as total')
            ->whereHas('sale', fn ($q) => $q->whereIn('store_id', $storeIds)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]))
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return [
            'labels' => $rows->pluck('product_name')->map(fn ($n) => (string) $n)->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (float) $v)->all(),
        ];
    }
}
