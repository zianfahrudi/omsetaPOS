<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Warehouse;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\ReportService;
use Illuminate\Http\Request;
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
     * @return array{0: ?Company, 1: \Illuminate\Support\Carbon, 2: \Illuminate\Support\Carbon}
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
