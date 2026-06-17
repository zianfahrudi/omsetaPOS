<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Material;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectExpense;
use App\Models\ProjectPaymentTerm;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Unit;
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

    protected string $viewIndex = 'v2.master.projects.index';

    protected string $label = 'Proyek';

    public const STATUS_LABELS = [
        'planned' => 'Direncanakan',
        'approved' => 'Penawaran Disetujui',
        'active' => 'Berjalan',
        'completed' => 'Selesai',
        'paid' => 'Lunas',
        'on_hold' => 'Ditunda',
        'cancelled' => 'Batal',
    ];

    public const COST_LABELS = [
        'material' => 'Material',
        'upah' => 'Upah',
        'operasional' => 'Operasional',
    ];

    public function index(Request $request): View
    {
        return parent::index($request)->with('statusLabels', self::STATUS_LABELS);
    }

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => '<a href="'.route('v2.projects.show', $m->id).'" class="font-medium text-indigo-600 hover:underline">'.e($m->name).'</a>'.($m->code ? '<span class="ml-1 text-xs text-slate-400">'.e($m->code).'</span>' : ''),
            'Nilai Kontrak' => fn (Model $m) => 'Rp '.number_format($m->effectiveContractValue(), 0, ',', '.'),
            'Sisa Tagihan' => fn (Model $m) => 'Rp '.number_format($m->remainingBill(), 0, ',', '.'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:500'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'regency_id' => ['nullable', 'integer', 'exists:regencies,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'code' => ['nullable', 'string', 'max:30'],
            'contact_id' => ['nullable', 'integer'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'profit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:planned,approved,active,completed,paid,on_hold,cancelled'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'status' => 'planned'];
    }

    /**
     * Proyek baru mewarisi default overhead & profit dari pengaturan perusahaan.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request, new Project));
        $company = Company::query()->first();

        $data['company_id'] = $company?->id;
        $data['status'] = 'planned';
        $data['overhead_percent'] = (float) ($company->default_overhead_percent ?? 0);
        $data['profit_percent'] = (float) ($company->default_profit_percent ?? 0);

        Project::create($data);

        return redirect()->route('v2.projects.index')->with('status', 'Proyek berhasil ditambahkan.');
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
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'regencies' => Regency::query()->orderBy('name')->get(['id', 'province_id', 'name'])
                ->map(fn (Regency $r) => ['id' => $r->id, 'province_id' => $r->province_id, 'name' => $r->name])
                ->values(),
        ];
    }

    // ---- Detail proyek + rincian biaya ----

    public function show(int $id): View
    {
        $project = $this->find($id);
        $project->load(['customer', 'province', 'regency', 'district', 'costs.product', 'expenses', 'paymentTerms']);
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
            'materials' => Material::query()
                ->where('company_id', $this->companyId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'price'])
                ->map(fn (Material $m) => ['id' => $m->id, 'name' => $m->name, 'unit' => (string) ($m->unit ?? ''), 'cost' => (float) $m->price])
                ->values(),
            'units' => Unit::query()
                ->where('company_id', $this->companyId())
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
            'costLabels' => self::COST_LABELS,
            'statusLabels' => self::STATUS_LABELS,
            'expenseCategories' => ProjectExpense::CATEGORIES,
        ]);
    }

    // ---- Preview & Export (Cetak / Excel / Word) ----

    public function print(int $id): View
    {
        return view('v2.master.projects.print', $this->documentData($id));
    }

    public function exportExcel(int $id): \Symfony\Component\HttpFoundation\Response
    {
        return $this->downloadDocument($id, 'xls', 'application/vnd.ms-excel');
    }

    public function exportWord(int $id): \Symfony\Component\HttpFoundation\Response
    {
        return $this->downloadDocument($id, 'doc', 'application/msword');
    }

    /**
     * @return array<string, mixed>
     */
    private function documentData(int $id): array
    {
        $project = $this->find($id);
        $project->load(['customer', 'province', 'regency', 'district', 'costs.product']);

        return [
            'project' => $project,
            'company' => Company::query()->first(),
            'costLabels' => self::COST_LABELS,
            'statusLabels' => self::STATUS_LABELS,
        ];
    }

    private function downloadDocument(int $id, string $ext, string $mime): \Symfony\Component\HttpFoundation\Response
    {
        $data = $this->documentData($id);
        $body = view('v2.master.projects.document-body', $data)->render();
        $name = 'Penawaran-'.str($data['project']->name)->slug().'.'.$ext;

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
            .'<head><meta charset="utf-8"></head><body>'.$body.'</body></html>';

        return response($html, 200, [
            'Content-Type' => $mime.'; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    public function storeCost(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'type' => ['required', 'in:material,upah,operasional'],
            'product_id' => ['nullable', 'integer'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
        ]);

        $productId = $data['type'] === 'material' ? ($data['product_id'] ?? null) : null;

        // Wajib ada identitas item: produk (material) atau keterangan bebas.
        if (! $productId && blank($data['description'] ?? null)) {
            return back()->withInput()->with('status', 'Isi nama/keterangan item atau pilih produk.');
        }

        $amount = round((float) $data['quantity'] * (float) $data['unit_cost'], 2);
        $nextOrder = (int) $project->costs()->max('sort_order') + 1;

        $project->costs()->create([
            'sort_order' => $nextOrder,
            'group_name' => $data['group_name'] ?? null,
            'type' => $data['type'],
            'product_id' => $productId,
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

    public function updateCost(Request $request, int $id, int $cost): RedirectResponse
    {
        $project = $this->find($id);
        $row = $project->costs()->whereKey($cost)->firstOrFail();

        $data = $request->validate([
            'type' => ['required', 'in:material,upah,operasional'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:30'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        if (blank($data['description'] ?? null) && ! $row->product_id) {
            return back()->with('status', 'Isi nama/keterangan item.');
        }

        $row->update([
            'type' => $data['type'],
            'group_name' => $data['group_name'] ?? null,
            'description' => $data['description'] ?? null,
            'quantity' => (float) $data['quantity'],
            'unit' => $data['unit'] ?? null,
            'unit_cost' => (float) $data['unit_cost'],
            'amount' => round((float) $data['quantity'] * (float) $data['unit_cost'], 2),
        ]);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Item RAB diperbarui.');
    }

    public function destroyCost(int $id, int $cost): RedirectResponse
    {
        $project = $this->find($id);
        $project->costs()->whereKey($cost)->delete();

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Biaya proyek dihapus.');
    }

    /**
     * Atur persentase overhead, profit & PPN untuk proyek ini (penawaran/RAB).
     */
    public function updatePenawaran(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'overhead_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'profit_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rounding_unit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->update([
            'overhead_percent' => (float) ($data['overhead_percent'] ?? 0),
            'profit_percent' => (float) ($data['profit_percent'] ?? 0),
            'tax_percent' => (float) ($data['tax_percent'] ?? 0),
            'rounding_unit' => (float) ($data['rounding_unit'] ?? 0),
        ]);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Pengaturan penawaran diperbarui.');
    }

    /**
     * Setujui penawaran: status berubah ke "Penawaran Disetujui" sehingga DP bisa dicatat.
     */
    public function approve(int $id): RedirectResponse
    {
        $project = $this->find($id);
        $project->load('costs');

        if ($project->totalPenawaran() <= 0) {
            return redirect()->route('v2.projects.show', $project->id)->with('status', 'Belum ada penawaran untuk disetujui.');
        }

        $project->update(['status' => 'approved']);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Penawaran disetujui. DP sudah bisa dicatat.');
    }

    /**
     * Ubah status proyek secara manual.
     */
    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'status' => ['required', 'in:planned,approved,active,completed,paid,on_hold,cancelled'],
        ]);

        $project->update(['status' => $data['status']]);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Status proyek diperbarui.');
    }

    /**
     * Catat DP setelah penawaran disepakati. Nilai kontrak dikunci ke total penawaran.
     */
    public function updateDownPayment(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);
        $project->load('costs');

        $data = $request->validate([
            'down_payment' => ['required', 'numeric', 'min:0'],
        ]);

        $total = $project->totalPenawaran();
        $dp = min((float) $data['down_payment'], $total);

        $attributes = [
            'contract_value' => $total,
            'down_payment' => $dp,
        ];

        // DP melunasi seluruh nilai → status otomatis "Lunas".
        if ($total > 0 && $dp >= $total) {
            $attributes['status'] = 'paid';
        } elseif ($project->status === 'approved') {
            // Penawaran disetujui & DP diterima → proyek berjalan.
            $attributes['status'] = 'active';
        }

        $project->update($attributes);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'DP dicatat. Nilai kontrak dikunci ke total penawaran.');
    }

    // ---- Realisasi biaya (anggaran RAB vs aktual) ----

    public function storeExpense(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'category' => ['required', 'in:'.implode(',', ProjectExpense::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
        $data['created_by'] = Auth::id();
        $project->expenses()->create($data);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Realisasi biaya dicatat.');
    }

    public function destroyExpense(int $id, int $expense): RedirectResponse
    {
        $project = $this->find($id);
        $project->expenses()->whereKey($expense)->delete();

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Realisasi biaya dihapus.');
    }

    // ---- Termin pembayaran ----

    public function storeTerm(Request $request, int $id): RedirectResponse
    {
        $project = $this->find($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $data['sort_order'] = (int) $project->paymentTerms()->max('sort_order') + 1;
        $project->paymentTerms()->create($data);

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Termin pembayaran ditambahkan.');
    }

    public function payTerm(Request $request, int $id, int $term): RedirectResponse
    {
        $project = $this->find($id);
        $row = $project->paymentTerms()->whereKey($term)->firstOrFail();

        $method = in_array($request->input('method'), ['cash', 'bank'], true) ? $request->input('method') : 'cash';
        $paid = ! $row->is_paid;
        $service = app(\App\Services\ProjectTermPaymentService::class);

        $row->update([
            'is_paid' => $paid,
            'paid_date' => $paid ? now() : null,
        ]);

        // Posting / batalkan jurnal (Dr Kas/Bank, Cr Pendapatan Proyek).
        if ($paid) {
            $service->markPaid($row, $method, Auth::id());
        } else {
            $service->reverse($row);
        }

        // Sinkronkan status proyek bila seluruh termin lunas.
        $project->load('paymentTerms');
        if ($project->paymentTerms->isNotEmpty() && $project->paymentTerms->every(fn ($t) => $t->is_paid)) {
            $project->update(['status' => 'paid']);
        } elseif ($project->status === 'paid') {
            $project->update(['status' => 'active']);
        }

        return redirect()->route('v2.projects.show', $project->id)->with('status', $paid ? 'Termin lunas & jurnal dicatat.' : 'Termin dibatalkan, jurnal dihapus.');
    }

    public function destroyTerm(int $id, int $term): RedirectResponse
    {
        $project = $this->find($id);
        $project->paymentTerms()->whereKey($term)->delete();

        return redirect()->route('v2.projects.show', $project->id)->with('status', 'Termin dihapus.');
    }
}
