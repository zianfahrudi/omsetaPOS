<?php

namespace Tests\Feature;

use App\Filament\Resources\Assemblies\Pages\CreateAssembly;
use App\Filament\Resources\Assemblies\Pages\ListAssemblies;
use App\Models\Assembly;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssemblyFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assembly_created_from_filament(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'a-asm@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $admin->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $comp = Product::create(['store_id' => $store->id, 'name' => 'Komponen', 'sku' => 'C', 'cost_price' => 4000, 'sell_price' => 0, 'stock' => 20, 'product_type' => 'goods', 'is_active' => true]);
        $finished = Product::create(['store_id' => $store->id, 'name' => 'Produk Jadi', 'sku' => 'F', 'cost_price' => 0, 'sell_price' => 15000, 'stock' => 0, 'product_type' => 'goods', 'is_active' => true]);

        $this->actingAs($admin);

        Livewire::test(ListAssemblies::class)->assertOk();

        Livewire::test(CreateAssembly::class)
            ->fillForm([
                'company_id' => $company->id,
                'date' => '2026-06-15',
                'product_id' => $finished->id,
                'quantity' => 5,
                'components' => [
                    ['product_id' => $comp->id, 'quantity' => 10],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Assembly::where('company_id', $company->id)->count());
        $this->assertSame(5, $finished->refresh()->stock);
        $this->assertSame(10, $comp->refresh()->stock);
    }
}
