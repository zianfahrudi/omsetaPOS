<?php

namespace Tests\Feature;

use App\Models\FixedAsset;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        $this->actingAs($user)->get(route('v2.assets.index'))->assertOk();
        $this->actingAs($user)->get(route('v2.assets.create'))->assertOk();
    }

    public function test_register_and_depreciate(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        $this->actingAs($user)->post(route('v2.assets.store'), [
            'name' => 'Mesin CNC', 'code' => 'AST-001',
            'acquisition_date' => now()->toDateString(),
            'acquisition_cost' => 120000000, 'salvage_value' => 0, 'useful_life_months' => 60,
        ])->assertRedirect(route('v2.assets.index'));

        $asset = FixedAsset::query()->firstOrFail();
        $this->assertEquals(2000000, $asset->monthlyDepreciation());

        $this->actingAs($user)->post(route('v2.assets.depreciate', $asset))->assertRedirect();

        $asset->refresh();
        $this->assertEquals(2000000, (float) $asset->accumulated_depreciation);
        $this->assertEquals(118000000, $asset->bookValue());
        $this->assertTrue(Journal::query()->where('reference', $asset->code)->exists());
    }
}
