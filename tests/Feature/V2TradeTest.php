<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2TradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_form_renders_and_posts(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $customer = Contact::query()->create([
            'company_id' => $company->id,
            'name' => 'Pelanggan Tes',
            'type' => 'customer',
            'is_active' => true,
        ]);
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 5)->firstOrFail();

        $this->actingAs($user)->get(route('v2.sales.invoices.create'))->assertOk();

        $before = (int) $product->stock;

        $response = $this->actingAs($user)->post(route('v2.sales.invoices.store'), [
            'contact_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 25000, 'tax_amount' => 0],
            ],
        ]);

        $invoice = SalesInvoice::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('v2.sales.invoices.show', $invoice));

        $this->assertEquals(75000, (float) $invoice->grand_total);
        $this->assertEquals($before - 3, (int) $product->fresh()->stock);
        $this->assertTrue(Journal::query()->where('reference', $invoice->number)->exists());
    }

    public function test_purchase_invoice_form_renders_and_posts(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $supplier = Contact::query()->where('company_id', $company->id)->where('type', 'supplier')->firstOrFail();
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->firstOrFail();

        $this->actingAs($user)->get(route('v2.purchase.invoices.create'))->assertOk();

        $before = (int) $product->stock;

        $response = $this->actingAs($user)->post(route('v2.purchase.invoices.store'), [
            'contact_id' => $supplier->id,
            'date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 12000, 'tax_amount' => 0],
            ],
        ]);

        $purchase = Purchase::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('v2.purchase.invoices.show', $purchase));

        $this->assertEquals(120000, (float) $purchase->grand_total);
        $this->assertEquals($before + 10, (int) $product->fresh()->stock);
        $this->assertTrue(Journal::query()->where('reference', $purchase->number)->exists());
    }
}
