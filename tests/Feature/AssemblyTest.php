<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Material;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\AssemblyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssemblyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assembly_rolls_material_cost_into_finished_good(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $owner = User::create([
            'name' => 'Owner', 'email' => 'o@test.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $owner->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);

        $matA = Material::create(['company_id' => $company->id, 'name' => 'Material A', 'unit' => 'btg', 'price' => 3000, 'stock' => 100, 'is_active' => true]);
        $matB = Material::create(['company_id' => $company->id, 'name' => 'Material B', 'unit' => 'm2', 'price' => 2000, 'stock' => 100, 'is_active' => true]);
        $finished = Product::create(['store_id' => $store->id, 'name' => 'Produk Jadi', 'sku' => 'F', 'cost_price' => 0, 'sell_price' => 20000, 'stock' => 0, 'product_type' => 'goods', 'is_active' => true]);

        // Build 2 finished from 2 A (6.000) + 4 B (8.000) = 14.000.
        app(AssemblyService::class)->create(
            company: $company,
            finishedProductId: $finished->id,
            finishedProductName: null,
            quantity: 2,
            components: [
                ['material_id' => $matA->id, 'quantity' => 2],
                ['material_id' => $matB->id, 'quantity' => 4],
            ],
        );

        $finished->refresh();
        $this->assertSame(2, $finished->stock);
        // unit cost = 14.000 / 2 = 7.000
        $this->assertSame('7000.00', (string) $finished->cost_price);
    }

    public function test_assembly_creates_product_from_manual_finished(): void
    {
        $company = Company::create(['name' => 'Co2', 'code' => 'CO2', 'currency' => 'IDR']);
        $owner = User::create(['name' => 'O', 'email' => 'o2@test.test', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);
        Store::create(['company_id' => $company->id, 'owner_id' => $owner->id, 'name' => 'Toko', 'code' => 'T-2', 'is_active' => true]);
        $matA = Material::create(['company_id' => $company->id, 'name' => 'Material A', 'price' => 5000, 'stock' => 100, 'is_active' => true]);

        $assembly = app(AssemblyService::class)->create(
            company: $company,
            finishedProductId: null,
            finishedProductName: 'Kusen Aluminium Manual',
            quantity: 1,
            components: [['material_id' => $matA->id, 'quantity' => 3]],
        );

        // Produk baru otomatis dibuat di master, HPP = biaya material.
        $this->assertNotNull($assembly->product_id);
        $product = \App\Models\Product::findOrFail($assembly->product_id);
        $this->assertSame('Kusen Aluminium Manual', $product->name);
        $this->assertSame('15000.00', (string) $product->cost_price);
        $this->assertSame(1, (int) $product->stock);
        $this->assertSame('15000.00', (string) $assembly->total_cost);
    }
}
