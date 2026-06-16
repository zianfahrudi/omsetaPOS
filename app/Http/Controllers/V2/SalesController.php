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
use App\Services\Accounting\ReportService;
use App\Services\SalesInvoicePaymentService;
use App\Services\SalesInvoiceService;
use App\Services\SalesOrderService;
use App\Services\SalesQuotationService;
use App\Services\SalesReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class SalesController extends Controller
{
    // ---- Penawaran ----------------------------------------------------------

    public function quotations(Request $request): View
    {
        $records = $this->base(SalesQuotation::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.quotations', compact('records'));
    }

    public function quotationCreate(): View
    {
        return view('v2.sales.quotation-form', $this->formData());
    }

    public function quotationStore(Request $request, SalesQuotationService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_price');

        return $this->run(fn () => $service->create(
            company: $company,
            contactId: (int) $data['contact_id'],
            items: $this->mapItems($data['items'], 'unit_price'),
            date: $data['date'],
            validUntil: $data['valid_until'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: Auth::id(),
        ), 'v2.sales.quotations', 'Penawaran %s berhasil dibuat.');
    }

    public function quotationConvert(SalesQuotation $quotation, SalesQuotationService $service): RedirectResponse
    {
        try {
            $service->convertToOrder($quotation, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        return redirect()->route('v2.sales.orders')->with('status', 'Penawaran dikonversi menjadi pesanan.');
    }

    // ---- Pesanan ------------------------------------------------------------

    public function orders(Request $request): View
    {
        $records = $this->base(SalesOrder::query()->with('customer'), $request)->paginate(20)->withQueryString();

        return view('v2.sales.orders', compact('records'));
    }

    public function orderCreate(): View
    {
        return view('v2.sales.order-form', $this->formData());
    }

    public function orderStore(Request $request, SalesOrderService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_price', 'expected_date');

        return $this->run(fn () => $service->create(
            company: $company,
            contactId: (int) $data['contact_id'],
            items: $this->mapItems($data['items'], 'unit_price'),
            date: $data['date'],
            expectedDate: $data['expected_date'] ?? null,
            notes: $data['notes'] ?? null,
            createdBy: Auth::id(),
        ), 'v2.sales.orders', 'Pesanan %s berhasil dibuat.');
    }

    public function orderConvert(SalesOrder $order, SalesOrderService $service): RedirectResponse
    {
        try {
            $order = $service->convertToInvoice($order, Auth::id());
        } catch (Throwable $e) {
            return back()->withErrors(['convert' => $e->getMessage()]);
        }

        return redirect()->route('v2.sales.invoices.show', $order->sales_invoice_id)->with('status', 'Pesanan dikonversi menjadi faktur.');
    }

    // ---- Faktur -------------------------------------------------------------

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

    public function invoicePrint(SalesInvoice $invoice): View
    {
        $invoice->load(['items', 'customer', 'company']);

        return view('v2.print.invoice', [
            'title' => 'FAKTUR PENJUALAN',
            'company' => $invoice->company,
            'number' => $invoice->number,
            'date' => $invoice->date,
            'dueDate' => $invoice->due_date,
            'partnerLabel' => 'Pelanggan',
            'partnerName' => $invoice->customer?->name ?? '—',
            'ref' => $invoice->customer_ref,
            'items' => $invoice->items->map(fn ($i) => [
                'name' => $i->product_name ?: $i->product?->name,
                'qty' => (int) $i->quantity,
                'price' => (float) $i->unit_price,
                'tax' => (float) $i->tax_amount,
                'total' => (float) $i->line_total,
            ]),
            'subtotal' => (float) $invoice->subtotal,
            'taxTotal' => (float) $invoice->tax_total,
            'grandTotal' => (float) $invoice->grand_total,
            'paid' => (float) $invoice->paid_amount,
            'outstanding' => (float) $invoice->outstanding_amount,
            'backUrl' => route('v2.sales.invoices.show', $invoice),
        ]);
    }

    public function invoiceCreate(): View
    {
        return view('v2.sales.invoice-form', $this->formData());
    }

    public function invoiceStore(Request $request, SalesInvoiceService $service): RedirectResponse
    {
        $company = $this->company();
        $data = $this->validateDoc($request, 'unit_price', 'due_date', withWarehouse: true, refField: 'customer_ref');

        try {
            $invoice = $service->create(
                company: $company,
                contactId: (int) $data['contact_id'],
                items: $this->mapItems($data['items'], 'unit_price'),
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

    // ---- Pembayaran piutang -------------------------------------------------

    public function paymentCreate(SalesInvoice $invoice): View
    {
        abort_if((float) $invoice->outstanding_amount <= 0, 404);
        $invoice->load('customer');

        return view('v2.sales.payment-form', compact('invoice'));
    }

    public function paymentStore(Request $request, SalesInvoice $invoice, SalesInvoicePaymentService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:cash,bank'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->pay($invoice, (float) $data['amount'], $data['method'], $data['date'], $data['notes'] ?? null, Auth::id());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('v2.sales.invoices.show', $invoice)->with('status', 'Pembayaran piutang berhasil dicatat.');
    }

    // ---- Retur penjualan ----------------------------------------------------

    public function returnCreate(SalesInvoice $invoice): View
    {
        $invoice->load('items.product', 'customer');

        return view('v2.sales.return-form', compact('invoice'));
    }

    public function returnStore(Request $request, SalesInvoice $invoice, SalesReturnService $service): RedirectResponse
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

        return redirect()->route('v2.sales.invoices.show', $invoice)->with('status', 'Retur penjualan berhasil dicatat.');
    }

    // ---- Daftar piutang -----------------------------------------------------

    public function receivables(Request $request, ReportService $reports): View
    {
        $company = $this->company();
        $asOf = $request->date('as_of') ?? now();
        $report = $company ? $reports->receivableAging($company, $asOf) : null;

        return view('v2.sales.receivables', ['report' => $report, 'asOf' => $asOf->toDateString()]);
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
            'customers' => Contact::query()->where('type', 'customer')->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->when($company, fn ($q) => $q->where('company_id', $company->id))->where('is_active', true)->orderBy('name')->get(['id', 'name', 'is_default']),
            'products' => $this->products($company),
        ];
    }

    private function products(?Company $company)
    {
        return Product::query()
            ->when($company, fn ($q) => $q->whereHas('store', fn ($s) => $s->where('company_id', $company->id)))
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'sell_price', 'product_type'])
            ->map(fn (Product $p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->sell_price, 'type' => $p->product_type])
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
