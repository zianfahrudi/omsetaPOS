<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function balanceSheet(Request $request, ReportService $reports): View
    {
        $company = Company::query()->first();
        $asOf = $request->date('as_of') ?? now();
        $report = $company ? $reports->balanceSheet($company, $asOf) : null;

        return view('v2.reports.balance-sheet', [
            'report' => $report,
            'asOf' => $asOf->toDateString(),
        ]);
    }

    public function incomeStatement(Request $request, ReportService $reports): View
    {
        $company = Company::query()->first();
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();
        $report = $company ? $reports->incomeStatement($company, $from, $to) : null;

        return view('v2.reports.income-statement', [
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function cashFlow(Request $request, ReportService $reports): View
    {
        [$company, $from, $to] = $this->period($request);
        $report = $company ? $reports->cashFlow($company, $from, $to) : null;

        return view('v2.reports.cash-flow', compact('report') + ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    /**
     * Rekap kas per minggu (laporan transaksi harian dikelompokkan mingguan).
     */
    public function cashWeekly(Request $request): View
    {
        $company = Company::query()->first();
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $weeks = [];
        if ($company) {
            $txns = CashTransaction::query()
                ->where('company_id', $company->id)
                ->whereIn('type', ['in', 'out'])
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($txns as $t) {
                $w = intdiv(((int) $t->date->day) - 1, 7) + 1; // Minggu 1..5 dalam bulan
                $weeks[$w] ??= ['no' => $w, 'rows' => [], 'out' => 0.0, 'in' => 0.0];
                $out = $t->type === 'out' ? (float) $t->amount : 0.0;
                $in = $t->type === 'in' ? (float) $t->amount : 0.0;
                $weeks[$w]['rows'][] = [
                    'date' => $t->date,
                    'description' => $t->description ?: ($t->number ?: '—'),
                    'out' => $out,
                    'in' => $in,
                ];
                $weeks[$w]['out'] = round($weeks[$w]['out'] + $out, 2);
                $weeks[$w]['in'] = round($weeks[$w]['in'] + $in, 2);
            }
            ksort($weeks);
        }

        $grandOut = round(array_sum(array_column($weeks, 'out')), 2);
        $grandIn = round(array_sum(array_column($weeks, 'in')), 2);

        return view('v2.reports.cash-weekly', [
            'weeks' => array_values($weeks),
            'grandOut' => $grandOut,
            'grandIn' => $grandIn,
            'grandNet' => round($grandIn - $grandOut, 2),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function sales(Request $request, ReportService $reports): View
    {
        [$company, $from, $to] = $this->period($request);
        $report = $company ? $reports->salesAnalysis($company, $from, $to) : null;

        return view('v2.reports.sales', compact('report') + ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function purchases(Request $request, ReportService $reports): View
    {
        [$company, $from, $to] = $this->period($request);
        $report = $company ? $reports->purchaseAnalysis($company, $from, $to) : null;

        return view('v2.reports.purchases', compact('report') + ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function inventory(Request $request, ReportService $reports): View
    {
        $company = Company::query()->first();
        $lowOnly = $request->boolean('low');
        $report = $company ? $reports->inventoryReport($company, null, $lowOnly) : null;

        return view('v2.reports.inventory', ['report' => $report, 'lowOnly' => $lowOnly]);
    }

    public function tax(Request $request, ReportService $reports): View
    {
        [$company, $from, $to] = $this->period($request);
        $report = $company ? $reports->taxReport($company, $from, $to) : null;

        return view('v2.reports.tax', compact('report') + ['from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    public function trialBalance(Request $request, LedgerService $ledger): View
    {
        $company = Company::query()->first();
        $asOf = $request->date('as_of') ?? now();
        $rows = $company ? $ledger->trialBalance($company, $asOf) : collect();

        return view('v2.reports.trial-balance', [
            'rows' => $rows,
            'totalDebit' => round((float) $rows->sum('debit'), 2),
            'totalCredit' => round((float) $rows->sum('credit'), 2),
            'asOf' => $asOf->toDateString(),
        ]);
    }

    public function warehouseStock(Request $request, ReportService $reports): View
    {
        $company = Company::query()->first();
        $warehouses = $company ? Warehouse::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']) : collect();
        $warehouseId = (int) $request->query('warehouse_id', 0) ?: null;
        $report = $company ? $reports->warehouseStockReport($company, $warehouseId) : null;

        return view('v2.reports.warehouse-stock', [
            'report' => $report,
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
        ]);
    }

    /**
     * Rekap performa petugas (mekanik/salesman) per periode.
     * Hanya admin/superuser. Agregasi item jasa tertaut petugas dari sale completed.
     */
    public function mechanicPerformance(Request $request): View
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'superuser'], true), 403, 'Hanya admin yang dapat melihat laporan ini.');

        [$company, $from, $to] = $this->period($request);

        $rows = $company
            ? SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('stores', 'stores.id', '=', 'sales.store_id')
                ->join('employees', 'employees.id', '=', 'sale_items.employee_id')
                ->whereNotNull('sale_items.employee_id')
                ->where('stores.company_id', $company->id)
                ->where('sales.status', 'completed')
                ->whereBetween('sales.paid_at', [$from, $to])
                ->where('sale_items.product_type', 'service')
                ->groupBy('sale_items.employee_id', 'employees.name', 'employees.code')
                ->selectRaw("sale_items.employee_id, employees.name as employee_name, employees.code as employee_code, COUNT(*) as service_count, SUM(sale_items.line_total) as service_total, COUNT(DISTINCT sale_items.sale_id) as sale_count, GROUP_CONCAT(DISTINCT sales.number) as sale_numbers")
                ->orderByDesc('service_total')
                ->get()
            : collect();

        return view('v2.reports.mechanic-performance', [
            'rows' => $rows,
            'company' => $company,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * @return array{0: ?Company, 1: Carbon, 2: Carbon}
     */
    private function period(Request $request): array
    {
        return [
            Company::query()->first(),
            $request->date('from') ?? now()->startOfMonth(),
            $request->date('to') ?? now(),
        ];
    }
}
