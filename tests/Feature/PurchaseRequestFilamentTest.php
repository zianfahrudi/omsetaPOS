<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseRequestFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_request_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-pr@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPurchaseRequests::class)->assertOk();

        Livewire::test(CreatePurchaseRequest::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'product_name' => 'Barang', 'quantity' => 10, 'unit_cost' => 5000, 'tax_amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $pr = PurchaseRequest::where('company_id', $company->id)->first();
        $this->assertNotNull($pr);
        $this->assertSame(50000.0, (float) $pr->grand_total);
    }
}
