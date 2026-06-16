<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2InvoicePrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_print_renders(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $customer = Contact::query()->create(['company_id' => $company->id, 'name' => 'Pelanggan Cetak', 'type' => 'customer', 'is_active' => true]);
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 2)->firstOrFail();

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company, contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 50000]],
            date: now(), createdBy: $user->id,
        );

        $this->actingAs($user)->get(route('v2.sales.invoices.print', $invoice))
            ->assertOk()->assertSee($invoice->number)->assertSee('FAKTUR PENJUALAN');
    }

    public function test_purchase_invoice_print_renders(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $supplier = Contact::query()->where('company_id', $company->id)->where('type', 'supplier')->firstOrFail();
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->firstOrFail();

        $purchase = app(PurchaseService::class)->create(
            company: $company, contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 12000]],
            date: now(), createdBy: $user->id,
        );

        $this->actingAs($user)->get(route('v2.purchase.invoices.print', $purchase))
            ->assertOk()->assertSee($purchase->number)->assertSee('FAKTUR PEMBELIAN');
    }
}
