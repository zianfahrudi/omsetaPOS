<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Consignment;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2ConsignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        $this->actingAs($user)->get(route('v2.inventory.consignments'))->assertOk();
        $this->actingAs($user)->get(route('v2.inventory.consignments.create'))->assertOk();
    }

    public function test_ship_settle_and_return_flow(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $consignee = Contact::query()->create(['company_id' => $company->id, 'name' => 'Toko Mitra', 'type' => 'other', 'is_active' => true]);
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 20)->firstOrFail();
        $cash = Account::query()->where('company_id', $company->id)->where('subtype', 'cash')->firstOrFail();

        $before = (int) $product->stock;

        // Ship 10 units
        $this->actingAs($user)->post(route('v2.inventory.consignments.store'), [
            'contact_id' => $consignee->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 9000]],
        ])->assertRedirect();

        $consignment = Consignment::query()->latest('id')->firstOrFail();
        $item = $consignment->items->first();
        $this->assertEquals($before - 10, (int) $product->fresh()->stock);

        // Settle 6 sold
        $this->actingAs($user)->post(route('v2.inventory.consignments.settle', $consignment), [
            'cash_account_id' => $cash->id, 'date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'sold_quantity' => 6]],
        ])->assertRedirect(route('v2.inventory.consignments.show', $consignment));

        $this->assertEquals(54000, (float) $consignment->fresh()->total_sold);
        $this->assertEquals(6, (int) $item->fresh()->sold_quantity);

        // Return remaining 4 -> stock back, consignment closed
        $this->actingAs($user)->post(route('v2.inventory.consignments.return', $consignment), [
            'date' => now()->toDateString(),
            'lines' => [['item_id' => $item->id, 'quantity' => 4]],
        ])->assertRedirect(route('v2.inventory.consignments.show', $consignment));

        $this->assertEquals($before - 6, (int) $product->fresh()->stock);
        $this->assertEquals('closed', $consignment->fresh()->status);
        $this->assertTrue(Journal::query()->where('reference', $consignment->number)->count() >= 3);
    }
}
