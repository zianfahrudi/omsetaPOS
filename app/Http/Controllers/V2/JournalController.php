<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Services\Accounting\PostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $journals = Journal::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('description', 'like', $like));
            })
            ->when($request->string('type')->value(), fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('v2.accounting.journals', compact('journals'));
    }

    public function show(Journal $journal): View
    {
        $journal->load(['lines.account', 'createdBy']);

        return view('v2.accounting.journal-show', compact('journal'));
    }

    public function create(): View
    {
        return view('v2.accounting.journal-form', [
            'accounts' => $this->accounts(),
        ]);
    }

    public function store(Request $request, PostingService $posting): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['nullable', 'integer'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($l) => ! empty($l['account_id']) && ((float) ($l['debit'] ?? 0) > 0 || (float) ($l['credit'] ?? 0) > 0))
            ->map(fn ($l) => [
                'account_id' => (int) $l['account_id'],
                'debit' => (float) ($l['debit'] ?? 0),
                'credit' => (float) ($l['credit'] ?? 0),
                'memo' => $l['memo'] ?? null,
            ])
            ->values()->all();

        try {
            $journal = $posting->post(
                company: $company,
                date: $data['date'],
                lines: $lines,
                type: 'general',
                description: $data['description'] ?? 'Jurnal umum',
                reference: $data['reference'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()->route('v2.accounting.journals.show', $journal)->with('status', "Jurnal {$journal->number} berhasil diposting.");
    }

    private function accounts()
    {
        $company = Company::query()->first();

        return Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Account $a) => ['id' => $a->id, 'name' => $a->code.' - '.$a->name])
            ->values();
    }
}
