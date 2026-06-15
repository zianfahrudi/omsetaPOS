<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\SalesInvoiceService;
use App\Services\SalesReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_return_restores_stock_reduces_receivable_and_revenue(): void
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

        // Sell 10 @ 10.000 -> AR 100.000, stock 40, COGS 60.000.
        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 10000]],
        );

        // Return 4 @ 10.000 = 40.000 (cost 4*6000 = 24.000).
        app(SalesReturnService::class)->create(
            invoice: $invoice,
            items: [['product_id' => $product->id, 'quantity' => 4]],
        );

        $this->assertSame(44, $product->refresh()->stock);

        $ledger = app(LedgerService::class);
        // AR 100.000 - 40.000 = 60.000
        $this->assertSame(60000.0, $ledger->balance($company->account('accounts_receivable')));
        // sales_return contra 40.000 (debit, revenue normal credit -> negative balance)
        $this->assertSame(-40000.0, $ledger->balance($company->account('sales_return')));
        // inventory: -60.000 + 24.000 = -36.000
        $this->assertSame(-36000.0, $ledger->balance($company->account('inventory')));
        // COGS: 60.000 - 24.000 = 36.000
        $this->assertSame(36000.0, $ledger->balance($company->account('cogs')));

        $invoice->refresh();
        $this->assertSame(60000.0, (float) $invoice->outstanding_amount);
    }
}
