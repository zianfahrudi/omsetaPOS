<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesReturns\Pages\CreateSalesReturn;
use App\Filament\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReturnFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_return_can_be_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-rtj@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $customer = Contact::create(['company_id' => $company->id, 'name' => 'Pelanggan', 'type' => 'customer', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 50,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 10000]],
        );

        $this->actingAs($admin);

        Livewire::test(ListSalesReturns::class)->assertOk();

        Livewire::test(CreateSalesReturn::class)
            ->fillForm([
                'company_id' => $company->id,
                'sales_invoice_id' => $invoice->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 10000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(44, $product->refresh()->stock);
        $this->assertDatabaseHas('sales_returns', [
            'company_id' => $company->id,
            'total' => 40000,
        ]);
        $this->assertSame(1, SalesReturn::where('company_id', $company->id)->count());
    }
}
