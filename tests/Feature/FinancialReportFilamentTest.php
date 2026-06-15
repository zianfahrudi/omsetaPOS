<?php

namespace Tests\Feature;

use App\Filament\Pages\BalanceSheetReport;
use App\Filament\Pages\IncomeStatementReport;
use App\Filament\Pages\TrialBalanceReport;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialReportFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_pages_render_with_data(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $company->account('cash')->id, 'debit' => 100000],
                ['account_id' => $company->account('sales')->id, 'credit' => 100000],
            ],
            type: 'sales',
        );

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-rep@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(BalanceSheetReport::class)->assertOk()->assertSee('Total Aset');
        Livewire::test(IncomeStatementReport::class)->assertOk()->assertSee('Laba (Rugi) Bersih');
        Livewire::test(TrialBalanceReport::class)->assertOk()->assertSee('Kas');
    }
}
