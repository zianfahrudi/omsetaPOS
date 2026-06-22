<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosMechanicReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bangun dunia uji dengan TEPAT SATU Company (controller laporan memakai
     * Company::query()->first()), satu Store, admin + kasir, dan produk.
     *
     * @return array{company: Company, store: Store, admin: User, cashier: User}
     */
    private function world(): array
    {
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $cashier->stores()->attach($store->id);

        return compact('company', 'store', 'admin', 'cashier');
    }

    // ---------------------------------------------------------------------
    // Task 6.3 — endpoint daftar petugas
    // ---------------------------------------------------------------------

    public function test_employees_endpoint_returns_active_company_employees(): void
    {
        ['company' => $company, 'store' => $store, 'cashier' => $cashier] = $this->world();

        $budi = Employee::factory()->forCompany($company)->create(['name' => 'Budi Mekanik', 'code' => 'MK-001']);
        $sari = Employee::factory()->forCompany($company)->create(['name' => 'Sari Teknisi', 'code' => 'MK-002']);

        // Tidak boleh muncul: nonaktif (company sama) & aktif (company lain).
        $nonaktif = Employee::factory()->forCompany($company)->inactive()->create(['name' => 'Tono Nonaktif', 'code' => 'MK-OFF']);
        $companyLain = Company::factory()->create();
        $luar = Employee::factory()->forCompany($companyLain)->create(['name' => 'Andi Luar', 'code' => 'MK-EXT']);

        $response = $this->actingAs($cashier)
            ->getJson(route('cashier.employees', ['store_id' => $store->id]))
            ->assertOk();

        $ids = collect($response->json('employees'))->pluck('id');

        $this->assertTrue($ids->contains($budi->id));
        $this->assertTrue($ids->contains($sari->id));
        $this->assertFalse($ids->contains($nonaktif->id), 'Karyawan nonaktif tidak boleh muncul.');
        $this->assertFalse($ids->contains($luar->id), 'Karyawan company lain tidak boleh muncul.');
        $this->assertCount(2, $ids);

        // Filter q berdasarkan name.
        $byName = $this->actingAs($cashier)
            ->getJson(route('cashier.employees', ['store_id' => $store->id, 'q' => 'Budi']))
            ->assertOk();
        $this->assertEquals([$budi->id], collect($byName->json('employees'))->pluck('id')->all());

        // Filter q berdasarkan code.
        $byCode = $this->actingAs($cashier)
            ->getJson(route('cashier.employees', ['store_id' => $store->id, 'q' => 'MK-002']))
            ->assertOk();
        $this->assertEquals([$sari->id], collect($byCode->json('employees'))->pluck('id')->all());
    }

    public function test_employees_endpoint_empty_when_no_active_employee(): void
    {
        // Store dengan company tanpa karyawan aktif → daftar kosong, tetap 200.
        $company = Company::factory()->create();
        $store = Store::factory()->create(['company_id' => $company->id]);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $cashier->stores()->attach($store->id);

        Employee::factory()->forCompany($company)->inactive()->create();

        $response = $this->actingAs($cashier)
            ->getJson(route('cashier.employees', ['store_id' => $store->id]))
            ->assertOk();

        $this->assertSame([], $response->json('employees'));
    }

    // ---------------------------------------------------------------------
    // Task 8.6 — laporan performa mekanik: agregasi & akses
    // ---------------------------------------------------------------------

    public function test_mechanic_performance_report_aggregates_and_access(): void
    {
        ['company' => $company, 'store' => $store, 'admin' => $admin, 'cashier' => $cashier] = $this->world();

        $empA = Employee::factory()->forCompany($company)->create(['name' => 'Mekanik Alpha', 'code' => 'MK-A']);
        $empB = Employee::factory()->forCompany($company)->create(['name' => 'Mekanik Beta', 'code' => 'MK-B']);

        $inPeriod = now()->startOfMonth()->addDays(5)->setTime(12, 0);
        $outPeriod = now()->subMonths(2);

        // Sale 1 (completed, dalam periode): empA 2 jasa @100000, empB 1 jasa 75000.
        $sale1 = Sale::factory()->create(['store_id' => $store->id, 'status' => 'completed', 'paid_at' => $inPeriod]);
        $this->item($sale1, 'service', $empA->id, 100000);
        $this->item($sale1, 'service', $empA->id, 100000);
        $this->item($sale1, 'service', $empB->id, 75000);

        // Sale 2 (completed, dalam periode): empA 1 jasa 50000.
        $sale2 = Sale::factory()->create(['store_id' => $store->id, 'status' => 'completed', 'paid_at' => $inPeriod]);
        $this->item($sale2, 'service', $empA->id, 50000);

        // Dikecualikan: goods (bukan jasa), employee null, luar periode, non-completed.
        $sale3 = Sale::factory()->create(['store_id' => $store->id, 'status' => 'completed', 'paid_at' => $inPeriod]);
        $this->item($sale3, 'goods', $empA->id, 600000);            // bukan service
        $this->item($sale3, 'service', null, 700000);               // employee null

        $sale4 = Sale::factory()->create(['store_id' => $store->id, 'status' => 'completed', 'paid_at' => $outPeriod]);
        $this->item($sale4, 'service', $empA->id, 999999);          // luar periode

        $sale5 = Sale::factory()->create(['store_id' => $store->id, 'status' => 'void', 'paid_at' => $inPeriod]);
        $this->item($sale5, 'service', $empA->id, 888888);          // non-completed

        // Oracle: empA service_count=3 total=250000 sale_count=2; empB count=1 total=75000 sale_count=1.

        $params = [
            'from' => now()->startOfMonth()->toDateTimeString(),
            'to' => now()->endOfMonth()->toDateTimeString(),
        ];

        // Non-admin (kasir) → 403.
        $this->actingAs($cashier)
            ->get(route('v2.reports.mechanic-performance', $params))
            ->assertForbidden();

        // Admin → 200 dengan agregasi benar.
        $response = $this->actingAs($admin)
            ->get(route('v2.reports.mechanic-performance', $params))
            ->assertOk()
            ->assertSee('Mekanik Alpha')
            ->assertSee('Mekanik Beta')
            ->assertSee('Rp 250.000')   // service_total empA
            ->assertSee('Rp 75.000')    // service_total empB
            ->assertDontSee('999.999')  // luar periode
            ->assertDontSee('888.888'); // non-completed

        $rows = $response->viewData('rows')->keyBy('employee_id');

        $this->assertEquals(3, (int) $rows[$empA->id]->service_count);
        $this->assertEqualsWithDelta(250000, (float) $rows[$empA->id]->service_total, 0.01);
        $this->assertEquals(2, (int) $rows[$empA->id]->sale_count);

        $this->assertEquals(1, (int) $rows[$empB->id]->service_count);
        $this->assertEqualsWithDelta(75000, (float) $rows[$empB->id]->service_total, 0.01);
        $this->assertEquals(1, (int) $rows[$empB->id]->sale_count);

        $this->assertCount(2, $rows);

        // Req 5.6 — ganti periode (bulan lain) → hasil BERBEDA. Periode subMonths(2)
        // hanya memuat sale4 (empA 999999, completed di luar periode utama).
        $other = $this->actingAs($admin)
            ->get(route('v2.reports.mechanic-performance', [
                'from' => now()->subMonths(2)->startOfMonth()->toDateTimeString(),
                'to' => now()->subMonths(2)->endOfMonth()->toDateTimeString(),
            ]))
            ->assertOk()
            ->assertSee('Mekanik Alpha')
            ->assertDontSee('Mekanik Beta'); // empB tak ada transaksi di periode ini

        $otherRows = $other->viewData('rows');
        $this->assertCount(1, $otherRows);
        $this->assertEquals($empA->id, (int) $otherRows->first()->employee_id);
        $this->assertEqualsWithDelta(999999, (float) $otherRows->first()->service_total, 0.01);

        // Req 5.7 — periode tanpa data sama sekali → laporan kosong.
        $empty = $this->actingAs($admin)
            ->get(route('v2.reports.mechanic-performance', [
                'from' => now()->subYears(5)->startOfMonth()->toDateTimeString(),
                'to' => now()->subYears(5)->endOfMonth()->toDateTimeString(),
            ]))
            ->assertOk()
            ->assertSee('Tidak ada data');
        $this->assertCount(0, $empty->viewData('rows'));
    }

    // ---------------------------------------------------------------------
    // Task 8.5 — Property 4: agregasi laporan benar vs oracle (≥100 iterasi)
    // ---------------------------------------------------------------------

    // Feature: pos-mechanic-tracking, Property 4: rekap performa per petugas sama
    // dengan oracle referensi yang hanya menghitung item service bertaut petugas
    // dari Sale completed dalam periode (item null & sale di luar periode/non-completed
    // selalu dikecualikan; tanpa data → rekap kosong).
    // Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.2, 5.7
    public function test_property_report_matches_oracle(): void
    {
        ['company' => $company, 'store' => $store, 'admin' => $admin] = $this->world();

        $employees = Employee::factory()->forCompany($company)->count(3)->create();
        $empIds = $employees->pluck('id')->all();
        $employeeChoices = array_merge([null], $empIds); // null = tanpa petugas

        $product = Product::factory()->create(['store_id' => $store->id]);

        $periodFrom = now()->startOfMonth();
        $periodTo = now()->endOfMonth();
        $params = [
            'from' => $periodFrom->toDateTimeString(),
            'to' => $periodTo->toDateTimeString(),
        ];
        $statuses = ['completed', 'void', 'draft', 'pending'];
        $types = ['service', 'goods'];

        for ($iter = 0; $iter < 100; $iter++) {
            // Bersihkan dataset iterasi sebelumnya.
            SaleItem::query()->delete();
            Sale::query()->delete();

            /** @var array<int, array{count:int, total:float, sales:array<int,bool>}> $oracle */
            $oracle = [];

            $saleCount = random_int(0, 4);
            for ($s = 0; $s < $saleCount; $s++) {
                $status = $statuses[array_rand($statuses)];
                $inPeriod = (bool) random_int(0, 1);
                $paidAt = $inPeriod
                    ? (clone $periodFrom)->addDays(random_int(0, $periodFrom->daysInMonth - 1))->addHours(random_int(0, 23))
                    : (clone $periodFrom)->subMonths(random_int(1, 3))->addDays(random_int(0, 20));

                $sale = Sale::factory()->create([
                    'store_id' => $store->id,
                    'status' => $status,
                    'paid_at' => $paidAt,
                ]);

                $itemCount = random_int(0, 4);
                for ($i = 0; $i < $itemCount; $i++) {
                    $type = $types[array_rand($types)];
                    $employeeId = $employeeChoices[array_rand($employeeChoices)];
                    $lineTotal = random_int(1000, 500000);

                    SaleItem::factory()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_type' => $type,
                        'employee_id' => $employeeId,
                        'line_total' => $lineTotal,
                    ]);

                    // Oracle: hanya service + petugas non-null + completed + dalam periode.
                    $qualifies = $type === 'service'
                        && $employeeId !== null
                        && $status === 'completed'
                        && $paidAt->betweenIncluded($periodFrom, $periodTo);

                    if ($qualifies) {
                        $oracle[$employeeId] ??= ['count' => 0, 'total' => 0.0, 'sales' => []];
                        $oracle[$employeeId]['count']++;
                        $oracle[$employeeId]['total'] += $lineTotal;
                        $oracle[$employeeId]['sales'][$sale->id] = true;
                    }
                }
            }

            $rows = $this->actingAs($admin)
                ->get(route('v2.reports.mechanic-performance', $params))
                ->assertOk()
                ->viewData('rows')
                ->keyBy('employee_id');

            $this->assertCount(count($oracle), $rows, "Iterasi {$iter}: jumlah baris tidak cocok oracle.");

            foreach ($oracle as $empId => $expected) {
                $this->assertTrue($rows->has($empId), "Iterasi {$iter}: petugas {$empId} hilang dari rekap.");
                $row = $rows[$empId];
                $this->assertEquals($expected['count'], (int) $row->service_count, "Iterasi {$iter}: service_count emp {$empId}.");
                $this->assertEqualsWithDelta($expected['total'], (float) $row->service_total, 0.01, "Iterasi {$iter}: service_total emp {$empId}.");
                $this->assertEquals(count($expected['sales']), (int) $row->sale_count, "Iterasi {$iter}: sale_count emp {$empId}.");
            }
        }
    }

    private function item(Sale $sale, string $type, ?int $employeeId, float $lineTotal): SaleItem
    {
        return SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_type' => $type,
            'employee_id' => $employeeId,
            'line_total' => $lineTotal,
            'product_id' => null,
        ]);
    }
}
