<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Models\Contact;
use App\Services\CashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class CashController extends Controller
{
    public const TYPE_LABELS = [
        'in' => 'Kas Masuk',
        'out' => 'Kas Keluar',
        'transfer' => 'Transfer',
    ];

    public function transactions(Request $request): View
    {
        $company = Company::query()->first();

        $records = CashTransaction::query()
            ->with(['account', 'toAccount'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.cash.transactions', [
            'records' => $records,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function create(): View
    {
        $company = Company::query()->first();

        return view('v2.cash.form', [
            'typeLabels' => self::TYPE_LABELS,
            'cashAccounts' => $this->cashAccounts($company?->id),
            'counterAccounts' => $this->counterAccounts($company?->id),
            'contacts' => Contact::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, CashService $cash): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'type' => ['required', 'in:in,out,transfer'],
            'date' => ['required', 'date'],
            'account_id' => ['required', 'integer'],
            'to_account_id' => ['required_if:type,transfer', 'nullable', 'integer', 'different:account_id'],
            'counter_account_id' => ['required_if:type,in', 'required_if:type,out', 'nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'contact_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $uid = Auth::id();

        try {
            $tx = match ($data['type']) {
                'in' => $cash->receive($company, (int) $data['account_id'], (int) $data['counter_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, $data['contact_id'] ?? null, $uid),
                'out' => $cash->pay($company, (int) $data['account_id'], (int) $data['counter_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, $data['contact_id'] ?? null, $uid),
                'transfer' => $cash->transfer($company, (int) $data['account_id'], (int) $data['to_account_id'], (float) $data['amount'], $data['date'], $data['description'] ?? null, $uid),
            };
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('v2.cash.transactions')->with('status', "Transaksi {$tx->number} berhasil dicatat.");
    }

    /**
     * @return array<int, string>
     */
    private function cashAccounts($companyId): array
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->whereIn('subtype', ['cash', 'bank'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function counterAccounts($companyId): array
    {
        return Account::query()
            ->where('company_id', $companyId)
            ->where('is_postable', true)
            ->where('is_active', true)
            ->whereNotIn('subtype', ['cash', 'bank'])
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} - {$a->name}"])
            ->all();
    }
}
