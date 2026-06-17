<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Material;
use App\Models\MaterialPurchase;
use App\Services\MaterialPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class MaterialPurchaseController extends Controller
{
    public const METHOD_LABELS = ['cash' => 'Tunai (Kas)', 'bank' => 'Transfer (Bank)', 'credit' => 'Kredit (Hutang)'];

    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = MaterialPurchase::query()
            ->with('supplier')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.purchase.material-purchases', [
            'records' => $records,
            'methodLabels' => self::METHOD_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('v2.purchase.material-purchase-form', [
            'materials' => $this->materials(),
            'suppliers' => $this->suppliers(),
            'methodLabels' => self::METHOD_LABELS,
        ]);
    }

    public function store(Request $request, MaterialPurchaseService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'payment_method' => ['required', 'in:cash,bank,credit'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['material_id']) && (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => [
                'material_id' => (int) $i['material_id'],
                'quantity' => (float) $i['quantity'],
                'unit_cost' => (float) $i['unit_cost'],
            ])->values()->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Pilih minimal 1 material.']);
        }

        try {
            $purchase = $service->create(
                company: $company,
                items: $items,
                paymentMethod: $data['payment_method'],
                contactId: ! empty($data['contact_id']) ? (int) $data['contact_id'] : null,
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.materials.show', $purchase->id)->with('status', 'Belanja bahan dicatat & stok diperbarui.');
    }

    public function show(int $id): View
    {
        $company = Company::query()->first();
        $purchase = MaterialPurchase::query()
            ->with(['items.material', 'supplier'])
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->findOrFail($id);

        return view('v2.purchase.material-purchase-show', [
            'purchase' => $purchase,
            'methodLabels' => self::METHOD_LABELS,
        ]);
    }

    public function stockCard(Request $request): View
    {
        $company = Company::query()->first();
        $month = $request->string('month')->value() ?: now()->format('Y-m');
        $p = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $p->copy()->startOfMonth()->toDateString();
        $end = $p->copy()->endOfMonth()->toDateString();

        $materials = Material::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->with(['movements' => fn ($q) => $q->whereDate('date', '>=', $start)->whereDate('date', '<=', $end)])
            ->orderBy('category')->orderBy('name')
            ->get();

        $rows = $materials->map(function (Material $m) {
            $masuk = (float) $m->movements->where('quantity', '>', 0)->sum('quantity');
            $keluar = (float) abs($m->movements->where('quantity', '<', 0)->sum('quantity'));
            $akhir = (float) $m->stock;
            $awal = round($akhir - $masuk + $keluar, 2);

            return [
                'name' => $m->name, 'category' => $m->category, 'unit' => $m->unit, 'price' => (float) $m->price,
                'awal' => $awal, 'masuk' => $masuk, 'keluar' => $keluar, 'akhir' => $akhir,
                'value' => round($akhir * (float) $m->price, 2),
            ];
        });

        return view('v2.purchase.material-stock-card', [
            'rows' => $rows,
            'month' => $month,
            'periodLabel' => $p->translatedFormat('F Y'),
            'totalValue' => $rows->sum('value'),
        ]);
    }

    private function materials()
    {
        $company = Company::query()->first();

        return Material::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price', 'stock'])
            ->map(fn (Material $m) => ['id' => $m->id, 'name' => $m->name, 'unit' => (string) ($m->unit ?? ''), 'price' => (float) $m->price, 'stock' => (float) $m->stock])
            ->values();
    }

    private function suppliers()
    {
        $company = Company::query()->first();

        return Contact::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->whereIn('type', ['supplier', 'other'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
