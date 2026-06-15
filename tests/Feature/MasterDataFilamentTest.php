<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Taxes\Pages\CreateTax;
use App\Filament\Resources\Taxes\Pages\ListTaxes;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Warehouses\Pages\ListWarehouses;
use App\Models\Company;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataFilamentTest extends TestCase
{
    use RefreshDatabase;

    private function boot(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'admin-md@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));
    }

    public function test_master_data_list_pages_render(): void
    {
        $this->boot();

        Livewire::test(ListContacts::class)->assertOk();
        Livewire::test(ListCategories::class)->assertOk();
        Livewire::test(ListUnits::class)->assertOk();
        Livewire::test(ListWarehouses::class)->assertOk();
        Livewire::test(ListTaxes::class)->assertOk();
    }

    public function test_contact_and_tax_create_pages_render(): void
    {
        $this->boot();

        Livewire::test(CreateContact::class)->assertOk();
        Livewire::test(CreateTax::class)->assertOk();
    }
}
