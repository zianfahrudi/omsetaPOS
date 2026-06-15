<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Models\Company;
use App\Models\Journal;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingFilamentTest extends TestCase
{
    use RefreshDatabase;

    private function setupCompany(): Company
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        return $company;
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-acc@test.test',
            'password' => bcrypt('password'),
            'role' => 'superuser',
            'is_active' => true,
        ]);
    }

    public function test_account_and_journal_list_pages_render(): void
    {
        $this->setupCompany();
        $this->actingAs($this->admin());

        Livewire::test(ListAccounts::class)->assertOk();
        Livewire::test(ListJournals::class)->assertOk();
    }

    public function test_manual_journal_can_be_posted_from_filament(): void
    {
        $company = $this->setupCompany();
        $this->actingAs($this->admin());

        $cash = $company->account('cash');
        $sales = $company->account('sales');

        Livewire::test(CreateJournal::class)
            ->fillForm([
                'company_id' => $company->id,
                'date' => '2026-06-15',
                'description' => 'Jurnal umum manual',
                'lines' => [
                    ['account_id' => $cash->id, 'debit' => 50000, 'credit' => 0, 'memo' => null],
                    ['account_id' => $sales->id, 'debit' => 0, 'credit' => 50000, 'memo' => null],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('journals', [
            'company_id' => $company->id,
            'total_debit' => 50000,
            'total_credit' => 50000,
            'status' => 'posted',
        ]);

        $journal = Journal::where('company_id', $company->id)->latest('id')->first();
        $this->assertCount(2, $journal->lines);
    }
}
