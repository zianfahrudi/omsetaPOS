<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Assembly;
use App\Models\Company;
use App\Models\Material;
use App\Models\Product;
use App\Services\AssemblyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class AssemblyController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = Assembly::query()
            ->with('product')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.inventory.assemblies', compact('records'));
    }

    public function create(): View
    {
        return view('v2.inventory.assembly-form', [
            'products' => $this->products(),
            'materials' => $this->materials(),
        ]);
    }

    public function store(Request $request, AssemblyService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.material_id' => ['nullable', 'integer'],
            'components.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if (empty($data['product_id']) && blank($data['product_name'] ?? null)) {
            return back()->withInput()->withErrors(['product_name' => 'Pilih produk jadi atau isi nama manual.']);
        }

        $components = collect($data['components'])
            ->filter(fn ($c) => ! empty($c['material_id']) && (float) ($c['quantity'] ?? 0) > 0)
            ->map(fn ($c) => ['material_id' => (int) $c['material_id'], 'quantity' => (int) $c['quantity']])
            ->values()->all();

        if ($components === []) {
            return back()->withInput()->withErrors(['components' => 'Pilih minimal 1 komponen material.']);
        }

        $productId = $data['product_id'] ?? null;

        try {
            $service->create(
                company: $company,
                finishedProductId: $productId ? (int) $productId : null,
                finishedProductName: $data['product_name'] ?? null,
                quantity: (int) $data['quantity'],
                components: $components,
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['components' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.assemblies')->with('status', 'Perakitan berhasil dicatat.');
    }

    public function show(Assembly $assembly): View
    {
        $assembly->load('components.product', 'product');

        return view('v2.inventory.assembly-show', compact('assembly'));
    }

    public function complete(Assembly $assembly, AssemblyService $service): RedirectResponse
    {
        try {
            $service->complete($assembly, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.assemblies.show', $assembly->id)->with('status', 'Perakitan selesai. Produk jadi masuk ke stok.');
    }

    public function cancel(Assembly $assembly, AssemblyService $service): RedirectResponse
    {
        try {
            $service->cancel($assembly, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.assemblies.show', $assembly->id)->with('status', 'Perakitan dibatalkan. Stok bahan dikembalikan.');
    }

    private function products()
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        return Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('product_type', '!=', 'service')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'stock', 'cost_price'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'stock' => (int) $p->stock, 'cost' => (float) $p->cost_price])
            ->values();
    }

    private function materials()
    {
        $company = Company::query()->first();

        return Material::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'price'])
            ->map(fn (Material $m) => ['id' => $m->id, 'name' => $m->name, 'unit' => (string) ($m->unit ?? ''), 'cost' => (float) $m->price, 'stock' => (float) $m->stock])
            ->values();
    }
}
