<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\CheckoutService;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSplitAndPartialPaymentTest extends TestCase
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

    public function test_combined_payment_cash_and_transfer_splits_journal(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        // grand total 30.000 → bayar 20.000 cash + 10.000 transfer
        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'split',
            paidAmount: 0,
            payments: [
                ['method' => 'cash', 'amount' => 20000],
                ['method' => 'transfer', 'amount' => 10000],
            ],
        );

        $this->assertSame('split', $sale->payment_method);
        $this->assertEqualsWithDelta(0, (float) $sale->debt_amount, 0.001);
        $this->assertCount(2, $sale->payments);

        $ledger = app(LedgerService::class);
        $this->assertSame(20000.0, $ledger->balance($company->account('cash')));
        $this->assertSame(10000.0, $ledger->balance($company->account('bank')));
        $this->assertSame(30000.0, $ledger->balance($company->account('sales')));
    }

    public function test_combined_payment_with_cash_overpay_records_change(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        // grand 30.000 → transfer 10.000 + cash 25.000 (tender 35.000) → kembalian 5.000
        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'split',
            paidAmount: 0,
            payments: [
                ['method' => 'transfer', 'amount' => 10000],
                ['method' => 'cash', 'amount' => 25000],
            ],
        );

        $this->assertEqualsWithDelta(5000, (float) $sale->change_amount, 0.001);

        $ledger = app(LedgerService::class);
        // cash net = 25.000 - 5.000 kembalian = 20.000
        $this->assertSame(20000.0, $ledger->balance($company->account('cash')));
        $this->assertSame(10000.0, $ledger->balance($company->account('bank')));
        $this->assertSame(30000.0, $ledger->balance($company->account('sales')));
    }

    public function test_debt_partial_settlement_in_installments(): void
    {
        [$cashier, $store, $product, $company] = $this->fixture();
        $this->actingAs($cashier);

        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Hutang', 'phone' => '0811']);

        // grand 30.000; DP 10.000 cash → hutang 20.000
        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'cash',
            paidAmount: 10000,
            customerId: $customer->id,
            isDebt: true,
        );

        $this->assertEqualsWithDelta(20000, (float) $sale->debt_amount, 0.001);
        $this->assertEqualsWithDelta(20000, (float) $customer->refresh()->outstanding_debt, 0.001);

        $pos = app(PosService::class);

        // Cicilan 1: 8.000 via transfer → sisa 12.000
        $sale = $pos->markTransactionPaid($sale, 8000, 'transfer');
        $this->assertEqualsWithDelta(12000, (float) $sale->debt_amount, 0.001);
        $this->assertEqualsWithDelta(12000, (float) $customer->refresh()->outstanding_debt, 0.001);

        // Pelunasan sisa (tanpa amount) → 0
        $sale = $pos->markTransactionPaid($sale);
        $this->assertEqualsWithDelta(0, (float) $sale->debt_amount, 0.001);
        $this->assertEqualsWithDelta(0, (float) $customer->refresh()->outstanding_debt, 0.001);

        $ledger = app(LedgerService::class);
        // Piutang lunas, kas/bank menerima total 30.000 (10rb cash awal + 8rb transfer + 12rb cash)
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame(22000.0, $ledger->balance($company->account('cash')));   // 10.000 + 12.000
        $this->assertSame(8000.0, $ledger->balance($company->account('bank')));     // cicilan transfer
    }

    public function test_cashier_checkout_endpoint_accepts_combined_payment(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $response = $this->actingAs($cashier)->postJson(route('cashier.checkout'), [
            'store_id' => $store->id,
            'payment_method' => 'split',
            'paid_amount' => 0,
            'payments' => [
                ['method' => 'cash', 'amount' => 20000],
                ['method' => 'transfer', 'amount' => 10000],
            ],
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ]);

        $response->assertOk();
        $response->assertJsonPath('sale.payment_method', 'split');
        $response->assertJsonPath('sale.change_amount', 0);
        $this->assertCount(2, $response->json('sale.payments'));
    }

    public function test_partial_settlement_via_mark_paid_endpoint(): void
    {
        [$cashier, $store, $product] = $this->fixture();
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $customer = Customer::create(['store_id' => $store->id, 'name' => 'Hutang', 'phone' => '0822']);

        $this->actingAs($cashier);
        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $cashier->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'cash',
            paidAmount: 0,
            customerId: $customer->id,
            isDebt: true,
        );

        $this->assertEqualsWithDelta(30000, (float) $sale->debt_amount, 0.001);

        // Bayar sebagian 12.000 via transfer
        $response = $this->postJson(route('cashier.transactions.mark-paid', ['sale' => $sale->id]), [
            'amount' => 12000,
            'method' => 'transfer',
        ]);

        $response->assertOk();
        $response->assertJsonPath('sale.payment_status', 'belum_lunas');
        $response->assertJsonPath('sale.debt_amount', 18000);
    }
}
