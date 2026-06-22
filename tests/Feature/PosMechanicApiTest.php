<?php

namespace Tests\Feature;

use App\Http\Resources\SaleItemResource;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosMechanicApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fixture: company + CoA terpasang, kasir, toko, produk (goods & service),
     * dan employee aktif se-company.
     *
     * @return array{cashier:User, store:Store, company:Company, goods:Product, service:Product, employee:Employee}
     */
    private function fixture(): array
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $cashier = User::create([
            'name' => 'Kasir', 'email' => 'k@test.test',
            'password' => bcrypt('password'), 'role' => 'cashier', 'is_active' => true,
        ]);

        $store = Store::create([
            'company_id' => $company->id, 'owner_id' => $cashier->id,
            'name' => 'Toko', 'code' => 'T-1', 'is_active' => true,
        ]);

        // Kasir punya akses ke toko (pivot store_user) untuk web & API canAccessStore.
        $cashier->stores()->attach($store->id);

        $goods = Product::create([
            'store_id' => $store->id, 'name' => 'Barang', 'sku' => 'SKU-G',
            'cost_price' => 6000, 'sell_price' => 10000, 'stock' => 1000,
            'product_type' => 'goods', 'is_active' => true,
        ]);

        $service = Product::create([
            'store_id' => $store->id, 'name' => 'Jasa Servis', 'sku' => 'SKU-S',
            'cost_price' => 0, 'sell_price' => 50000, 'stock' => 1000,
            'product_type' => 'service', 'is_active' => true,
        ]);

        $employee = Employee::factory()->forCompany($company)->create();

        return compact('cashier', 'store', 'company', 'goods', 'service', 'employee');
    }

    /**
     * Task 4.3: employee_id yang tidak ada di tabel ditolak request layer (422 exists),
     * tidak ada Sale tersimpan.
     */
    public function test_web_checkout_rejects_nonexistent_employee_id_422(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['cashier']);

        $response = $this->postJson(route('cashier.checkout'), [
            'store_id' => $f['store']->id,
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                ['product_id' => $f['service']->id, 'quantity' => 1, 'employee_id' => 999999],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('sales', 0);
    }

    /**
     * Task 4.3: employee LOLOS rule exists (id ada) tapi ditolak domain di service
     * (nonaktif / company lain) → 422 'Petugas tidak valid', tanpa Sale tersimpan.
     */
    public function test_checkout_rejects_inactive_or_other_company_employee(): void
    {
        $f = $this->fixture();
        $this->actingAs($f['cashier']);

        // Employee nonaktif se-company: id ada (lolos exists) tapi domain menolak.
        $inactive = Employee::factory()->forCompany($f['company'])->inactive()->create();

        $response = $this->postJson(route('cashier.checkout'), [
            'store_id' => $f['store']->id,
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                ['product_id' => $f['service']->id, 'quantity' => 1, 'employee_id' => $inactive->id],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Petugas tidak valid untuk toko ini.']);
        $this->assertDatabaseCount('sales', 0);

        // Employee dari company lain: id ada (lolos exists) tapi domain menolak.
        $otherCompany = Company::create(['name' => 'Other Co', 'code' => 'OTH', 'currency' => 'IDR']);
        $otherEmployee = Employee::factory()->forCompany($otherCompany)->create();

        $response2 = $this->postJson(route('cashier.checkout'), [
            'store_id' => $f['store']->id,
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                ['product_id' => $f['service']->id, 'quantity' => 1, 'employee_id' => $otherEmployee->id],
            ],
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonFragment(['message' => 'Petugas tidak valid untuk toko ini.']);
        $this->assertDatabaseCount('sales', 0);
    }

    /**
     * Task 5.5 + Property 2 (jalur API): checkout API menyertakan employee per item;
     * item tanpa employee → employee_id/employee_name null.
     */
    public function test_api_checkout_includes_employee_in_items(): void
    {
        $f = $this->fixture();
        Sanctum::actingAs($f['cashier']);

        $response = $this->postJson(route('api.v1.checkout.store'), [
            'store_id' => $f['store']->id,
            'payment_method' => 'cash',
            'paid_amount' => 100000,
            'items' => [
                // item 0: bertaut employee valid
                ['product_id' => $f['service']->id, 'quantity' => 1, 'employee_id' => $f['employee']->id],
                // item 1: tanpa employee
                ['product_id' => $f['goods']->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.items.0.employee_id', $f['employee']->id);
        $response->assertJsonPath('data.items.0.employee_name', $f['employee']->name);

        // Item tanpa employee → kedua field null.
        $items = $response->json('data.items');
        $noEmployee = collect($items)->firstWhere('product_id', $f['goods']->id);
        $this->assertNotNull($noEmployee);
        $this->assertNull($noEmployee['employee_id']);
        $this->assertNull($noEmployee['employee_name']);
    }

    /**
     * Task 5.4
     * Feature: pos-mechanic-tracking, Property 5: Representasi item menyertakan petugas
     * Validates: Requirements 4.4, 6.3
     *
     * For any SaleItem (tertaut atau tidak), SaleItemResource menyertakan employee_id &
     * employee_name sesuai petugas; item tanpa petugas → kedua field null tanpa error.
     */
    public function test_property_sale_item_resource_includes_employee(): void
    {
        $request = request();

        for ($i = 0; $i < 100; $i++) {
            $linked = fake()->boolean();

            if ($linked) {
                $employee = Employee::factory()->create();
                $item = SaleItem::factory()->create(['employee_id' => $employee->id]);
            } else {
                $employee = null;
                $item = SaleItem::factory()->create(['employee_id' => null]);
            }

            $array = (new SaleItemResource($item->load('employee')))->toArray($request);

            if ($linked) {
                $this->assertSame($employee->id, $array['employee_id']);
                $this->assertSame($employee->name, $array['employee_name']);
            } else {
                $this->assertNull($array['employee_id']);
                $this->assertNull($array['employee_name']);
            }
        }
    }
}
