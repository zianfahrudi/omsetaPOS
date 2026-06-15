<?php

namespace Tests\Feature;

use App\Models\Company;
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

    public function test_assembly_consumes_components_and_produces_finished_good(): void
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

        $compA = Product::create(['store_id' => $store->id, 'name' => 'Komponen A', 'sku' => 'A', 'cost_price' => 3000, 'sell_price' => 0, 'stock' => 10, 'product_type' => 'goods', 'is_active' => true]);
        $compB = Product::create(['store_id' => $store->id, 'name' => 'Komponen B', 'sku' => 'B', 'cost_price' => 2000, 'sell_price' => 0, 'stock' => 10, 'product_type' => 'goods', 'is_active' => true]);
        $finished = Product::create(['store_id' => $store->id, 'name' => 'Produk Jadi', 'sku' => 'F', 'cost_price' => 0, 'sell_price' => 20000, 'stock' => 0, 'product_type' => 'goods', 'is_active' => true]);

        // Build 2 finished, each from 1 A + 2 B. Total components: 2 A (6.000) + 4 B (8.000) = 14.000.
        app(AssemblyService::class)->create(
            company: $company,
            finishedProductId: $finished->id,
            quantity: 2,
            components: [
                ['product_id' => $compA->id, 'quantity' => 2],
                ['product_id' => $compB->id, 'quantity' => 4],
            ],
        );

        $this->assertSame(8, $compA->refresh()->stock);
        $this->assertSame(6, $compB->refresh()->stock);

        $finished->refresh();
        $this->assertSame(2, $finished->stock);
        // unit cost = 14.000 / 2 = 7.000
        $this->assertSame('7000.00', (string) $finished->cost_price);
    }
}
