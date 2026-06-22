<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueByCategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Store,2:User}
     */
    private function world(): array
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

        return [$company, $store, $cashier];
    }

    public function test_pos_sale_splits_revenue_per_category_account(): void
    {
        [$company, $store, $cashier] = $this->world();

        $sales = $company->account('sales');

        // Sub-akun pendapatan khusus kategori Oli.
        $oliRev = Account::create([
            'company_id' => $company->id, 'parent_id' => $sales->id, 'code' => '4-1101',
            'name' => 'Pendapatan Oli', 'type' => 'revenue', 'subtype' => null,
            'normal_balance' => 'credit', 'is_postable' => true, 'is_active' => true,
        ]);

        $catOli = Category::create(['company_id' => $company->id, 'name' => 'Oli', 'revenue_account_id' => $oliRev->id]);
        $catBan = Category::create(['company_id' => $company->id, 'name' => 'Ban']); // tanpa pemetaan

        $oli = Product::create([
            'store_id' => $store->id, 'category_id' => $catOli->id, 'name' => 'Oli 1L', 'sku' => 'OLI',
            'cost_price' => 0, 'sell_price' => 50000, 'stock' => 100, 'product_type' => 'goods', 'is_active' => true,
        ]);
        $ban = Product::create([
            'store_id' => $store->id, 'category_id' => $catBan->id, 'name' => 'Ban Luar', 'sku' => 'BAN',
            'cost_price' => 0, 'sell_price' => 300000, 'stock' => 100, 'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(
            storeId: $store->id, cashierId: $cashier->id,
            items: [
                ['product_id' => $oli->id, 'quantity' => 2],   // 100.000 → Pendapatan Oli
                ['product_id' => $ban->id, 'quantity' => 1],   // 300.000 → Penjualan default
            ],
            paymentMethod: 'cash', paidAmount: 400000,
        );

        $is = app(ReportService::class)->incomeStatement($company, now()->startOfMonth(), now()->endOfMonth());
        $rev = collect($is['revenue'])->keyBy('code');

        $this->assertEqualsWithDelta(100000, (float) $rev[$oliRev->code]['amount'], 0.01, 'Oli harus masuk akun pendapatan kategorinya.');
        $this->assertEqualsWithDelta(300000, (float) $rev[$sales->code]['amount'], 0.01, 'Ban (tanpa pemetaan) jatuh ke akun Penjualan default.');
        $this->assertEqualsWithDelta(400000, (float) $is['total_revenue'], 0.01);
    }

    public function test_product_without_category_mapping_falls_back_to_default_sales(): void
    {
        [$company, $store, $cashier] = $this->world();

        $sales = $company->account('sales');
        $cat = Category::create(['company_id' => $company->id, 'name' => 'Umum']);

        $p = Product::create([
            'store_id' => $store->id, 'category_id' => $cat->id, 'name' => 'Barang', 'sku' => 'BRG',
            'cost_price' => 0, 'sell_price' => 75000, 'stock' => 100, 'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(
            storeId: $store->id, cashierId: $cashier->id,
            items: [['product_id' => $p->id, 'quantity' => 1]],
            paymentMethod: 'cash', paidAmount: 75000,
        );

        $is = app(ReportService::class)->incomeStatement($company, now()->startOfMonth(), now()->endOfMonth());
        $rev = collect($is['revenue'])->keyBy('code');

        $this->assertEqualsWithDelta(75000, (float) $rev[$sales->code]['amount'], 0.01);
    }
}
