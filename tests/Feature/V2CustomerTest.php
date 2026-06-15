<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_master_crud(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $store = $user->accessibleStores()->firstOrFail();

        $this->actingAs($user)->get(route('v2.customers.index'))->assertOk();
        $this->actingAs($user)->get(route('v2.customers.create'))->assertOk();

        $this->actingAs($user)->post(route('v2.customers.store'), [
            'store_id' => $store->id, 'name' => 'Budi Santoso', 'phone' => '0811000111',
        ])->assertRedirect(route('v2.customers.index'));

        $customer = Customer::query()->where('name', 'Budi Santoso')->firstOrFail();
        $this->assertEquals($store->id, $customer->store_id);

        $this->actingAs($user)->put(route('v2.customers.update', $customer), [
            'store_id' => $store->id, 'name' => 'Budi S.',
        ])->assertRedirect(route('v2.customers.index'));
        $this->assertEquals('Budi S.', $customer->fresh()->name);

        $this->actingAs($user)->delete(route('v2.customers.destroy', $customer))->assertRedirect(route('v2.customers.index'));
        $this->assertNull(Customer::query()->find($customer->id));
    }
}
