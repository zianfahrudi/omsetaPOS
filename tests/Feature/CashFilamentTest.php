<?php

namespace Tests\Feature;

use App\Filament\Resources\CashTransactions\Pages\CreateCashTransaction;
use App\Filament\Resources\CashTransactions\Pages\ListCashTransactions;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_in_can_be_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-cash@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ListCashTransactions::class)->assertOk();

        Livewire::test(CreateCashTransaction::class)
            ->fillForm([
                'company_id' => $company->id,
                'type' => 'in',
                'date' => '2026-06-15',
                'account_id' => $company->account('cash')->id,
                'counter_account_id' => $company->account('other_income')->id,
                'amount' => 150000,
                'description' => 'Setoran modal',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cash_transactions', [
            'company_id' => $company->id,
            'type' => 'in',
            'amount' => 150000,
        ]);
        $this->assertSame(1, CashTransaction::where('company_id', $company->id)->count());
    }
}
