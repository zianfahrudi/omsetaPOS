<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use App\Services\BankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_flags_balanced_and_difference(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $bank = $company->account('bank');

        // Book balance 1.000.000 into bank.
        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $bank->id, 'debit' => 1000000],
                ['account_id' => $company->account('equity')->id, 'credit' => 1000000],
            ],
            type: 'general',
        );

        $service = app(BankReconciliationService::class);

        $balanced = $service->reconcile($company, $bank->id, now(), 1000000);
        $this->assertSame('balanced', $balanced->status);
        $this->assertSame(0.0, (float) $balanced->difference);

        $off = $service->reconcile($company, $bank->id, now(), 950000);
        $this->assertSame('unbalanced', $off->status);
        $this->assertSame(-50000.0, (float) $off->difference);
        $this->assertSame(1000000.0, (float) $off->book_balance);
    }
}
