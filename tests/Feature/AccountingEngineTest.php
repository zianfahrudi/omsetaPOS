<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AccountingEngineTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        $company = Company::create([
            'name' => 'Test Co',
            'code' => 'TEST',
            'currency' => 'IDR',
        ]);

        app(ChartOfAccounts::class)->install($company);

        return $company;
    }

    public function test_chart_of_accounts_installs_with_headers_and_system_accounts(): void
    {
        $company = $this->company();

        $this->assertGreaterThan(20, $company->accounts()->count());

        // Header account is not postable.
        $header = Account::where('company_id', $company->id)->where('code', '1-0000')->first();
        $this->assertNotNull($header);
        $this->assertFalse($header->is_postable);

        // System accounts resolvable by subtype.
        foreach (['cash', 'bank', 'accounts_receivable', 'inventory', 'sales', 'cogs', 'tax_output'] as $subtype) {
            $this->assertNotNull($company->account($subtype), "Missing system account: {$subtype}");
        }

        $this->assertSame('debit', $company->account('cash')->normal_balance);
        $this->assertSame('credit', $company->account('sales')->normal_balance);
    }

    public function test_posts_a_balanced_journal_and_updates_ledger_balance(): void
    {
        $company = $this->company();
        $cash = $company->account('cash');
        $sales = $company->account('sales');

        $journal = app(PostingService::class)->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $cash->id, 'debit' => 150000],
                ['account_id' => $sales->id, 'credit' => 150000],
            ],
            type: 'sales',
            description: 'Penjualan tunai',
        );

        $this->assertSame('posted', $journal->status);
        $this->assertTrue($journal->isBalanced());
        $this->assertStringStartsWith('JJ/202606/', $journal->number);
        $this->assertCount(2, $journal->lines);

        $ledger = app(LedgerService::class);
        $this->assertSame(150000.0, $ledger->balance($cash));
        $this->assertSame(150000.0, $ledger->balance($sales));
    }

    public function test_rejects_unbalanced_journal(): void
    {
        $company = $this->company();

        $this->expectException(InvalidArgumentException::class);

        app(PostingService::class)->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $company->account('cash')->id, 'debit' => 100000],
                ['account_id' => $company->account('sales')->id, 'credit' => 90000],
            ],
        );
    }

    public function test_rejects_posting_to_header_account(): void
    {
        $company = $this->company();
        $header = Account::where('company_id', $company->id)->where('code', '1-0000')->first();
        $cash = $company->account('cash');

        $this->expectException(InvalidArgumentException::class);

        app(PostingService::class)->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $header->id, 'debit' => 50000],
                ['account_id' => $cash->id, 'credit' => 50000],
            ],
        );
    }

    public function test_reverse_nets_balances_to_zero(): void
    {
        $company = $this->company();
        $cash = $company->account('cash');
        $sales = $company->account('sales');
        $posting = app(PostingService::class);

        $journal = $posting->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $cash->id, 'debit' => 75000],
                ['account_id' => $sales->id, 'credit' => 75000],
            ],
        );

        $posting->reverse($journal, '2026-06-16');

        $ledger = app(LedgerService::class);
        $this->assertSame(0.0, $ledger->balance($cash));
        $this->assertSame(0.0, $ledger->balance($sales));
    }

    public function test_trial_balance_is_balanced(): void
    {
        $company = $this->company();
        $posting = app(PostingService::class);

        $posting->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $company->account('cash')->id, 'debit' => 200000],
                ['account_id' => $company->account('sales')->id, 'credit' => 200000],
            ],
        );
        $posting->post(
            company: $company,
            date: '2026-06-15',
            lines: [
                ['account_id' => $company->account('inventory')->id, 'debit' => 80000],
                ['account_id' => $company->account('cash')->id, 'credit' => 80000],
            ],
        );

        $rows = app(LedgerService::class)->trialBalance($company);
        $totalDebit = round($rows->sum('debit'), 2);
        $totalCredit = round($rows->sum('credit'), 2);

        $this->assertSame($totalDebit, $totalCredit);
        $this->assertGreaterThan(0, $totalDebit);
    }
}
