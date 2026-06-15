<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\ReportService;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_flow_summarises_inflows_and_outflows(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $cash = $company->account('cash');
        $service = app(CashService::class);

        // Cash in 500.000, cash out 120.000.
        $service->receive($company, $cash->id, $company->account('other_income')->id, 500000);
        $service->pay($company, $cash->id, $company->account('operating_expense')->id, 120000);

        $cf = app(ReportService::class)->cashFlow($company, now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(0.0, $cf['opening']);
        $this->assertSame(500000.0, $cf['total_in']);
        $this->assertSame(120000.0, $cf['total_out']);
        $this->assertSame(380000.0, $cf['net']);
        $this->assertSame(380000.0, $cf['closing']);
        $this->assertTrue($cf['groups']->isNotEmpty());
    }
}
