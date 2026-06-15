<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Giro;
use App\Services\GiroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class GiroController extends Controller
{
    public const STATUS_LABELS = [
        'received' => 'Diterima',
        'deposited' => 'Disetor',
        'cleared' => 'Cair',
        'rejected' => 'Tolak',
    ];

    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = Giro::query()
            ->with('customer')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($w) => $w->where('number', 'like', '%'.$term.'%')->orWhere('giro_number', 'like', '%'.$term.'%')))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.cash.giros', [
            'records' => $records,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        $company = Company::query()->first();

        return view('v2.cash.giro-form', [
            'customers' => Contact::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->where('type', 'customer')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, GiroService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:1'],
            'date' => ['required', 'date'],
            'giro_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date'],
        ]);

        try {
            $service->receive(
                company: $company,
                contactId: (int) $data['contact_id'],
                amount: (float) $data['amount'],
                date: $data['date'],
                giroNumber: $data['giro_number'] ?? null,
                bankName: $data['bank_name'] ?? null,
                dueDate: $data['due_date'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('v2.cash.giros')->with('status', 'Giro masuk berhasil dicatat.');
    }

    public function deposit(Giro $giro, GiroService $service): RedirectResponse
    {
        try {
            $service->deposit($giro);
        } catch (Throwable $e) {
            return back()->withErrors(['giro' => $e->getMessage()]);
        }

        return back()->with('status', 'Giro ditandai disetor.');
    }

    public function clearForm(Giro $giro): View
    {
        abort_unless($giro->isOpen(), 404);
        $company = Company::query()->first();

        return view('v2.cash.giro-clear', [
            'giro' => $giro->load('customer'),
            'bankAccounts' => $this->bankAccounts($company),
        ]);
    }

    public function clear(Request $request, Giro $giro, GiroService $service): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
        ]);

        try {
            $service->clear($giro, (int) $data['bank_account_id'], $data['date']);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['bank_account_id' => $e->getMessage()]);
        }

        return redirect()->route('v2.cash.giros')->with('status', "Giro {$giro->number} berhasil dicairkan.");
    }

    public function reject(Giro $giro, GiroService $service): RedirectResponse
    {
        try {
            $service->reject($giro);
        } catch (Throwable $e) {
            return back()->withErrors(['giro' => $e->getMessage()]);
        }

        return back()->with('status', "Giro {$giro->number} ditandai ditolak.");
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
