<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\CheckoutService;
use App\Services\PosService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesJournalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:User,1:Store,2:Product,3:Company}
     */
    private function fixture(): array
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
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 100,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        return [$cashier, $store, $product, $company];
    }

    public function test_cash_sale_posts_balanced_journal_with_revenue_and_cogs(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'cash',
            paidAmount: 30000,
        );

        $journal = Journal::where('source_type', $sale->getMorphClass())
            ->where('source_id', $sale->id)
            ->where('type', 'sales')
            ->first();

        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        // total = revenue legs (30.000) + COGS legs (18.000)
        $this->assertEquals(48000.0, (float) $journal->total_debit);

        $ledger = app(LedgerService::class);
        // Cash up by 30.000, sales up by 30.000
        $this->assertSame(30000.0, $ledger->balance($company->account('cash')));
        $this->assertSame(30000.0, $ledger->balance($company->account('sales')));
        // COGS 3 x 6.000 = 18.000, inventory down 18.000
        $this->assertSame(18000.0, $ledger->balance($company->account('cogs')));
        $this->assertSame(-18000.0, $ledger->balance($company->account('inventory')));
    }

    public function test_debt_sale_books_receivable(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 5000,
            customerName: 'Budi',
            customerPhone: '0811',
            isDebt: true,
        );

        $ledger = app(LedgerService::class);
        // grand total 20.000; paid 5.000 cash; debt 15.000 AR
        $this->assertSame(5000.0, $ledger->balance($company->account('cash')));
        $this->assertSame(15000.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame(20000.0, $ledger->balance($company->account('sales')));
    }

    public function test_debt_payment_clears_receivable(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 2]],
            paymentMethod: 'cash',
            paidAmount: 5000,
            customerName: 'Budi',
            customerPhone: '0811',
            isDebt: true,
        );

        app(PosService::class)->markTransactionPaid($sale);

        $ledger = app(LedgerService::class);
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame(20000.0, $ledger->balance($company->account('cash')));
    }

    public function test_full_refund_reverses_revenue_and_restores_inventory(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'cash',
            paidAmount: 30000,
        );

        $returned = $sale->items->map(fn ($item) => [
            'sale_item_id' => $item->id,
            'quantity' => $item->quantity,
        ])->all();

        $refund = app(RefundService::class)->refund(
            saleId: $sale->id,
            handledById: $cashier->id,
            type: 'full',
            returnedItems: $returned,
        );

        $journal = Journal::where('source_type', $refund->getMorphClass())
            ->where('source_id', $refund->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());

        $ledger = app(LedgerService::class);
        // Sale + full refund nets cash, COGS and inventory back to zero.
        $this->assertSame(0.0, $ledger->balance($company->account('cash')));
        $this->assertSame(0.0, $ledger->balance($company->account('cogs')));
        $this->assertSame(0.0, $ledger->balance($company->account('inventory')));
    }
}
