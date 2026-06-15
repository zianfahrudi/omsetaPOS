<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Resources\SalesInvoices\Pages\ListSalesInvoices;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesInvoiceFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-inv@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $customer = Contact::create([
            'company_id' => $company->id, 'name' => 'PT Pelanggan', 'type' => 'customer', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 50,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListSalesInvoices::class)->assertOk();

        Livewire::test(CreateSalesInvoice::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'product_name' => 'Barang', 'line_type' => 'goods', 'quantity' => 3, 'unit_price' => 10000, 'tax_amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = SalesInvoice::where('company_id', $company->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(30000.0, (float) $invoice->grand_total);
        $this->assertSame(47, $product->refresh()->stock);
    }
}
