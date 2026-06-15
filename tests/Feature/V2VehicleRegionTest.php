<?php

namespace Tests\Feature;

use App\Models\CustomerVehicle;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2VehicleRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        foreach (['v2.vehicles.index', 'v2.vehicles.create', 'v2.regions.provinces', 'v2.regions.regencies', 'v2.customers.create'] as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }

    public function test_vehicle_crud(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $store = $user->accessibleStores()->firstOrFail();
        $customer = \App\Models\Customer::query()->create(['store_id' => $store->id, 'name' => 'Pemilik Tes']);

        $this->actingAs($user)->post(route('v2.vehicles.store'), [
            'store_id' => $store->id, 'customer_id' => $customer->id, 'plate_number' => 'dd 1234 xy', 'name' => 'Avanza',
        ])->assertRedirect(route('v2.vehicles.index'));

        $vehicle = CustomerVehicle::query()->firstOrFail();
        $this->assertEquals('DD 1234 XY', $vehicle->plate_number);

        $this->actingAs($user)->delete(route('v2.vehicles.destroy', $vehicle))->assertRedirect(route('v2.vehicles.index'));
        $this->assertNull(CustomerVehicle::query()->find($vehicle->id));
    }

    public function test_customer_with_region(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $store = $user->accessibleStores()->firstOrFail();

        $province = Province::query()->create(['code' => '99', 'name' => 'Provinsi Tes']);
        $regency = Regency::query()->create(['province_id' => $province->id, 'code' => '9901', 'name' => 'Kota Tes']);

        $this->actingAs($user)->post(route('v2.customers.store'), [
            'store_id' => $store->id, 'name' => 'Andi', 'province_id' => $province->id, 'regency_id' => $regency->id,
        ])->assertRedirect(route('v2.customers.index'));

        $customer = \App\Models\Customer::query()->where('name', 'Andi')->firstOrFail();
        $this->assertEquals($regency->id, $customer->regency_id);
        $this->assertEquals('Kota Tes', $customer->regency->name);
    }
}
