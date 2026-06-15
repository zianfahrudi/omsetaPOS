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
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPurchaseAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_and_purchase_analysis_group_totals(): void
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
        $customer = Contact::create(['company_id' => $company->id, 'name' => 'PT Maju', 'type' => 'customer', 'is_active' => true]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'CV Sumber', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 10000, 'stock' => 100,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        // Credit invoice 3 @ 10.000.
        app(SalesInvoiceService::class)->create(
            company: $company, contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 10000]],
        );

        // POS sale 2 @ 10.000.
        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(
            storeId: $store->id, cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash', paidAmount: 20000,
        );

        // Purchase 10 @ 5.000.
        app(PurchaseService::class)->create(
            company: $company, contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000]],
        );

        $reports = app(ReportService::class);

        $sales = $reports->salesAnalysis($company, now()->startOfMonth(), now()->endOfMonth());
        // 3*10.000 + 2*10.000 = 50.000
        $this->assertSame(50000.0, $sales['total']);
        $this->assertSame(5, $sales['by_product']->firstWhere('label', 'Barang')['quantity']);
        $this->assertSame(2, $sales['by_customer']->count());

        $purchases = $reports->purchaseAnalysis($company, now()->startOfMonth(), now()->endOfMonth());
        $this->assertSame(50000.0, $purchases['total']);
        $this->assertSame('CV Sumber', $purchases['by_supplier']->first()['label']);
    }
}
