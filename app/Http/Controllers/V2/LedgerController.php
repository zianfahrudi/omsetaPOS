<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Services\Accounting\LedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function index(Request $request, LedgerService $ledgerService): View
    {
        $company = Company::query()->first();

        $accounts = Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $accountId = (int) $request->query('account_id', $accounts->first()->id ?? 0);
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $account = $accounts->firstWhere('id', $accountId);
        $entries = $account ? $ledgerService->ledger($account, $from, $to) : collect();
        $opening = $account ? $ledgerService->balance($account, $from->copy()->subDay()) : 0.0;

        return view('v2.accounting.ledger', [
            'accounts' => $accounts,
            'accountId' => $accountId,
            'account' => $account,
            'entries' => $entries,
            'opening' => $opening,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
