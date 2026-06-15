<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\PurchaseService;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WarehouseStockTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Product,2:Warehouse,3:Warehouse,4:Contact}
     */
    private function fixture(): array
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $owner = User::create([
            'name' => 'Owner', 'email' => 'o@test.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $owner->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $main = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Utama', 'code' => 'G1', 'is_default' => true, 'is_active' => true]);
        $branch = Warehouse::create(['company_id' => $company->id, 'name' => 'Gudang Cabang', 'code' => 'G2', 'is_active' => true]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        return [$company, $product, $main, $branch, $supplier];
    }

    public function test_purchase_lands_in_default_warehouse_and_transfer_moves_between(): void
    {
        [$company, $product, $main, $branch, $supplier] = $this->fixture();
        $wh = app(WarehouseStockService::class);

        // Buy 20 -> default warehouse 20, total 20.
        app(PurchaseService::class)->create(
            company: $company, contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 5000]],
        );

        $this->assertSame(20, $product->refresh()->stock);
        $this->assertSame(20, $wh->quantity($main->id, $product->id));
        $this->assertSame(0, $wh->quantity($branch->id, $product->id));

        // Transfer 8 to branch. Total unchanged.
        $wh->transfer($company, $main->id, $branch->id, [
            ['product_id' => $product->id, 'quantity' => 8],
        ]);

        $this->assertSame(12, $wh->quantity($main->id, $product->id));
        $this->assertSame(8, $wh->quantity($branch->id, $product->id));
        $this->assertSame(20, $product->refresh()->stock);
        // sum of warehouses == total
        $this->assertSame(20, $wh->quantity($main->id, $product->id) + $wh->quantity($branch->id, $product->id));
    }

    public function test_transfer_rejects_insufficient_stock(): void
    {
        [$company, $product, $main, $branch] = $this->fixture();

        $this->expectException(InvalidArgumentException::class);
        app(WarehouseStockService::class)->transfer($company, $main->id, $branch->id, [
            ['product_id' => $product->id, 'quantity' => 5],
        ]);
    }
}
