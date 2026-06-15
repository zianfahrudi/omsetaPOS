<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Services\FixedAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class FixedAssetController extends Controller
{
    public const STATUS_LABELS = [
        'active' => 'Aktif',
        'fully_depreciated' => 'Tersusut Penuh',
        'disposed' => 'Dilepas',
    ];

    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = FixedAsset::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$term.'%')->orWhere('code', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('v2.assets.index', [
            'records' => $records,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('v2.assets.form', [
            'asset' => new FixedAsset(['acquisition_date' => now()->toDateString(), 'useful_life_months' => 60]),
        ] + $this->accountOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $this->validateData($request);
        $data['company_id'] = $company->id;
        $data['created_by'] = Auth::id();
        FixedAsset::create($data);

        return redirect()->route('v2.assets.index')->with('status', 'Harta tetap berhasil dicatat.');
    }

    public function show(FixedAsset $asset): View
    {
        $asset->load('company');

        return view('v2.assets.show', [
            'asset' => $asset,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function edit(FixedAsset $asset): View
    {
        return view('v2.assets.form', ['asset' => $asset] + $this->accountOptions());
    }

    public function update(Request $request, FixedAsset $asset): RedirectResponse
    {
        $asset->update($this->validateData($request));

        return redirect()->route('v2.assets.index')->with('status', 'Harta tetap diperbarui.');
    }

    public function depreciate(FixedAsset $asset, FixedAssetService $service): RedirectResponse
    {
        try {
            $service->depreciate($asset, now());
        } catch (Throwable $e) {
            return back()->withErrors(['asset' => $e->getMessage()]);
        }

        return back()->with('status', 'Penyusutan bulan ini berhasil diposting.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['required', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1'],
            'asset_account_id' => ['nullable', 'integer'],
            'accumulated_account_id' => ['nullable', 'integer'],
            'expense_account_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountOptions(): array
    {
        $company = Company::query()->first();
        $accounts = Account::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return [
            'assetAccounts' => $accounts->where('type', 'asset')->values(),
            'expenseAccounts' => $accounts->where('type', 'expense')->values(),
        ];
    }
}
