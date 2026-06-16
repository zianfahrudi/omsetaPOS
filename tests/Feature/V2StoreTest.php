<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_outlet(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->get(route('v2.stores.index'))->assertOk();
        $this->actingAs($admin)->get(route('v2.stores.create'))->assertOk();

        $this->actingAs($admin)->post(route('v2.stores.store'), [
            'name' => 'Outlet Baru', 'code' => 'OUT-99', 'is_active' => 1,
        ])->assertRedirect(route('v2.stores.index'));

        $store = Store::query()->where('code', 'OUT-99')->firstOrFail();
        $this->assertTrue($admin->fresh()->canAccessStore($store->id));

        $this->actingAs($admin)->put(route('v2.stores.update', $store), [
            'name' => 'Outlet Update', 'code' => 'OUT-99', 'is_active' => 1,
        ])->assertRedirect(route('v2.stores.index'));
        $this->assertEquals('Outlet Update', $store->fresh()->name);

        $this->actingAs($admin)->delete(route('v2.stores.destroy', $store))->assertRedirect(route('v2.stores.index'));
        $this->assertNull(Store::query()->find($store->id));
    }

    public function test_cashier_cannot_manage_outlet(): void
    {
        $this->seed();
        $cashier = User::query()->where('role', 'cashier')->firstOrFail();

        $this->actingAs($cashier)->get(route('v2.stores.index'))->assertForbidden();
    }
}
