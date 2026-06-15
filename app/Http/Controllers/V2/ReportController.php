<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
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
}
