<?php

namespace Tests\Feature;

use App\Filament\Pages\PayableAgingReport;
use App\Filament\Pages\ReceivableAgingReport;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgingFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_aging_pages_render(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-age@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ReceivableAgingReport::class)->assertOk()->assertSee('Belum Jatuh Tempo');
        Livewire::test(PayableAgingReport::class)->assertOk()->assertSee('Supplier');
    }
}
