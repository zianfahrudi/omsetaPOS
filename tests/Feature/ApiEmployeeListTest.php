<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEmployeeListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{company: Company, store: Store, cashier: User}
     */
    private function fixture(): array
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        $cashier = User::create([
            'name' => 'Kasir', 'email' => 'k@test.test',
            'password' => bcrypt('password'), 'role' => 'cashier', 'is_active' => true,
        ]);
        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $cashier->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);
        $cashier->stores()->attach($store->id);

        return compact('company', 'store', 'cashier');
    }

    public function test_api_lists_active_company_employees(): void
    {
        ['company' => $company, 'store' => $store, 'cashier' => $cashier] = $this->fixture();

        $budi = Employee::factory()->forCompany($company)->create(['name' => 'Budi Mekanik', 'code' => 'MK-001']);
        Employee::factory()->forCompany($company)->inactive()->create(['name' => 'Nonaktif']);
        $otherCompany = Company::create(['name' => 'Other', 'code' => 'OTH', 'currency' => 'IDR']);
        Employee::factory()->forCompany($otherCompany)->create(['name' => 'Luar']);

        Sanctum::actingAs($cashier);

        $response = $this->getJson(route('api.v1.employees.index', ['store_id' => $store->id]))
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Budi Mekanik'));
        $this->assertFalse($names->contains('Nonaktif'));
        $this->assertFalse($names->contains('Luar'));
        $response->assertJsonPath('data.0.code', 'MK-001');

        // Filter q.
        $this->getJson(route('api.v1.employees.index', ['store_id' => $store->id, 'q' => 'budi']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $budi->id);
    }

    public function test_api_employees_forbidden_for_other_store(): void
    {
        $this->fixture();
        $outsider = User::create([
            'name' => 'Lain', 'email' => 'x@test.test',
            'password' => bcrypt('password'), 'role' => 'cashier', 'is_active' => true,
        ]);
        $otherCompany = Company::create(['name' => 'C2', 'code' => 'C2', 'currency' => 'IDR']);
        $otherStore = Store::create([
            'company_id' => $otherCompany->id, 'owner_id' => $outsider->id,
            'name' => 'Toko2', 'code' => 'T-2', 'is_active' => true,
        ]);

        Sanctum::actingAs($outsider);

        // Outsider mencoba akses store milik fixture (id 1) → 403.
        $this->getJson(route('api.v1.employees.index', ['store_id' => 1]))
            ->assertForbidden();
    }
}
