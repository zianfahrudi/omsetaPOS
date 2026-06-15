<?php

namespace Tests\Feature;

use App\Filament\Pages\InventoryReport;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_report_values_stock_and_renders(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-stkrep@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        Product::create([
            'store_id' => $store->id, 'name' => 'Barang A', 'sku' => 'A1',
            'cost_price' => 5000, 'sell_price' => 9000, 'stock' => 10,
            'product_type' => 'goods', 'is_active' => true,
        ]);
        Product::create([
            'store_id' => $store->id, 'name' => 'Barang B', 'sku' => 'B1',
            'cost_price' => 2000, 'sell_price' => 4000, 'stock' => 5,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $report = app(ReportService::class)->inventoryReport($company);
        // 10*5000 + 5*2000 = 60.000
        $this->assertSame(60000.0, $report['total_value']);
        $this->assertSame(2, $report['total_items']);

        $this->actingAs($admin);
        Livewire::test(InventoryReport::class)
            ->assertOk()
            ->assertSee('Total Nilai Persediaan');
    }
}
