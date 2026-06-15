<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\SalesInvoicePaymentService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Contact,2:Product}
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
        $customer = Contact::create([
            'company_id' => $company->id, 'name' => 'PT Pelanggan', 'type' => 'customer', 'is_active' => true,
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 50,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        return [$company, $customer, $product];
    }

    public function test_invoice_books_receivable_revenue_and_cogs(): void
    {
        [$company, $customer, $product] = $this->fixture();

        // Sell 10 @ 10.000 + tax 11.000.
        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 10000, 'tax_amount' => 11000]],
        );

        $this->assertSame(40, $product->refresh()->stock);
        $this->assertSame(111000.0, (float) $invoice->grand_total);

        $ledger = app(LedgerService::class);
        $this->assertSame(111000.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame(100000.0, $ledger->balance($company->account('sales')));
        $this->assertSame(11000.0, $ledger->balance($company->account('tax_output')));
        $this->assertSame(60000.0, $ledger->balance($company->account('cogs')));
        $this->assertSame(-60000.0, $ledger->balance($company->account('inventory')));

        $customer->refresh();
        $this->assertSame('111000.00', (string) $customer->receivable_balance);
    }

    public function test_invoice_payment_clears_receivable(): void
    {
        [$company, $customer, $product] = $this->fixture();

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10000]],
        );

        app(SalesInvoicePaymentService::class)->pay($invoice, 20000, 'bank');

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->outstanding_amount);
        $this->assertSame('lunas', $invoice->paymentStatus());

        $ledger = app(LedgerService::class);
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame(20000.0, $ledger->balance($company->account('bank')));
    }
}
