<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use App\Services\Accounting\ReportService;
use App\Services\PurchaseOrderService;
use App\Services\PurchasePaymentService;
use App\Services\PurchaseRequestService;
use App\Services\PurchaseReturnService;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class PurchaseController extends Controller
{
    // ---- Permintaan Pembelian ----------------------------------------------

    public function requests(Request $request): View
    {
        $records = $this->base(PurchaseRequest::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.requests', compact('records'));
    }

    public function requestCreate(): View
    {
        return view('v2.purchase.request-form', $this->formData());
    }

    public function requestStore(Request $request, PurchaseRequestService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_cost', 'needed_date');

        return $this->run(fn () => $service->create(
            company: $company,
            contactId: (int) $data['contact_id'],
            items: $this->mapItems($data['items'], 'unit_cost'),
            date: $data['date'],
            neededDate: $data['needed_date'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: Auth::id(),
        ), 'v2.purchase.requests', 'Permintaan %s berhasil dibuat.');
    }

    public function requestConvert(PurchaseRequest $purchaseRequest, PurchaseRequestService $service): RedirectResponse
    {
        try {
            $service->convertToOrder($purchaseRequest, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.orders')->with('status', 'Permintaan dikonversi menjadi pesanan.');
    }

    // ---- Pesanan Pembelian --------------------------------------------------

    public function orders(Request $request): View
    {
        $records = $this->base(PurchaseOrder::query()->with('supplier'), $request)->paginate(20)->withQueryString();

        return view('v2.purchase.orders', compact('records'));
    }

    public function orderCreate(): View
    {
        return view('v2.purchase.order-form', $this->formData());
    }

    public function orderStore(Request $request, PurchaseOrderService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_cost', 'expected_date');

        return $this->run(fn () => $service->create(
            company: $company,
            contactId: (int) $data['contact_id'],
            items: $this->mapItems($data['items'], 'unit_cost'),
            date: $data['date'],
            expectedDate: $data['expected_date'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: Auth::id(),
        ), 'v2.purchase.orders', 'Pesanan %s berhasil dibuat.');
    }

    public function orderConvert(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $purchaseOrder = $service->convertToPurchase($purchaseOrder, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.invoices.show', $purchaseOrder->purchase_id)->with('status', 'Pesanan dikonversi menjadi faktur.');
    }

    // ---- Faktur Pembelian ---------------------------------------------------

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

    public function invoicePrint(Purchase $invoice): View
    {
        $invoice->load(['items', 'supplier', 'company']);

        return view('v2.print.invoice', [
            'title' => 'FAKTUR PEMBELIAN',
            'company' => $invoice->company,
            'number' => $invoice->number,
            'date' => $invoice->date,
            'dueDate' => $invoice->due_date,
            'partnerLabel' => 'Pemasok',
            'partnerName' => $invoice->supplier?->name ?? '—',
            'ref' => $invoice->supplier_invoice_no,
            'items' => $invoice->items->map(fn ($i) => [
                'name' => $i->product_name ?: $i->product?->name,
                'qty' => (int) $i->quantity,
                'price' => (float) $i->unit_cost,
                'tax' => (float) $i->tax_amount,
                'total' => (float) $i->line_total,
            ]),
            'subtotal' => (float) $invoice->subtotal,
            'taxTotal' => (float) $invoice->tax_total,
            'grandTotal' => (float) $invoice->grand_total,
            'paid' => (float) $invoice->paid_amount,
            'outstanding' => (float) $invoice->outstanding_amount,
            'backUrl' => route('v2.purchase.invoices.show', $invoice),
        ]);
    }

    public function invoiceCreate(): View
    {
        return view('v2.purchase.invoice-form', $this->formData());
    }

    public function invoiceStore(Request $request, PurchaseService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_cost', 'due_date', withWarehouse: true, refField: 'supplier_invoice_no');

        try {
            $purchase = $service->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $this->mapItems($data['items'], 'unit_cost'),
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

    // ---- Pembayaran hutang --------------------------------------------------

    public function paymentCreate(Purchase $invoice): View
    {
        abort_if((float) $invoice->outstanding_amount <= 0, 404);
        $invoice->load('supplier', 'company');

        return view('v2.purchase.payment-form', [
            'invoice' => $invoice,
            'cashAccounts' => $invoice->company?->cashBankAccounts() ?? collect(),
        ]);
    }

    public function paymentStore(Request $request, Purchase $invoice, PurchasePaymentService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'account_id' => ['nullable', 'integer'],
            'method' => ['nullable', 'in:cash,bank'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $accountId = isset($data['account_id']) ? (int) $data['account_id'] : null;
        $method = $data['method'] ?? 'cash';
        if ($accountId) {
            $account = $invoice->company?->cashBankAccounts()->firstWhere('id', $accountId);
            if ($account) {
                $method = $account->subtype === 'cash' ? 'cash' : 'bank';
            }
        }

        try {
            $service->pay($invoice, (float) $data['amount'], $method, $data['date'], $data['notes'] ?? null, Auth::id(), $accountId);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.invoices.show', $invoice)->with('status', 'Pembayaran hutang berhasil dicatat.');
    }

    // ---- Retur pembelian ----------------------------------------------------

    public function returnCreate(Purchase $invoice): View
    {
        $invoice->load('items.product', 'supplier');

        return view('v2.purchase.return-form', compact('invoice'));
    }

    public function returnStore(Request $request, Purchase $invoice, PurchaseReturnService $service): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $items = collect($data['items'])
            ->filter(fn ($i) => (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => ['product_id' => (int) $i['product_id'], 'quantity' => (int) $i['quantity']])
            ->values()->all();

        if ($items === []) {
            return back()->withInput()->withErrors(['items' => 'Pilih minimal 1 item dengan kuantitas.']);
        }

        try {
            $service->create($invoice, $items, now(), $data['reason'] ?? null, Auth::id());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('v2.purchase.invoices.show', $invoice)->with('status', 'Retur pembelian berhasil dicatat.');
    }

    // ---- Daftar hutang ------------------------------------------------------

    public function payables(Request $request, ReportService $reports): View
    {
        $company = $this->company();
        $asOf = $request->date('as_of') ?? now();
        $report = $company ? $reports->payableAging($company, $asOf) : null;

        return view('v2.purchase.payables', ['report' => $report, 'asOf' => $asOf->toDateString()]);
    }

    // ---- Helpers ------------------------------------------------------------

    private function company(): ?Company
    {
        return Company::query()->first();
    }

    private function base($query, Request $request)
    {
        $company = $this->company();

        return $query
            ->when($company, fn ($q) => $q->where('company_id', $company->id))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where('number', 'like', '%'.$term.'%'))
            ->orderByDesc('date')->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $company = $this->company();

        return [
            'suppliers' => Contact::query()->where('type', 'supplier')->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->where('is_active', true)->orderBy('name')->get(['id', 'name', 'is_default']),
            'products' => $this->products($company),
        ];
    }

    private function products(?Company $company)
    {
        return Product::query()
            ->when($company, fn ($q) => $q->whereHas('store', fn ($s) => $s->where('company_id', $company->id)))
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'cost_price', 'product_type'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->cost_price, 'type' => $p->product_type])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDoc(Request $request, string $priceField, ?string $secondaryDate = null, bool $withWarehouse = false, ?string $refField = null): array
    {
        $rules = [
            'contact_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.product_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.'.$priceField => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
        if ($secondaryDate) {
            $rules[$secondaryDate] = ['nullable', 'date'];
        }
        if ($withWarehouse) {
            $rules['warehouse_id'] = ['nullable', 'integer'];
        }
        if ($refField) {
            $rules[$refField] = ['nullable', 'string', 'max:100'];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapItems(array $rows, string $priceField): array
    {
        return collect($rows)
            ->filter(fn ($i) => (float) ($i['quantity'] ?? 0) > 0)
            ->map(fn ($i) => [
                'product_id' => $i['product_id'] ?? null,
                'product_name' => $i['product_name'] ?? null,
                'quantity' => (int) $i['quantity'],
                $priceField => (float) $i[$priceField],
                'tax_amount' => (float) ($i['tax_amount'] ?? 0),
            ])
            ->values()->all();
    }

    private function run(callable $fn, string $redirectRoute, string $successMessage): RedirectResponse
    {
        try {
            $doc = $fn();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route($redirectRoute)->with('status', sprintf($successMessage, $doc->number));
    }
}
