<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Consignment;
use App\Models\Contact;
use App\Models\Product;
use App\Services\ConsignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class ConsignmentController extends Controller
{
    public function index(Request $request): View
    {
        $company = Company::query()->first();

        $records = Consignment::query()
            ->with('consignee')
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('v2.inventory.consignments', compact('records'));
    }

    public function create(): View
    {
        $company = Company::query()->first();

        return view('v2.inventory.consignment-form', [
            'consignees' => Contact::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->orderBy('name')->get(['id', 'name', 'type']),
            'products' => $this->products($company),
        ]);
    }

    public function store(Request $request, ConsignmentService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => ! empty($i['product_id']) && (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => (int) $i['quantity'], 'unit_price' => (float) $i['unit_price']])
            ->values()->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Pilih minimal 1 item.']);
        }

        try {
            $consignment = $service->ship($company, (int) $data['contact_id'], $items, $data['date'], $data['notes'] ?? null, Auth::id());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.consignments.show', $consignment)->with('status', "Konsinyasi {$consignment->number} berhasil dikirim.");
    }

    public function show(Consignment $consignment): View
    {
        $consignment->load('items.product', 'consignee');
        $company = Company::query()->first();

        return view('v2.inventory.consignment-show', [
            'consignment' => $consignment,
            'cashAccounts' => Account::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->whereIn('subtype', ['cash', 'bank'])->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function settle(Request $request, Consignment $consignment, ConsignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'cash_account_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.sold_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($l) => (float) ($l['sold_quantity'] ?? 0) > 0)
            ->map(fn ($l) => ['item_id' => (int) $l['item_id'], 'sold_quantity' => (int) $l['sold_quantity']])
            ->values()->all();

        if ($lines === []) {
            return back()->withErrors(['settle' => 'Isi kuantitas terjual minimal 1 item.']);
        }

        try {
            $service->settle($consignment, $lines, (int) $data['cash_account_id'], $data['date'], Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['settle' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.consignments.show', $consignment)->with('status', 'Penjualan konsinyasi dicatat.');
    }

    public function returnItems(Request $request, Consignment $consignment, ConsignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'lines' => ['required', 'array'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($l) => (float) ($l['quantity'] ?? 0) > 0)
            ->map(fn ($l) => ['item_id' => (int) $l['item_id'], 'quantity' => (int) $l['quantity']])
            ->values()->all();

        if ($lines === []) {
            return back()->withErrors(['return' => 'Isi kuantitas retur minimal 1 item.']);
        }

        try {
            $service->returnItems($consignment, $lines, $data['date'], Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return redirect()->route('v2.inventory.consignments.show', $consignment)->with('status', 'Retur konsinyasi dicatat.');
    }

    private function products(?Company $company)
    {
        $storeIds = Auth::user()->accessibleStores()->pluck('id');

        return Product::query()
            ->whereIn('store_id', $storeIds)
            ->where('product_type', '!=', 'service')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sell_price', 'stock'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->sell_price, 'stock' => (int) $p->stock])
            ->values();
    }
}
