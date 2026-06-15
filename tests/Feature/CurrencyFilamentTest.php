<?php

namespace Tests\Feature;

use App\Filament\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Resources\Currencies\Pages\ListCurrencies;
use App\Models\Company;
use App\Models\Currency;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_currency_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-cur@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ListCurrencies::class)->assertOk();

        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'company_id' => $company->id,
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 16000,
                'is_default' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('currencies', ['company_id' => $company->id, 'code' => 'USD']);
        $this->assertSame(1, Currency::where('company_id', $company->id)->count());
    }
}
