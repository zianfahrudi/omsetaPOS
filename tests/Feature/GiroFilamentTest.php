<?php

namespace Tests\Feature;

use App\Filament\Resources\Giros\Pages\CreateGiro;
use App\Filament\Resources\Giros\Pages\ListGiros;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Giro;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GiroFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_giro_received_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-giro@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $customer = Contact::create(['company_id' => $company->id, 'name' => 'Pelanggan', 'type' => 'customer', 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(ListGiros::class)->assertOk();

        Livewire::test(CreateGiro::class)
            ->fillForm([
                'company_id' => $company->id,
                'contact_id' => $customer->id,
                'date' => '2026-06-15',
                'amount' => 250000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('giros', [
            'company_id' => $company->id,
            'amount' => 250000,
            'status' => 'received',
        ]);
        $this->assertSame(1, Giro::where('company_id', $company->id)->count());
    }
}
