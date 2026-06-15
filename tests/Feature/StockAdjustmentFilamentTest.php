<?php

namespace Tests\Feature;

use App\Filament\Pages\StockCardReport;
use App\Filament\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockAdjustmentFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_adjustment_create_and_stock_card_render(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-stk@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 9000, 'stock' => 20,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListStockAdjustments::class)->assertOk();
        Livewire::test(StockCardReport::class)->assertOk();

        Livewire::test(CreateStockAdjustment::class)
            ->fillForm([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'reason' => 'opname',
                'quantity_after' => 17,
                'date' => '2026-06-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(17, $product->refresh()->stock);
        $this->assertDatabaseHas('stock_adjustments', [
            'product_id' => $product->id,
            'difference' => -3,
        ]);
    }
}
