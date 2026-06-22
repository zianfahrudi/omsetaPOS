<?php

namespace Tests\Feature;

use App\Filament\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\PurchaseService;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockTransferFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_transfer_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-trf@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $main = Warehouse::create(['company_id' => $company->id, 'name' => 'Utama', 'code' => 'G1', 'is_default' => true, 'is_active' => true]);
        $branch = Warehouse::create(['company_id' => $company->id, 'name' => 'Cabang', 'code' => 'G2', 'is_active' => true]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        app(PurchaseService::class)->create(
            company: $company, contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 15, 'unit_cost' => 5000]],
        );

        $this->actingAs($admin);

        Livewire::test(ListStockTransfers::class)->assertOk();

        Livewire::test(CreateStockTransfer::class)
            ->fillForm([
                'company_id' => $company->id,
                'date' => '2026-06-15',
                'from_warehouse_id' => $main->id,
                'to_warehouse_id' => $branch->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 6],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, StockTransfer::where('company_id', $company->id)->count());

        $wh = app(WarehouseStockService::class);
        $this->assertSame(9, $wh->quantity($main->id, $product->id));
        $this->assertSame(6, $wh->quantity($branch->id, $product->id));
        $this->assertSame(15, $product->refresh()->stock);
    }
}
