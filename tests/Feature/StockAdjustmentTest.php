<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Product}
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
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 9000, 'stock' => 20,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        return [$company, $product];
    }

    public function test_stock_loss_decreases_inventory_and_books_expense(): void
    {
        [$company, $product] = $this->fixture();

        // Counted 15 vs system 20 -> loss 5 @ 5.000 = 25.000.
        app(StockAdjustmentService::class)->adjust($company, $product->id, 15, 'opname');

        $this->assertSame(15, $product->refresh()->stock);

        $ledger = app(LedgerService::class);
        $this->assertSame(-25000.0, $ledger->balance($company->account('inventory')));
        $this->assertSame(25000.0, $ledger->balance($company->account('operating_expense')));

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => -5,
        ]);
    }

    public function test_stock_gain_increases_inventory_and_books_income(): void
    {
        [$company, $product] = $this->fixture();

        // Counted 26 vs system 20 -> gain 6 @ 5.000 = 30.000.
        app(StockAdjustmentService::class)->adjust($company, $product->id, 26, 'correction');

        $this->assertSame(26, $product->refresh()->stock);

        $ledger = app(LedgerService::class);
        $this->assertSame(30000.0, $ledger->balance($company->account('inventory')));
        $this->assertSame(30000.0, $ledger->balance($company->account('other_income')));
    }
}
