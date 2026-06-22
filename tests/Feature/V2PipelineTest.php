<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesOrder;
use App\Models\SalesQuotation;
use App\Models\User;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2PipelineTest extends TestCase
{
    use RefreshDatabase;

    private function customer(Company $company): Contact
    {
        return Contact::query()->create([
            'company_id' => $company->id, 'name' => 'Pelanggan Tes', 'type' => 'customer', 'is_active' => true,
        ]);
    }

    private function product(Company $company): Product
    {
        return Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 20)->firstOrFail();
    }

    public function test_new_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        foreach ([
            'v2.sales.quotations.create', 'v2.sales.orders.create', 'v2.sales.receivables',
            'v2.purchase.requests', 'v2.purchase.requests.create', 'v2.purchase.orders.create', 'v2.purchase.payables',
        ] as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }

    public function test_sales_quotation_to_order_to_invoice(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $customer = $this->customer($company);
        $product = $this->product($company);

        $this->actingAs($user)->post(route('v2.sales.quotations.store'), [
            'contact_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 30000]],
        ])->assertRedirect(route('v2.sales.quotations'));

        $quotation = SalesQuotation::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('v2.sales.quotations.convert', $quotation))->assertRedirect(route('v2.sales.orders'));
        $this->assertEquals('ordered', $quotation->fresh()->status);

        $order = SalesOrder::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('v2.sales.orders.convert', $order))->assertRedirect();
        $this->assertEquals('invoiced', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->sales_invoice_id);
    }

    public function test_sales_payment_and_return(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $customer = $this->customer($company);
        $product = $this->product($company);

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company, contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 50000]],
            date: now(), createdBy: $user->id,
        );

        // Pembayaran sebagian.
        $this->actingAs($user)->post(route('v2.sales.invoices.payment.store', $invoice), [
            'amount' => 100000, 'method' => 'cash', 'date' => now()->toDateString(),
        ])->assertRedirect(route('v2.sales.invoices.show', $invoice));
        $this->assertEquals(100000, (float) $invoice->fresh()->outstanding_amount);

        // Retur 1 unit -> stok bertambah.
        $before = (int) $product->fresh()->stock;
        $this->actingAs($user)->post(route('v2.sales.invoices.return.store', $invoice), [
            'reason' => 'Rusak',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect(route('v2.sales.invoices.show', $invoice));
        $this->assertEquals($before + 1, (int) $product->fresh()->stock);
    }

    public function test_purchase_request_to_order_to_invoice_and_payment(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $supplier = Contact::query()->where('company_id', $company->id)->where('type', 'supplier')->firstOrFail();
        $product = $this->product($company);

        $this->actingAs($user)->post(route('v2.purchase.requests.store'), [
            'contact_id' => $supplier->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 10000]],
        ])->assertRedirect(route('v2.purchase.requests'));

        $pr = PurchaseRequest::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('v2.purchase.requests.convert', $pr))->assertRedirect(route('v2.purchase.orders'));

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('v2.purchase.orders.convert', $po))->assertRedirect();
        $this->assertEquals('received', $po->fresh()->status);

        $purchase = Purchase::query()->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('v2.purchase.invoices.payment.store', $purchase), [
            'amount' => (float) $purchase->outstanding_amount, 'method' => 'bank', 'date' => now()->toDateString(),
        ])->assertRedirect(route('v2.purchase.invoices.show', $purchase));
        $this->assertEquals(0.0, (float) $purchase->fresh()->outstanding_amount);
    }
}
