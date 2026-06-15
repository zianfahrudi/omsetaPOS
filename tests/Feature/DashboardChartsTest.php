<?php

namespace Tests\Feature;

use App\Filament\Widgets\MonthlyCashFlowChart;
use App\Filament\Widgets\SalesDistributionChart;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_chart_widgets_render(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        app(CashService::class)->receive($company, $company->account('cash')->id, $company->account('other_income')->id, 100000);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-chart@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(MonthlyCashFlowChart::class)->assertOk();
        Livewire::test(SalesDistributionChart::class)->assertOk();
    }
}
