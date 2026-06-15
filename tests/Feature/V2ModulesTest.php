<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        foreach ([
            'v2.reports.cash-flow', 'v2.reports.sales', 'v2.reports.purchases', 'v2.reports.inventory', 'v2.reports.tax',
            'v2.accounting.ledger', 'v2.accounting.journals.create',
            'v2.inventory.adjustments.create', 'v2.inventory.transfers.create', 'v2.inventory.stock-card',
            'v2.pos.transactions', 'v2.pos.sessions',
        ] as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }

    public function test_manual_journal_posts(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $accounts = Account::query()->where('company_id', $company->id)->where('is_postable', true)->take(2)->get();

        $response = $this->actingAs($user)->post(route('v2.accounting.journals.store'), [
            'date' => now()->toDateString(),
            'description' => 'Tes jurnal manual',
            'lines' => [
                ['account_id' => $accounts[0]->id, 'debit' => 50000, 'credit' => 0, 'memo' => 'a'],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 50000, 'memo' => 'b'],
            ],
        ]);

        $journal = Journal::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('v2.accounting.journals.show', $journal));
        $this->assertEquals(50000, (float) $journal->total_debit);
        $this->assertTrue($journal->isBalanced());
    }

    public function test_manual_journal_rejects_unbalanced(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $accounts = Account::query()->where('company_id', $company->id)->where('is_postable', true)->take(2)->get();

        $this->actingAs($user)->post(route('v2.accounting.journals.store'), [
            'date' => now()->toDateString(),
            'lines' => [
                ['account_id' => $accounts[0]->id, 'debit' => 50000, 'credit' => 0],
                ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 40000],
            ],
        ])->assertSessionHasErrors('lines');
    }

    public function test_stock_adjustment_changes_stock(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->firstOrFail();

        $target = (int) $product->stock + 7;

        $this->actingAs($user)->post(route('v2.inventory.adjustments.store'), [
            'product_id' => $product->id, 'quantity_after' => $target, 'reason' => 'opname', 'date' => now()->toDateString(),
        ])->assertRedirect(route('v2.inventory.adjustments'));

        $this->assertEquals($target, (int) $product->fresh()->stock);
    }

    public function test_stock_transfer_between_warehouses(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $from = $company->defaultWarehouse();
        $to = Warehouse::query()->create(['company_id' => $company->id, 'name' => 'Gudang Cabang', 'code' => 'GC', 'is_active' => true]);

        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->where('stock', '>', 5)->firstOrFail();

        $fromBefore = app(WarehouseStockService::class)->quantity($from->id, $product->id);
        $this->assertGreaterThanOrEqual(3, $fromBefore);

        $this->actingAs($user)->post(route('v2.inventory.transfers.store'), [
            'from_warehouse_id' => $from->id, 'to_warehouse_id' => $to->id, 'date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertRedirect(route('v2.inventory.transfers'));

        $this->assertEquals(3, app(WarehouseStockService::class)->quantity($to->id, $product->id));
    }
}
