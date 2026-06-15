<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashServiceTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        return $company;
    }

    public function test_cash_in_increases_cash_and_credits_counter(): void
    {
        $company = $this->company();
        $cash = $company->account('cash');
        $income = $company->account('other_income');

        app(CashService::class)->receive($company, $cash->id, $income->id, 250000, description: 'Setoran modal');

        $ledger = app(LedgerService::class);
        $this->assertSame(250000.0, $ledger->balance($cash));
        $this->assertSame(250000.0, $ledger->balance($income));
    }

    public function test_cash_out_decreases_cash_and_debits_expense(): void
    {
        $company = $this->company();
        $cash = $company->account('cash');
        $expense = $company->account('operating_expense');

        app(CashService::class)->pay($company, $cash->id, $expense->id, 75000, description: 'Bayar listrik');

        $ledger = app(LedgerService::class);
        $this->assertSame(-75000.0, $ledger->balance($cash));
        $this->assertSame(75000.0, $ledger->balance($expense));
    }

    public function test_transfer_moves_funds_between_accounts(): void
    {
        $company = $this->company();
        $cash = $company->account('cash');
        $bank = $company->account('bank');

        app(CashService::class)->receive($company, $cash->id, $company->account('other_income')->id, 500000);
        app(CashService::class)->transfer($company, $cash->id, $bank->id, 200000, description: 'Setor ke bank');

        $ledger = app(LedgerService::class);
        $this->assertSame(300000.0, $ledger->balance($cash));
        $this->assertSame(200000.0, $ledger->balance($bank));
    }
}
