<?php

namespace Tests\Feature;

use App\Filament\Resources\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\ListFixedAssets;
use App\Models\Company;
use App\Models\FixedAsset;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FixedAssetFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_asset_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-fa@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ListFixedAssets::class)->assertOk();

        Livewire::test(CreateFixedAsset::class)
            ->fillForm([
                'company_id' => $company->id,
                'name' => 'Kendaraan',
                'acquisition_date' => '2026-01-01',
                'acquisition_cost' => 24000000,
                'salvage_value' => 0,
                'useful_life_months' => 24,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $asset = FixedAsset::where('company_id', $company->id)->first();
        $this->assertNotNull($asset);
        $this->assertSame(1000000.0, $asset->monthlyDepreciation());
    }
}
