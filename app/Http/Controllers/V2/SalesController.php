<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\Warehouse;
use App\Services\SalesInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class SalesController extends Controller
{
    public function quotations(Request $request): View
    {
        $records = $this->base(SalesQuotation::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.quotations', compact('records'));
    }

    public function orders(Request $request): View
    {
        $records = $this->base(SalesOrder::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.orders', compact('records'));
    }

    public function invoices(Request $request): View
    {
        $records = $this->base(SalesInvoice::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.invoices', compact('records'));
    }

    public function invoiceShow(SalesInvoice $invoice): View
    {
        $invoice->load(['items.product', 'customer', 'payments']);

        return view('v2.sales.invoice-show', compact('invoice'));
    }

    public function invoiceCreate(): View
    {
        $company = Company::query()->first();

        return view('v2.sales.invoice-form', [
            'customers' => Contact::query()->where('type', 'customer')->orderBy('name')->get(['id', 'name']),
            'warehouses' => $this->warehouses($company),
            'products' => $this->products($company),
        ]);
    }

    public function invoiceStore(Request $request, SalesInvoiceService $service): RedirectResponse
    {
        $company = Company::query()->first();
        abort_unless($company, 404);

        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer'],
            'customer_ref' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => [
                'product_id' => $i['product_id'] ?? null,
                'product_name' => $i['product_name'] ?? null,
                'quantity' => (int) $i['quantity'],
                'unit_price' => (float) $i['unit_price'],
                'tax_amount' => (float) ($i['tax_amount'] ?? 0),
            ])
            ->values()
            ->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Minimal 1 item dengan kuantitas lebih dari nol.']);
        }

        try {
            $invoice = $service->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $items,
                date: $data['date'],
                warehouseId: $data['warehouse_id'] ?? null,
                storeId: null,
                customerRef: $data['customer_ref'] ?? null,
                dueDate: $data['due_date'] ?? null,
                notes: $data['notes'] ?? null,
                createdBy: Auth::id(),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.sales.invoices.show', $invoice)->with('status', "Faktur {$invoice->number} berhasil dibuat.");
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
                'price' => (float) $p->sell_price,
                'type' => $p->product_type,
            ])
            ->values();
    }
}
