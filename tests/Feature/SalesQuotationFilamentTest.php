<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesQuotations\Pages\CreateSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\ListSalesQuotations;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesQuotation;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesQuotationFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-quo@test.test',
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

        $this->actingAs($admin);

        Livewire::test(ListSalesQuotations::class)->assertOk();

        Livewire::test(CreateSalesQuotation::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'product_name' => 'Barang', 'quantity' => 4, 'unit_price' => 10000, 'tax_amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $q = SalesQuotation::where('company_id', $company->id)->first();
        $this->assertNotNull($q);
        $this->assertSame(40000.0, (float) $q->grand_total);
    }
}
