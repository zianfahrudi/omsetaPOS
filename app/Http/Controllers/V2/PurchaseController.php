<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class PurchaseController extends Controller
{
    public function orders(Request $request): View
    {
        $records = $this->base(PurchaseOrder::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.orders', compact('records'));
    }

    public function invoices(Request $request): View
    {
        $records = $this->base(Purchase::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.invoices', compact('records'));
    }

    public function invoiceShow(Purchase $invoice): View
    {
        $invoice->load(['items.product', 'supplier', 'payments']);

        return view('v2.purchase.invoice-show', compact('invoice'));
    }

    public function invoiceCreate(): View
    {
        $company = Company::query()->first();

        return view('v2.purchase.invoice-form', [
            'suppliers' => Contact::query()->where('type', 'supplier')->orderBy('name')->get(['id', 'name']),
            'warehouses' => $this->warehouses($company),
            'products' => $this->products($company),
        ]);
    }

    public function invoiceStore(Request $request, PurchaseService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer'],
            'supplier_invoice_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => [
                'product_id' => $i['product_id'] ?? null,
                'product_name' => $i['product_name'] ?? null,
                'quantity' => (int) $i['quantity'],
                'unit_cost' => (float) $i['unit_cost'],
                'tax_amount' => (float) ($i['tax_amount'] ?? 0),
            ])
            ->values()
            ->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Minimal 1 item dengan kuantitas lebih dari nol.']);
        }

        try {
            $purchase = $service->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $items,
                date: $data['date'],
                warehouseId: $data['warehouse_id'] ?? null,
                storeId: null,
                supplierInvoiceNo: $data['supplier_invoice_no'] ?? null,
                dueDate: $data['due_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.invoices.show', $purchase)->with('status', "Faktur {$purchase->number} berhasil dibuat.");
    }

    private function base($query, Request $request)
    {
        $company = Company::query()->first();

        return $query
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where('number', 'like', $like);
            })
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function warehouses(?Company $company)
    {
        return Warehouse::query()
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'is_default']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function products(?Company $company)
    {
        return Product::query()
            ->when($company, fn ($q) => $q->whereHas('store', fn ($s) => $s->where('company_id', $company->id)))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sell_price', 'cost_price', 'product_type'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->cost_price,
                'type' => $p->product_type,
            ])
            ->values();
    }
}
