<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PosMechanicCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bangun konteks transaksi: company dengan akun terpasang (agar SalePoster
     * tidak error), kasir, dan toko. Pola mengikuti fixture SalesJournalTest.
     *
     * @return array{0:User,1:Store,2:Company}
     */
    private function context(): array
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

        $this->actingAs($cashier);

        return [$cashier, $store, $company];
    }

    private function checkout(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    // ------------------------------------------------------------------
    // Task 1.4 — smoke migrasi & relasi
    // ------------------------------------------------------------------

    public function test_sale_item_supports_nullable_employee_and_relations(): void
    {
        // Item tanpa petugas: kolom employee_id null, relasi employee null (backward compat).
        $itemTanpaPetugas = SaleItem::factory()->create(['employee_id' => null]);

        $this->assertNull($itemTanpaPetugas->employee_id);
        $this->assertNull($itemTanpaPetugas->employee);

        // Item dengan petugas: relasi employee resolve, dan handledSaleItems memuat item.
        $employee = Employee::factory()->create();
        $itemBertaut = SaleItem::factory()->create(['employee_id' => $employee->id]);

        $this->assertNotNull($itemBertaut->employee);
        $this->assertSame($employee->id, $itemBertaut->employee->id);
        $this->assertTrue(
            $employee->handledSaleItems()->pluck('id')->contains($itemBertaut->id)
        );
    }

    // ------------------------------------------------------------------
    // Task 3.7 — unit contoh grouping
    // ------------------------------------------------------------------

    public function test_same_product_different_mechanic_creates_two_lines(): void
    {
        [, $store, $company] = $this->context();

        $product = Product::factory()->goods()->create([
            'store_id' => $store->id, 'stock' => 9999, 'is_active' => true,
        ]);
        $empA = Employee::factory()->forCompany($company)->create(['is_active' => true]);
        $empB = Employee::factory()->forCompany($company)->create(['is_active' => true]);

        // Produk sama, mekanik berbeda → 2 baris terpisah, masing-masing qty 1.
        $sale = $this->checkout()->checkout(
            storeId: $store->id,
            cashierId: $store->owner_id,
            items: [
                ['product_id' => $product->id, 'quantity' => 1, 'employee_id' => $empA->id],
                ['product_id' => $product->id, 'quantity' => 1, 'employee_id' => $empB->id],
            ],
            paymentMethod: 'cash',
            paidAmount: 99_999_999,
        );

        $this->assertCount(2, $sale->items);
        $this->assertSame(1, (int) $sale->items->firstWhere('employee_id', $empA->id)->quantity);
        $this->assertSame(1, (int) $sale->items->firstWhere('employee_id', $empB->id)->quantity);

        // Produk sama + petugas sama → digabung jadi 1 baris qty 2.
        $sale2 = $this->checkout()->checkout(
            storeId: $store->id,
            cashierId: $store->owner_id,
            items: [
                ['product_id' => $product->id, 'quantity' => 1, 'employee_id' => $empA->id],
                ['product_id' => $product->id, 'quantity' => 1, 'employee_id' => $empA->id],
            ],
            paymentMethod: 'cash',
            paidAmount: 99_999_999,
        );

        $this->assertCount(1, $sale2->items);
        $this->assertSame(2, (int) $sale2->items->first()->quantity);
        $this->assertSame($empA->id, $sale2->items->first()->employee_id);
    }

    // ------------------------------------------------------------------
    // Task 3.4 — Property 1 (grouping cart)
    // ------------------------------------------------------------------

    public function test_property_cart_grouping_by_product_employee(): void
    {
        // Feature: pos-mechanic-tracking, Property 1: Grouping cart per (product_id, employee_id) — satu SaleItem per kombinasi unik, qty teragregasi, grand total tidak berubah oleh pemecahan baris.
        [, $store, $company] = $this->context();

        $products = collect(range(1, 3))->map(fn () => Product::factory()->goods()->create([
            'store_id' => $store->id, 'stock' => 99999, 'is_active' => true,
        ]));
        $employees = collect(range(1, 3))->map(fn () => Employee::factory()
            ->forCompany($company)->create(['is_active' => true]));

        // Pool pilihan employee_id: null + id valid.
        $employeeChoices = $employees->pluck('id')->push(null)->all();

        for ($iter = 0; $iter < 120; $iter++) {
            $rowCount = mt_rand(1, 8);
            $items = [];
            $expected = []; // key "product-employee" => total qty

            for ($r = 0; $r < $rowCount; $r++) {
                $productId = $products->random()->id;
                $employeeId = $employeeChoices[array_rand($employeeChoices)];
                $qty = mt_rand(1, 3);

                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'employee_id' => $employeeId,
                ];

                $key = $productId.'-'.($employeeId ?? 'null');
                $expected[$key] = ($expected[$key] ?? 0) + $qty;
            }

            $sale = $this->checkout()->checkout(
                storeId: $store->id,
                cashierId: $store->owner_id,
                items: $items,
                paymentMethod: 'cash',
                paidAmount: 999_999_999,
            );

            // Jumlah SaleItem == jumlah kombinasi unik (product_id, employee_id).
            $this->assertCount(count($expected), $sale->items, "Iterasi {$iter}: jumlah baris item tidak sesuai kombinasi unik.");

            // Qty tiap item == jumlah qty baris berkombinasi sama.
            foreach ($sale->items as $item) {
                $key = $item->product_id.'-'.($item->employee_id ?? 'null');
                $this->assertArrayHasKey($key, $expected, "Iterasi {$iter}: kombinasi tak terduga {$key}.");
                $this->assertSame($expected[$key], (int) $item->quantity, "Iterasi {$iter}: qty kombinasi {$key} salah.");
            }

            // Grand total == sum(unit_price * qty) — pemecahan baris tidak mengubah total.
            $expectedTotal = round($sale->items->sum(fn ($i) => (float) $i->unit_price * (int) $i->quantity), 2);
            $this->assertSame($expectedTotal, (float) $sale->grand_total, "Iterasi {$iter}: grand total berubah oleh pemecahan baris.");
        }
    }

    // ------------------------------------------------------------------
    // Task 3.5 — Property 2 (persistensi petugas per item)
    // ------------------------------------------------------------------

    public function test_property_employee_persisted_per_item(): void
    {
        // Feature: pos-mechanic-tracking, Property 2: Persistensi & pemetaan petugas per item — employee_id tersimpan persis (id valid → id, tanpa petugas → null).
        [, $store, $company] = $this->context();

        $products = collect(range(1, 3))->map(fn () => Product::factory()->goods()->create([
            'store_id' => $store->id, 'stock' => 99999, 'is_active' => true,
        ]));
        $employees = collect(range(1, 3))->map(fn () => Employee::factory()
            ->forCompany($company)->create(['is_active' => true]));

        // Campuran: id valid + null.
        $employeeChoices = $employees->pluck('id')->push(null)->push(null)->all();

        for ($iter = 0; $iter < 110; $iter++) {
            $rowCount = mt_rand(1, 6);
            $items = [];
            $expected = []; // key "product-employee" => employee_id (atau null)

            for ($r = 0; $r < $rowCount; $r++) {
                $productId = $products->random()->id;
                $employeeId = $employeeChoices[array_rand($employeeChoices)];

                $items[] = [
                    'product_id' => $productId,
                    'quantity' => mt_rand(1, 3),
                    'employee_id' => $employeeId,
                ];

                $expected[$productId.'-'.($employeeId ?? 'null')] = [
                    'product_id' => $productId,
                    'employee_id' => $employeeId,
                ];
            }

            $sale = $this->checkout()->checkout(
                storeId: $store->id,
                cashierId: $store->owner_id,
                items: $items,
                paymentMethod: 'cash',
                paidAmount: 999_999_999,
            );

            // Tiap kombinasi yang dikirim → baca SaleItem di DB, pastikan employee_id persis.
            foreach ($expected as $combo) {
                $row = SaleItem::query()
                    ->where('sale_id', $sale->id)
                    ->where('product_id', $combo['product_id'])
                    ->when(
                        $combo['employee_id'] === null,
                        fn ($q) => $q->whereNull('employee_id'),
                        fn ($q) => $q->where('employee_id', $combo['employee_id']),
                    )
                    ->first();

                $this->assertNotNull($row, "Iterasi {$iter}: SaleItem kombinasi tidak ditemukan.");
                $this->assertSame($combo['employee_id'], $row->employee_id, "Iterasi {$iter}: employee_id tersimpan tidak sesuai.");
            }
        }
    }

    // ------------------------------------------------------------------
    // Task 3.6 — Property 3 (penolakan petugas tidak valid)
    // ------------------------------------------------------------------

    public function test_property_invalid_employee_rejected(): void
    {
        // Feature: pos-mechanic-tracking, Property 3: Penolakan petugas tidak valid — checkout dengan petugas nonaktif/company lain/tak-ada ditolak & tidak ada Sale tersimpan.
        [, $store, $company] = $this->context();

        $product = Product::factory()->goods()->create([
            'store_id' => $store->id, 'stock' => 99999, 'is_active' => true,
        ]);

        // Sumber employee_id tidak valid.
        $inactive = Employee::factory()->forCompany($company)->inactive()->create();
        $otherCompany = Company::factory()->create();
        $foreign = Employee::factory()->forCompany($otherCompany)->create(['is_active' => true]);

        for ($iter = 0; $iter < 110; $iter++) {
            $invalidId = match (mt_rand(0, 2)) {
                0 => $inactive->id,            // nonaktif
                1 => $foreign->id,             // company lain
                default => mt_rand(100000, 999999), // id acak tak ada
            };

            $before = Sale::count();

            try {
                $this->checkout()->checkout(
                    storeId: $store->id,
                    cashierId: $store->owner_id,
                    items: [
                        ['product_id' => $product->id, 'quantity' => mt_rand(1, 3), 'employee_id' => $invalidId],
                    ],
                    paymentMethod: 'cash',
                    paidAmount: 999_999_999,
                );

                $this->fail("Iterasi {$iter}: checkout seharusnya ditolak untuk employee_id {$invalidId}.");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Petugas tidak valid', $e->getMessage(), "Iterasi {$iter}: pesan kesalahan tidak sesuai.");
            }

            // Rollback penuh: tidak ada Sale baru tersimpan.
            $this->assertSame($before, Sale::count(), "Iterasi {$iter}: ada Sale tersimpan padahal checkout ditolak.");
        }

        $this->assertSame(0, Sale::count());
    }
}
