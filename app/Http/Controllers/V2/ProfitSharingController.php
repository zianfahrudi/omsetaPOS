<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ProfitDistribution;
use App\Services\ProfitDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class ProfitSharingController extends Controller
{
    public function index(): View
    {
        $company = Company::query()->first();

        $records = ProfitDistribution::query()
            ->with('shares')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20);

        return view('v2.profit-sharing.index', compact('records'));
    }

    public function create(Request $request, ProfitDistributionService $service): View
    {
        $company = Company::query()->first();
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $netIncome = $company ? $service->netIncomeFor($company, $from, $to) : 0.0;

        return view('v2.profit-sharing.form', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'netIncome' => $netIncome,
            'defaultShares' => [
                ['name' => 'Owner', 'percent' => 30],
                ['name' => 'Modal/Gudang', 'percent' => 70],
            ],
        ]);
    }

    public function store(Request $request, ProfitDistributionService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
            'date' => ['required', 'date'],
            'base_amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'shares' => ['required', 'array', 'min:1'],
            'shares.*.name' => ['nullable', 'string', 'max:100'],
            'shares.*.percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $shares = collect($data['shares'])
            ->filter(fn ($s) => filled($s['name'] ?? null) && (float) ($s['percent'] ?? 0) > 0)
            ->map(fn ($s) => ['name' => $s['name'], 'percent' => (float) $s['percent']])
            ->values()->all();

        try {
            $dist = $service->create(
                company: $company,
                from: $data['period_from'],
                to: $data['period_to'],
                baseAmount: (float) $data['base_amount'],
                shares: $shares,
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['base_amount' => $e->getMessage()]);
        }

        return redirect()->route('v2.profit-sharing.show', $dist->id)->with('status', "Bagi hasil {$dist->number} dicatat & dijurnal.");
    }

    public function show(int $id): View
    {
        $company = Company::query()->first();
        $distribution = ProfitDistribution::query()
            ->with('shares')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->findOrFail($id);

        return view('v2.profit-sharing.show', compact('distribution'));
    }

    public function destroy(int $id, ProfitDistributionService $service): RedirectResponse
    {
        $company = Company::query()->first();
        $distribution = ProfitDistribution::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->findOrFail($id);

        $service->delete($distribution);

        return redirect()->route('v2.profit-sharing.index')->with('status', 'Bagi hasil dibatalkan & jurnal dihapus.');
    }
}
