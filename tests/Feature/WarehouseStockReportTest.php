<?php

namespace Tests\Feature;

use App\Filament\Pages\WarehouseStockReport;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use App\Services\PurchaseService;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseStockReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_stock_report_values_and_renders(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-whr@test.test',
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
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000]],
        );
        app(WarehouseStockService::class)->transfer($company, $main->id, $branch->id, [
            ['product_id' => $product->id, 'quantity' => 4],
        ]);

        $report = app(ReportService::class)->warehouseStockReport($company);
        // 10 units @ 5.000 across 2 warehouses = 50.000
        $this->assertSame(50000.0, $report['total_value']);
        $this->assertSame(2, $report['rows']->count());

        $this->actingAs($admin);
        Livewire::test(WarehouseStockReport::class)->assertOk()->assertSee('Total Nilai Stok');
    }
}
