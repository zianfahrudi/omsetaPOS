<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2MasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        foreach (['units', 'warehouses', 'departments', 'projects', 'currencies', 'taxes'] as $base) {
            $this->actingAs($user)->get(route("v2.$base.index"))->assertOk();
            $this->actingAs($user)->get(route("v2.$base.create"))->assertOk();
        }
    }

    public function test_unit_crud_flow(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $this->actingAs($user)->post(route('v2.units.store'), [
            'name' => 'Karton', 'code' => 'CTN', 'is_active' => 1,
        ])->assertRedirect(route('v2.units.index'));

        $unit = Unit::query()->where('name', 'Karton')->firstOrFail();
        $this->assertEquals($company->id, $unit->company_id);

        $this->actingAs($user)->put(route('v2.units.update', $unit->id), [
            'name' => 'Karton Besar', 'code' => 'CTN', 'is_active' => 1,
        ])->assertRedirect(route('v2.units.index'));
        $this->assertEquals('Karton Besar', $unit->fresh()->name);

        $this->actingAs($user)->delete(route('v2.units.destroy', $unit->id))->assertRedirect(route('v2.units.index'));
        $this->assertNull(Unit::query()->find($unit->id));
    }

    public function test_tax_store_persists(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        $this->actingAs($user)->post(route('v2.taxes.store'), [
            'name' => 'PPN 12%', 'type' => 'ppn', 'rate' => 12, 'is_active' => 1,
        ])->assertRedirect(route('v2.taxes.index'));

        $this->assertTrue(Tax::query()->where('name', 'PPN 12%')->exists());
    }
}
