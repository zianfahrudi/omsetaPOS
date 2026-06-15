<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\CashierSessionService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CashierSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_computes_expected_cash_and_difference(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $cashier = User::create([
            'name' => 'Kasir', 'email' => 'k@test.test',
            'password' => bcrypt('password'), 'role' => 'cashier', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $cashier->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 4000, 'sell_price' => 10000, 'stock' => 100,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = app(CashierSessionService::class);
        $session = $service->open($store, $cashier->id, 100000);
        $this->assertSame('open', $session->status);

        // Cannot open a second session.
        try {
            $service->open($store, $cashier->id, 50000);
            $this->fail('Expected exception');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // Two cash sales: 2x10.000 + 3x10.000 = 50.000.
        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(storeId: $store->id, cashierId: $cashier->id, items: [['product_id' => $product->id, 'quantity' => 2]], paymentMethod: 'cash', paidAmount: 20000);
        app(CheckoutService::class)->checkout(storeId: $store->id, cashierId: $cashier->id, items: [['product_id' => $product->id, 'quantity' => 3]], paymentMethod: 'cash', paidAmount: 30000);

        // Count 148.000 (short by 2.000): expected = 100.000 + 50.000 = 150.000.
        $session = $service->close($session, 148000);

        $this->assertSame('closed', $session->status);
        $this->assertSame('50000.00', (string) $session->cash_sales_total);
        $this->assertSame('150000.00', (string) $session->expected_cash);
        $this->assertSame('-2000.00', (string) $session->cash_difference);
    }
}
