<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use App\Services\CheckoutService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_statement_and_balance_sheet_reflect_purchase_and_sale(): void
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
        $supplier = Contact::create([
            'company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 0, 'sell_price' => 10000, 'stock' => 0,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        // Buy 10 @ 6.000 -> inventory 60.000, payable 60.000, cost 6.000.
        app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 6000]],
        );

        // Sell 5 @ 10.000 cash -> revenue 50.000, COGS 30.000.
        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 5]],
            paymentMethod: 'cash',
            paidAmount: 50000,
        );

        $reports = app(ReportService::class);

        $is = $reports->incomeStatement($company, now()->startOfMonth(), now()->endOfMonth());
        $this->assertSame(50000.0, $is['total_revenue']);
        $this->assertSame(30000.0, $is['total_expense']); // HPP
        $this->assertSame(20000.0, $is['net_income']);

        $bs = $reports->balanceSheet($company, now()->endOfDay());
        // assets: cash 50.000 + inventory 30.000 = 80.000
        $this->assertSame(80000.0, $bs['total_assets']);
        // liabilities: payable 60.000
        $this->assertSame(60000.0, $bs['total_liabilities']);
        // equity: net income 20.000
        $this->assertSame(20000.0, $bs['total_equity']);
        $this->assertTrue($bs['balanced']);
    }
}
