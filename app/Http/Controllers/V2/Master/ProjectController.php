<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCost;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectController extends SimpleCrudController
{
    protected string $modelClass = Project::class;

    protected string $routeBase = 'v2.projects';

    protected string $viewForm = 'v2.master.projects.form';

    protected string $label = 'Proyek';

    public const STATUS_LABELS = [
        'planned' => 'Direncanakan',
        'active' => 'Berjalan',
        'completed' => 'Selesai',
        'on_hold' => 'Ditunda',
        'cancelled' => 'Batal',
    ];

    public const COST_LABELS = [
        'material' => 'Material',
        'upah' => 'Upah',
        'operasional' => 'Operasional',
    ];

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => '<a href="'.route('v2.projects.show', $m->id).'" class="font-medium text-indigo-600 hover:underline">'.e($m->name).'</a>'.($m->code ? '<span class="ml-1 text-xs text-slate-400">'.e($m->code).'</span>' : ''),
            'Status' => fn (Model $m) => e(self::STATUS_LABELS[$m->status] ?? $m->status),
            'Nilai Kontrak' => fn (Model $m) => 'Rp '.number_format((float) $m->contract_value, 0, ',', '.'),
            'Sisa Tagihan' => fn (Model $m) => 'Rp '.number_format($m->remainingBill(), 0, ',', '.'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'contact_id' => ['nullable', 'integer'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'profit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:planned,active,completed,on_hold,cancelled'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        $company = Company::query()->first();

        return [
            'is_active' => true,
            'status' => 'active',
            'overhead_percent' => (float) ($company->default_overhead_percent ?? 0),
            'profit_percent' => (float) ($company->default_profit_percent ?? 0),
        ];
    }

    protected function formData(): array
    {
        return [
            'statusLabels' => self::STATUS_LABELS,
            'customers' => Contact::query()
                ->where('company_id', $this->companyId())
                ->where('type', 'customer')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    // ---- Detail proyek + rincian biaya ----

    public function show(int $id): View
    {
        $project = $this->find($id);
        $project->load(['customer', 'costs.product']);

        $storeIds = Auth::user()->accessibleStores()->pluck('id');
        $products = Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'unit'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'cost' => (float) $p->cost_price, 'unit' => (string) ($p->unit ?? '')])
            ->values();

        return view('v2.master.projects.show', [
            'project' => $project,
            'products' => $products,
            'costLabels' => self::COST_LABELS,
            'statusLabels' => self::STATUS_LABELS,
        ]);
    }

    public function storeCost(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'type' => ['required', 'in:material,upah,operasional'],
            'product_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
        ]);

        $amount = round((float) $data['quantity'] * (float) $data['unit_cost'], 2);

        $project->costs()->create([
            'type' => $data['type'],
            'product_id' => $data['type'] === 'material' ? ($data['product_id'] ?? null) : null,
            'description' => $data['description'] ?? null,
            'quantity' => (float) $data['quantity'],
            'unit' => $data['unit'] ?? null,
            'unit_cost' => (float) $data['unit_cost'],
            'amount' => $amount,
            'date' => $data['date'] ?? now(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Biaya proyek ditambahkan.');
    }

    public function destroyCost(int $id, int $cost): RedirectResponse
    {
        $project = $this->find($id);
        $project->costs()->whereKey($cost)->delete();

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Biaya proyek dihapus.');
    }

    /**
     * Atur persentase overhead & profit untuk proyek ini (penawaran/RAB).
     */
    public function updatePenawaran(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'profit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $project->update([
            'overhead_percent' => (float) ($data['overhead_percent'] ?? 0),
            'profit_percent' => (float) ($data['profit_percent'] ?? 0),
        ]);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Persentase overhead & profit diperbarui.');
    }
}
