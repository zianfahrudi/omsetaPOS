<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseReturns\Pages\CreatePurchaseReturn;
use App\Filament\Resources\PurchaseReturns\Pages\ListPurchaseReturns;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseReturnFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_can_be_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-rtb@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $purchase = app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 6000]],
        );

        $this->actingAs($admin);

        Livewire::test(ListPurchaseReturns::class)->assertOk();

        Livewire::test(CreatePurchaseReturn::class)
            ->fillForm([
                'company_id' => $company->id,
                'purchase_id' => $purchase->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 6000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(7, $product->refresh()->stock);
        $this->assertDatabaseHas('purchase_returns', [
            'company_id' => $company->id,
            'total' => 18000,
        ]);
        $this->assertSame(1, PurchaseReturn::where('company_id', $company->id)->count());
    }
}
