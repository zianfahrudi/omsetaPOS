<?php

namespace Tests\Feature;

use App\Filament\Pages\CashFlowReport;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\CashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashFlowFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_flow_page_renders(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        app(CashService::class)->receive(
            $company,
            $company->account('cash')->id,
            $company->account('other_income')->id,
            300000,
        );

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-cf@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(CashFlowReport::class)
            ->assertOk()
            ->assertSee('Saldo Akhir')
            ->assertSee('Arus Kas Bersih');
    }
}
