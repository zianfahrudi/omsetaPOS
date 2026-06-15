<?php

namespace Tests\Feature;

use App\Filament\Resources\CashierSessions\Pages\CreateCashierSession;
use App\Filament\Resources\CashierSessions\Pages\ListCashierSessions;
use App\Models\CashierSession;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashierSessionFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_opened_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $cashier = User::create([
            'name' => 'Kasir', 'email' => 'k-ses@test.test',
            'password' => bcrypt('password'), 'role' => 'cashier', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $cashier->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $cashier->stores()->attach($store->id, ['role' => 'cashier', 'is_default' => true]);

        $this->actingAs($cashier);

        Livewire::test(ListCashierSessions::class)->assertOk();

        Livewire::test(CreateCashierSession::class)
            ->fillForm([
                'store_id' => $store->id,
                'opening_cash' => 200000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $session = CashierSession::where('store_id', $store->id)->first();
        $this->assertNotNull($session);
        $this->assertSame('open', $session->status);
        $this->assertSame('200000.00', (string) $session->opening_cash);
    }
}
