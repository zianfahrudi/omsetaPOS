<?php

namespace Tests\Feature;

use App\Models\Assembly;
use App\Models\Company;
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

    public function test_assembly_consumes_components_and_produces_finished(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $goods = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 10)->take(2)->get();
        $finished = $goods[0];
        $component = $goods[1];

        $finishedBefore = (int) $finished->stock;
        $componentBefore = (int) $component->stock;

        $this->actingAs($user)->post(route('v2.inventory.assemblies.store'), [
            'product_id' => $finished->id,
            'quantity' => 2,
            'date' => now()->toDateString(),
            'components' => [
                ['product_id' => $component->id, 'quantity' => 3],
            ],
        ])->assertRedirect(route('v2.inventory.assemblies'));

        $this->assertEquals($finishedBefore + 2, (int) $finished->fresh()->stock);
        $this->assertEquals($componentBefore - 3, (int) $component->fresh()->stock);
        $this->assertEquals(1, Assembly::query()->count());
    }
}
