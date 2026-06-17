<?php

namespace Tests\Feature;

use App\Models\Assembly;
use App\Models\Company;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2AssemblyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assembly_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        $this->actingAs($user)->get(route('v2.inventory.assemblies'))->assertOk();
        $this->actingAs($user)->get(route('v2.inventory.assemblies.create'))->assertOk();
    }

    public function test_assembly_from_material_produces_finished(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $finished = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->firstOrFail();
        $material = Material::create(['company_id' => $company->id, 'name' => 'Aluminium Batang', 'unit' => 'btg', 'price' => 150000, 'stock' => 100, 'is_active' => true]);

        $finishedBefore = (int) $finished->stock;

        $this->actingAs($user)->post(route('v2.inventory.assemblies.store'), [
            'product_id' => $finished->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
            'components' => [
                ['material_id' => $material->id, 'quantity' => 3],
            ],
        ])->assertRedirect(route('v2.inventory.assemblies'));

        $this->assertEquals($finishedBefore + 2, (int) $finished->fresh()->stock);
        $this->assertEquals(1, Assembly::query()->count());
        // 3 x 150.000 = 450.000
        $this->assertSame('450000.00', (string) Assembly::query()->first()->total_cost);
    }

    public function test_assembly_with_manual_finished(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $material = Material::create(['company_id' => $company->id, 'name' => 'Kaca 5mm', 'unit' => 'm2', 'price' => 100000, 'stock' => 100, 'is_active' => true]);

        $resp = $this->actingAs($user)->post(route('v2.inventory.assemblies.store'), [
            'product_name' => 'Etalase Kaca Custom',
            'quantity' => 1,
            'date' => now()->toDateString(),
            'components' => [
                ['material_id' => $material->id, 'quantity' => 4],
            ],
        ]);
        $resp->assertRedirect(route('v2.inventory.assemblies'));

        $assembly = Assembly::query()->firstOrFail();
        $this->assertNull($assembly->product_id);
        $this->assertSame('Etalase Kaca Custom', $assembly->product_name);
        $this->assertSame('400000.00', (string) $assembly->total_cost);
    }
}
