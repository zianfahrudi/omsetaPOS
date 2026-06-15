<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\LedgerService;
use App\Services\GiroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiroTest extends TestCase
{
    use RefreshDatabase;

    private function setup2(): array
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);
        $customer = Contact::create([
            'company_id' => $company->id, 'name' => 'Pelanggan', 'type' => 'customer',
            'is_active' => true, 'receivable_balance' => 500000,
        ]);

        return [$company, $customer];
    }

    public function test_giro_received_then_cleared_moves_to_bank(): void
    {
        [$company, $customer] = $this->setup2();
        $service = app(GiroService::class);
        $ledger = app(LedgerService::class);

        $giro = $service->receive($company, $customer->id, 200000);
        // Dr Piutang Giro, Cr Piutang Usaha
        $this->assertSame(200000.0, $ledger->balance($company->account('giro_receivable')));
        $this->assertSame(-200000.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame('300000.00', (string) $customer->refresh()->receivable_balance);

        $service->clear($giro, $company->account('bank')->id);
        // Bank up, giro receivable back to 0
        $this->assertSame(200000.0, $ledger->balance($company->account('bank')));
        $this->assertSame(0.0, $ledger->balance($company->account('giro_receivable')));
        $this->assertSame('cleared', $giro->refresh()->status);
    }

    public function test_giro_rejected_restores_receivable(): void
    {
        [$company, $customer] = $this->setup2();
        $service = app(GiroService::class);
        $ledger = app(LedgerService::class);

        $giro = $service->receive($company, $customer->id, 150000);
        $service->reject($giro);

        // giro receivable back to 0, AR net back to 0 (was -150k then +150k)
        $this->assertSame(0.0, $ledger->balance($company->account('giro_receivable')));
        $this->assertSame(0.0, $ledger->balance($company->account('accounts_receivable')));
        $this->assertSame('rejected', $giro->refresh()->status);
        $this->assertSame('500000.00', (string) $customer->refresh()->receivable_balance);
    }
}
