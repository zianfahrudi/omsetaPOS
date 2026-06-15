<?php

namespace Tests\Feature;

use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2CashierSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_and_close_session(): void
    {
        $this->seed();
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $store = $admin->accessibleStores()->firstOrFail();

        $this->actingAs($admin)->get(route('v2.pos.sessions'))->assertOk();

        // Buka sesi.
        $this->actingAs($admin)->post(route('v2.pos.sessions.open'), [
            'store_id' => $store->id, 'opening_cash' => 200000,
        ])->assertRedirect(route('v2.pos.sessions'));

        $session = CashierSession::query()->where('user_id', $admin->id)->where('status', 'open')->firstOrFail();
        $this->assertEquals(200000, (float) $session->opening_cash);

        // Tidak boleh buka sesi kedua.
        $this->actingAs($admin)->post(route('v2.pos.sessions.open'), [
            'store_id' => $store->id, 'opening_cash' => 50000,
        ])->assertSessionHasErrors('opening_cash');

        // Tutup sesi.
        $this->actingAs($admin)->post(route('v2.pos.sessions.close', $session), [
            'closing_cash' => 200000,
        ])->assertRedirect(route('v2.pos.sessions'));

        $session->refresh();
        $this->assertEquals('closed', $session->status);
        $this->assertEquals(200000, (float) $session->expected_cash);
        $this->assertEquals(0.0, (float) $session->cash_difference);
    }
}
