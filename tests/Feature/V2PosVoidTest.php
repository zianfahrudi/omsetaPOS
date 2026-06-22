<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2PosVoidTest extends TestCase
{
    use RefreshDatabase;

    private function makeSale(): array
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $store = $admin->accessibleStores()->firstOrFail();
        $product = Product::query()->where('store_id', $store->id)
            ->where('product_type', '!=', 'service')->where('stock', '>', 5)->firstOrFail();

        $sale = app(CheckoutService::class)->checkout(
            storeId: $store->id,
            cashierId: $admin->id,
            items: [['product_id' => $product->id, 'quantity' => 3]],
            paymentMethod: 'cash',
            paidAmount: 10000000,
        );

        return [$admin, $product->fresh(), $sale];
    }

    public function test_void_restores_stock_and_reverses_journal(): void
    {
        $this->seed();
        [$admin, $product, $sale] = $this->makeSale();

        $stockAfterSale = (int) $product->stock;
        $salesJournals = Journal::query()->where('reference', $sale->number)->count();
        $this->assertEquals(1, $salesJournals);

        $this->actingAs($admin)->post(route('v2.pos.transactions.void', $sale))
            ->assertRedirect(route('v2.pos.transactions.show', $sale));

        $this->assertEquals('void', $sale->fresh()->status);
        $this->assertEquals($stockAfterSale + 3, (int) $product->fresh()->stock);
        // Reversing journal posted.
        $this->assertTrue(Journal::query()->where('description', "Pembatalan penjualan {$sale->number}")->exists());
    }

    public function test_cashier_cannot_void(): void
    {
        $this->seed();
        [, , $sale] = $this->makeSale();
        $cashier = User::query()->where('role', 'cashier')->firstOrFail();

        $this->actingAs($cashier)->post(route('v2.pos.transactions.void', $sale))->assertForbidden();
        $this->assertNotEquals('void', $sale->fresh()->status);
    }

    public function test_receipt_renders(): void
    {
        $this->seed();
        [$admin, , $sale] = $this->makeSale();

        $this->actingAs($admin)->get(route('v2.pos.transactions.receipt', $sale))
            ->assertOk()
            ->assertSee($sale->number);
    }
}
