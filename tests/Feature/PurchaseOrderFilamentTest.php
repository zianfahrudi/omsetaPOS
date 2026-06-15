<?php

namespace Tests\Feature;

use App\Filament\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseOrderFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-po@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListPurchaseOrders::class)->assertOk();

        Livewire::test(CreatePurchaseOrder::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $supplier->id,
                'date' => '2026-06-15',
                'items' => [
                    ['product_id' => $product->id, 'product_name' => 'Barang', 'quantity' => 10, 'unit_cost' => 6000, 'tax_amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = PurchaseOrder::where('company_id', $company->id)->first();
        $this->assertNotNull($order);
        $this->assertSame(60000.0, (float) $order->grand_total);
    }
}
