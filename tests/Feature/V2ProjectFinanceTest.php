<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2ProjectFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_costs_and_profit(): void
    {
        $this->seed();
        $user = User::query()->where('role', 'admin')->firstOrFail();
        $company = Company::query()->firstOrFail();

        // Buat proyek via form store.
        $this->actingAs($user)->post(route('v2.projects.store'), [
            'name' => 'Renovasi Toko', 'status' => 'active',
            'contract_value' => 50000000, 'down_payment' => 20000000, 'is_active' => 1,
        ])->assertRedirect(route('v2.projects.index'));

        $project = Project::query()->where('name', 'Renovasi Toko')->firstOrFail();
        $this->assertEquals(30000000, $project->remainingBill()); // 50jt - 20jt DP

        $this->actingAs($user)->get(route('v2.projects.show', $project->id))->assertOk();

        $product = Product::query()->whereHas('store', fn ($q) => $q->where('company_id', $company->id))
            ->where('product_type', '!=', 'service')->firstOrFail();

        // Material (dari produk): 10 x 100.000.
        $this->actingAs($user)->post(route('v2.projects.costs.store', $project->id), [
            'type' => 'material', 'product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 100000,
        ])->assertRedirect();

        // Upah: 1 x 5.000.000.
        $this->actingAs($user)->post(route('v2.projects.costs.store', $project->id), [
            'type' => 'upah', 'description' => 'Tukang', 'quantity' => 1, 'unit_cost' => 5000000,
        ])->assertRedirect();

        // Operasional: 1 x 2.000.000.
        $this->actingAs($user)->post(route('v2.projects.costs.store', $project->id), [
            'type' => 'operasional', 'description' => 'Sewa alat', 'quantity' => 1, 'unit_cost' => 2000000,
        ])->assertRedirect();

        $project->load('costs');
        $this->assertEquals(1000000, $project->costByType('material'));
        $this->assertEquals(5000000, $project->costByType('upah'));
        $this->assertEquals(2000000, $project->costByType('operasional'));
        $this->assertEquals(8000000, $project->totalCost());
        // Laba sementara = 50jt - 8jt = 42jt.
        $this->assertEquals(42000000, $project->tentativeProfit());
    }
}
