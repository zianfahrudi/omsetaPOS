<?php

namespace Tests\Feature;

use App\Filament\Pages\TaxReport;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxReportFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_report_page_renders(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-tax@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(TaxReport::class)
            ->assertOk()
            ->assertSee('PPN Keluaran')
            ->assertSee('PPN Masukan');
    }
}
