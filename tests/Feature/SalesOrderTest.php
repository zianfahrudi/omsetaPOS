<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_order_converts_to_posted_invoice(): void
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
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 50,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = app(SalesOrderService::class);

        $order = $service->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000]],
        );

        $this->assertSame(50000.0, (float) $order->grand_total);
        $this->assertSame('confirmed', $order->status);
        // SO itself has no ledger impact yet.
        $this->assertSame(0.0, app(LedgerService::class)->balance($company->account('accounts_receivable')));

        $order = $service->convertToInvoice($order);

        $this->assertSame('invoiced', $order->status);
        $this->assertNotNull($order->sales_invoice_id);
        $this->assertSame(45, $product->refresh()->stock);
        $this->assertSame(50000.0, app(LedgerService::class)->balance($company->account('accounts_receivable')));
    }
}
