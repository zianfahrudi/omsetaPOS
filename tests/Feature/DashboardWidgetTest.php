<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinancialOverview;
use App\Filament\Widgets\FinancialPosition;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_widgets_render(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $company->account('cash')->id, 'debit' => 500000],
                ['account_id' => $company->account('sales')->id, 'credit' => 500000],
            ],
            type: 'sales',
        );

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-dash@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(FinancialOverview::class)
            ->assertOk()
            ->assertSee('Saldo Kas & Bank')
            ->assertSee('Penjualan Bulan Ini');

        Livewire::test(FinancialPosition::class)
            ->assertOk()
            ->assertSee('Total Aset')
            ->assertSee('Total Ekuitas');
    }
}
