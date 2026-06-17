<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreCharge;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superuser = User::query()->updateOrCreate(
            ['email' => 'superuser@omsetapos.test'],
            [
                'name' => 'Superuser Omseta',
                'password' => Hash::make('password'),
                'role' => 'superuser',
                'is_active' => true,
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@omsetapos.test'],
            [
                'name' => 'Admin Toko',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $cashier = User::query()->updateOrCreate(
            ['email' => 'cashier@omsetapos.test'],
            [
                'name' => 'Kasir Demo',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
            ],
        );

        $company = Company::query()->updateOrCreate(
            ['code' => 'OMSETA'],
            [
                'name' => 'Omseta Group',
                'currency' => 'IDR',
                'phone' => '081234567890',
                'address' => 'Jl. Demo Bisnis No. 1',
                'book_opened_at' => now()->startOfYear(),
                'is_active' => true,
            ],
        );

        app(ChartOfAccounts::class)->install($company);

        foreach (['Pcs' => 'pcs', 'Kilogram' => 'kg', 'Liter' => 'ltr', 'Box' => 'box', 'Lusin' => 'lsn', 'Meter' => 'm', 'Unit' => 'unit'] as $unitName => $unitCode) {
            \App\Models\Unit::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $unitCode],
                ['name' => $unitName, 'is_active' => true],
            );
        }

        foreach (['Umum', 'Sparepart', 'Oli & Pelumas', 'Ban', 'Jasa', 'Aki', 'Filter'] as $categoryName) {
            \App\Models\Category::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $categoryName],
                ['is_active' => true],
            );
        }

        foreach (['Tukang', 'Helper', 'Mandor', 'Admin', 'Kasir', 'Sopir'] as $positionName) {
            \App\Models\Position::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $positionName],
                ['is_active' => true],
            );
        }

        foreach (['Aluminium', 'Kaca', 'Kusen', 'Aksesoris', 'Besi'] as $matCat) {
            \App\Models\MaterialCategory::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $matCat],
                ['is_active' => true],
            );
        }

        \App\Models\Warehouse::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'GDG-01'],
            ['name' => 'Gudang Utama', 'is_default' => true, 'is_active' => true],
        );

        \App\Models\Tax::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'PPN 11%'],
            [
                'account_id' => $company->account('tax_output')?->id,
                'type' => 'ppn',
                'rate' => 11,
                'is_active' => true,
            ],
        );

        \App\Models\Currency::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'IDR'],
            ['name' => 'Rupiah', 'symbol' => 'Rp', 'exchange_rate' => 1, 'is_default' => true, 'is_active' => true],
        );

        \App\Models\Contact::query()->updateOrCreate(
            ['company_id' => $company->id, 'code' => 'SUP-01'],
            [
                'name' => 'Supplier Sparepart Jaya',
                'type' => 'supplier',
                'phone' => '081200000001',
                'is_active' => true,
            ],
        );

        $mainStore = Store::query()->updateOrCreate(
            ['code' => 'OMSETA-01'],
            [
                'company_id' => $company->id,
                'owner_id' => $admin->id,
                'name' => 'Omseta Mart Pusat',
                'phone' => '081234567890',
                'address' => 'Jl. Demo Bisnis No. 1',
                'is_active' => true,
            ],
        );

        $branchStore = Store::query()->updateOrCreate(
            ['code' => 'OMSETA-02'],
            [
                'company_id' => $company->id,
                'owner_id' => $admin->id,
                'name' => 'Omseta Mart Cabang',
                'phone' => '081234567891',
                'address' => 'Jl. Cabang No. 2',
                'is_active' => true,
            ],
        );

        foreach ([$mainStore, $branchStore] as $store) {
            $store->users()->syncWithoutDetaching([
                $superuser->id => ['role' => 'superuser', 'is_default' => $store->is($mainStore)],
                $admin->id => ['role' => 'admin', 'is_default' => $store->is($mainStore)],
                $cashier->id => ['role' => 'cashier', 'is_default' => $store->is($mainStore)],
            ]);
        }

        $products = [
            // Bengkel
            ['name' => 'Oli Mesin 10W-40 4L', 'sku' => 'BKL-001', 'barcode' => '8993000000001', 'cost_price' => 245000, 'sell_price' => 325000, 'stock' => 40],
            ['name' => 'Filter Oli Avanza', 'sku' => 'BKL-002', 'barcode' => '8993000000002', 'cost_price' => 28000, 'sell_price' => 45000, 'stock' => 80],
            ['name' => 'Kampas Rem Depan', 'sku' => 'BKL-003', 'barcode' => '8993000000003', 'cost_price' => 210000, 'sell_price' => 315000, 'stock' => 30],
            ['name' => 'Aki NS60 45Ah', 'sku' => 'BKL-004', 'barcode' => '8993000000004', 'cost_price' => 720000, 'sell_price' => 950000, 'stock' => 15],
            ['name' => 'Jasa Tune Up Mesin', 'sku' => 'BKL-005', 'barcode' => '8993000000005', 'cost_price' => 0, 'sell_price' => 350000, 'stock' => 0, 'product_type' => 'service'],
            // Manufaktur Aluminium
            ['name' => 'Profil Aluminium 4 inci (batang 6m)', 'sku' => 'ALM-001', 'barcode' => '8994000000001', 'cost_price' => 185000, 'sell_price' => 245000, 'stock' => 120, 'unit' => 'btg'],
            ['name' => 'Kusen Aluminium Putih (set)', 'sku' => 'ALM-002', 'barcode' => '8994000000002', 'cost_price' => 420000, 'sell_price' => 575000, 'stock' => 35, 'unit' => 'set'],
            ['name' => 'Kaca Polos 5mm (m2)', 'sku' => 'ALM-003', 'barcode' => '8994000000003', 'cost_price' => 95000, 'sell_price' => 140000, 'stock' => 200, 'unit' => 'm2'],
            ['name' => 'Engsel Pintu Aluminium', 'sku' => 'ALM-004', 'barcode' => '8994000000004', 'cost_price' => 32000, 'sell_price' => 52000, 'stock' => 150, 'unit' => 'pcs'],
            ['name' => 'Jasa Pasang Kusen Aluminium', 'sku' => 'ALM-005', 'barcode' => '8994000000005', 'cost_price' => 0, 'sell_price' => 275000, 'stock' => 0, 'product_type' => 'service'],
        ];

        foreach ([$mainStore, $branchStore] as $store) {
            StoreCharge::query()->updateOrCreate(
                ['store_id' => $store->id],
                [
                    'tax_percentage' => 11,
                    'service_fee_percentage' => 5,
                    'is_tax_active' => true,
                    'is_service_fee_active' => false,
                ],
            );

            Discount::query()->updateOrCreate(
                ['store_id' => $store->id, 'code' => 'HEMAT10'],
                [
                    'name' => 'Diskon demo 10%',
                    'type' => 'percentage',
                    'value' => 10,
                    'minimum_spend' => 10000,
                    'is_active' => true,
                ],
            );

            foreach ($products as $product) {
                Product::query()->updateOrCreate(
                    ['store_id' => $store->id, 'sku' => $product['sku']],
                    $product + ['store_id' => $store->id, 'minimum_stock' => 10, 'unit' => 'pcs', 'is_active' => true],
                );
            }
        }

        // Seed per-warehouse stock so warehouse_stocks stays consistent with
        // products.stock (the migration backfill runs before products exist).
        $warehouse = $company->defaultWarehouse();
        if ($warehouse) {
            Product::query()
                ->whereIn('store_id', [$mainStore->id, $branchStore->id])
                ->where('stock', '>', 0)
                ->get()
                ->each(function (Product $product) use ($warehouse) {
                    \App\Models\WarehouseStock::query()->updateOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $product->id],
                        ['quantity' => (int) $product->stock],
                    );
                });
        }
    }
}
