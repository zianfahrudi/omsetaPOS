<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\Company;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class BankReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = BankReconciliation::query()
            ->with('account')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->orderByDesc('statement_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.cash.reconciliations', compact('records'));
    }

    public function create(): View
    {
        $company = Company::query()->first();

        return view('v2.cash.reconciliation-form', [
            'bankAccounts' => $this->bankAccounts($company),
        ]);
    }

    public function store(Request $request, BankReconciliationService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'account_id' => ['required', 'integer'],
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $rec = $service->reconcile(
                company: $company,
                accountId: (int) $data['account_id'],
                statementDate: $data['statement_date'],
                statementBalance: (float) $data['statement_balance'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['account_id' => $e->getMessage()]);
        }

        return redirect()->route('v2.cash.reconciliations.show', $rec)->with('status', "Rekonsiliasi {$rec->number} dibuat.");
    }

    public function show(BankReconciliation $reconciliation): View
    {
        $reconciliation->load('account');

        return view('v2.cash.reconciliation-show', ['rec' => $reconciliation]);
    }

    private function bankAccounts(?Company $company)
    {
        return Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->whereIn('subtype', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }
}
