<?php

namespace Tests\Feature;

use App\Filament\Pages\PurchaseAnalysisReport;
use App\Filament\Pages\SalesAnalysisReport;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesPurchaseAnalysisFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_analysis_pages_render(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-an@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(SalesAnalysisReport::class)->assertOk()->assertSee('Total Penjualan Periode');
        Livewire::test(PurchaseAnalysisReport::class)->assertOk()->assertSee('Total Pembelian Periode');
    }
}
