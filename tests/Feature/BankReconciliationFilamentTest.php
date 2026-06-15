<?php

namespace Tests\Feature;

use App\Filament\Resources\BankReconciliations\Pages\CreateBankReconciliation;
use App\Filament\Resources\BankReconciliations\Pages\ListBankReconciliations;
use App\Models\BankReconciliation;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankReconciliationFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-rek@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ListBankReconciliations::class)->assertOk();

        Livewire::test(CreateBankReconciliation::class)
            ->fillForm([
                'company_id' => $company->id,
                'account_id' => $company->account('bank')->id,
                'statement_date' => '2026-06-15',
                'statement_balance' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, BankReconciliation::where('company_id', $company->id)->count());
        $this->assertSame('balanced', BankReconciliation::first()->status);
    }
}
