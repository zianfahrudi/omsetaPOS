<?php

namespace Tests\Feature;

use App\Models\Account;
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

class PaymentAccountSelectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Contact,2:Product,3:Account,4:Account}
     */
    private function fixture(): array
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        // Bank induk → non-postable; buat sub-akun BCA & BNI (subtype bank).
        $bank = Account::query()->where('company_id', $company->id)->where('subtype', 'bank')->firstOrFail();
        $bank->update(['is_postable' => false, 'subtype' => null]);

        $bca = Account::create([
            'company_id' => $company->id, 'parent_id' => $bank->id, 'code' => '1-1201',
            'name' => 'Bank BCA', 'type' => 'asset', 'subtype' => 'bank',
            'normal_balance' => 'debit', 'is_postable' => true, 'is_active' => true,
        ]);
        $bni = Account::create([
            'company_id' => $company->id, 'parent_id' => $bank->id, 'code' => '1-1202',
            'name' => 'Bank BNI', 'type' => 'asset', 'subtype' => 'bank',
            'normal_balance' => 'debit', 'is_postable' => true, 'is_active' => true,
        ]);

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

        return [$company, $customer, $product, $bca, $bni];
    }

    public function test_company_lists_cash_and_bank_payment_accounts(): void
    {
        [$company, , , $bca, $bni] = $this->fixture();

        $codes = $company->cashBankAccounts()->pluck('code');
        $this->assertTrue($codes->contains('1-1100')); // Kas
        $this->assertTrue($codes->contains($bca->code));
        $this->assertTrue($codes->contains($bni->code));
        // Induk Bank (non-postable) tidak masuk daftar pilihan.
        $this->assertFalse($codes->contains('1-1200'));
    }

    public function test_invoice_payment_lands_in_selected_bank_account(): void
    {
        [$company, $customer, $product, $bca, $bni] = $this->fixture();

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 10000]],
        );

        // Bayar penuh ke BCA.
        $payment = app(SalesInvoicePaymentService::class)->pay(
            invoice: $invoice,
            amount: 100000,
            method: 'bank',
            accountId: $bca->id,
        );

        $this->assertSame($bca->id, $payment->account_id);

        $ledger = app(LedgerService::class);
        $this->assertSame(100000.0, $ledger->balance($bca->fresh()));
        $this->assertSame(0.0, $ledger->balance($bni->fresh()));
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_receivable')));
    }

    public function test_invoice_payment_falls_back_to_default_when_no_account_chosen(): void
    {
        [$company, $customer, $product, $bca] = $this->fixture();

        $invoice = app(SalesInvoiceService::class)->create(
            company: $company,
            contactId: $customer->id,
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10000]],
        );

        // Tanpa accountId → fallback ke akun default subtype 'cash' (Kas 1-1100).
        $payment = app(SalesInvoicePaymentService::class)->pay(
            invoice: $invoice,
            amount: 50000,
            method: 'cash',
        );

        $kas = $company->account('cash');
        $this->assertSame($kas->id, $payment->account_id);
        $this->assertSame(50000.0, app(LedgerService::class)->balance($kas));
    }
}
