<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use App\Services\PurchaseService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_receivable_and_payable_aging_buckets(): void
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
        $customer = Contact::create(['company_id' => $company->id, 'name' => 'Pelanggan', 'type' => 'customer', 'is_active' => true]);
        $supplier = Contact::create(['company_id' => $company->id, 'name' => 'Supplier', 'type' => 'supplier', 'is_active' => true]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 5000, 'sell_price' => 10000, 'stock' => 100,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        // Receivable: invoice due 45 days ago -> bucket 31_60.
        app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 4, 'unit_price' => 10000]],
            date: now()->subDays(50),
            dueDate: now()->subDays(45),
        );

        // Payable: purchase due 10 days ago -> bucket 1_30.
        app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 5000]],
            date: now()->subDays(15),
            dueDate: now()->subDays(10),
        );

        $reports = app(ReportService::class);

        $ar = $reports->receivableAging($company, now());
        $this->assertSame(40000.0, $ar['total']);
        $this->assertSame(40000.0, $ar['buckets']['31_60']);

        $ap = $reports->payableAging($company, now());
        $this->assertSame(50000.0, $ap['total']);
        $this->assertSame(50000.0, $ap['buckets']['1_30']);
    }
}
