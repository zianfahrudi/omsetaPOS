<?php

namespace Tests\Feature;

use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_can_be_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-pur@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create([
            'company_id' => $company->id, 'name' => 'Supplier A', 'type' => 'supplier', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 9000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPurchases::class)->assertOk();

        Livewire::test(CreatePurchase::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'product_name' => 'Barang', 'line_type' => 'goods', 'quantity' => 4, 'unit_cost' => 7000, 'tax_amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $purchase = Purchase::where('company_id', $company->id)->first();
        $this->assertNotNull($purchase);
        $this->assertSame(28000.0, (float) $purchase->grand_total);

        $product->refresh();
        $this->assertSame(4, $product->stock);
    }
}
