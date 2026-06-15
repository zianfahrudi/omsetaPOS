<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\PurchaseService;
use App\Services\PurchasePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseJournalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Contact,2:Product,3:Store}
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

        $supplier = Contact::create([
            'company_id' => $company->id, 'name' => 'Supplier A', 'type' => 'supplier', 'is_active' => true,
        ]);

        $product = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU1',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 10,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        return [$company, $supplier, $product, $store];
    }

    public function test_purchase_raises_inventory_payable_and_weighted_average_cost(): void
    {
        [$company, $supplier, $product] = $this->fixture();

        // Buy 10 @ 8.000 (tax 8.800). Old: 10 @ 6.000.
        $purchase = app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [[
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_cost' => 8000,
                'tax_amount' => 8800,
            ]],
        );

        $product->refresh();
        $this->assertSame(20, $product->stock);
        // Weighted average: (10*6000 + 10*8000) / 20 = 7000
        $this->assertSame('7000.00', (string) $product->cost_price);

        $ledger = app(LedgerService::class);
        $this->assertSame(80000.0, $ledger->balance($company->account('inventory')));
        $this->assertSame(8800.0, $ledger->balance($company->account('tax_input')));
        // Payable = goods 80.000 + tax 8.800 = 88.800
        $this->assertSame(88800.0, $ledger->balance($company->account('accounts_payable')));
        $this->assertSame(88800.0, (float) $purchase->grand_total);

        $supplier->refresh();
        $this->assertSame('88800.00', (string) $supplier->payable_balance);
    }

    public function test_purchase_payment_clears_payable(): void
    {
        [$company, $supplier, $product] = $this->fixture();

        $purchase = app(PurchaseService::class)->create(
            company: $company,
            contactId: $supplier->id,
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 8000]],
        );

        app(PurchasePaymentService::class)->pay($purchase, 40000, 'bank');

        $purchase->refresh();
        $this->assertSame(0.0, (float) $purchase->outstanding_amount);
        $this->assertSame('lunas', $purchase->paymentStatus());

        $ledger = app(LedgerService::class);
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_payable')));
        $this->assertSame(-40000.0, $ledger->balance($company->account('bank')));
    }
}
