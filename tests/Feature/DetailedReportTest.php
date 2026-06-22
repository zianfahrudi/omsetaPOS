<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\ReportService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetailedReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0:Company,1:Store,2:User}
     */
    private function world(): array
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

        return [$company, $store, $cashier];
    }

    public function test_balance_sheet_groups_bank_sub_accounts(): void
    {
        [$company] = $this->world();

        // Jadikan induk Bank non-postable, buat sub-akun BCA & BNI.
        $bank = Account::query()->where('company_id', $company->id)->where('subtype', 'bank')->firstOrFail();
        $bank->update(['is_postable' => false, 'subtype' => null]);

        $bca = Account::create([
            'company_id' => $company->id, 'parent_id' => $bank->id, 'code' => '1-1201',
            'name' => 'Bank BCA', 'type' => 'asset', 'subtype' => 'bank',
            'normal_balance' => 'debit', 'is_postable' => true, 'is_active' => true,
        ]);
        $bni = Account::create([
            'company_id' => $company->id, 'parent_id' => $bank->id, 'code' => '1-1202',
            'name' => 'Bank BNI', 'type' => 'asset', 'subtype' => null,
            'normal_balance' => 'debit', 'is_postable' => true, 'is_active' => true,
        ]);

        // Posting manual: terima uang di BCA 100.000 & BNI 40.000 (lawan modal).
        $equity = $company->account('equity');
        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $bca->id, 'debit' => 100000],
                ['account_id' => $bni->id, 'debit' => 40000],
                ['account_id' => $equity->id, 'credit' => 140000],
            ],
            type: 'general',
            description: 'Setoran modal',
        );

        $bs = app(ReportService::class)->balanceSheet($company, now()->endOfDay());

        // Ada kelompok "Bank" berisi 2 sub-akun dengan subtotal 140.000.
        $bankGroup = collect($bs['asset_groups'])->firstWhere('group_name', 'Bank');
        $this->assertNotNull($bankGroup, 'Kelompok Bank harus muncul di neraca.');
        $this->assertCount(2, $bankGroup['rows']);
        $this->assertSame(140000.0, $bankGroup['subtotal']);
    }

    public function test_sales_analysis_includes_by_category(): void
    {
        [$company, $store, $cashier] = $this->world();

        $catOli = Category::create(['company_id' => $company->id, 'name' => 'Oli']);
        $catBan = Category::create(['company_id' => $company->id, 'name' => 'Ban']);

        $oli = Product::create([
            'store_id' => $store->id, 'category_id' => $catOli->id, 'name' => 'Oli 1L', 'sku' => 'OLI',
            'cost_price' => 0, 'sell_price' => 50000, 'stock' => 100, 'product_type' => 'goods', 'is_active' => true,
        ]);
        $ban = Product::create([
            'store_id' => $store->id, 'category_id' => $catBan->id, 'name' => 'Ban Luar', 'sku' => 'BAN',
            'cost_price' => 0, 'sell_price' => 300000, 'stock' => 100, 'product_type' => 'goods', 'is_active' => true,
        ]);

        $this->actingAs($cashier);
        app(CheckoutService::class)->checkout(
            storeId: $store->id, cashierId: $cashier->id,
            items: [
                ['product_id' => $oli->id, 'quantity' => 2],   // 100.000
                ['product_id' => $ban->id, 'quantity' => 1],   // 300.000
            ],
            paymentMethod: 'cash', paidAmount: 400000,
        );

        $analysis = app(ReportService::class)->salesAnalysis($company, now()->startOfMonth(), now()->endOfMonth());

        $byCategory = collect($analysis['by_category'])->keyBy('label');
        $this->assertEqualsWithDelta(100000, (float) $byCategory['Oli']['total'], 0.01);
        $this->assertEqualsWithDelta(300000, (float) $byCategory['Ban']['total'], 0.01);
    }
}
