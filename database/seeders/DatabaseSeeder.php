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
            ['name' => 'Kopi Susu Botol', 'sku' => 'KOPI-SUSU', 'barcode' => '899100100001', 'image_url' => '/product-images/kopi-susu.svg', 'cost_price' => 7000, 'sell_price' => 12000, 'stock' => 80],
            ['name' => 'Roti Coklat', 'sku' => 'ROTI-COKLAT', 'barcode' => '899100100002', 'image_url' => '/product-images/roti-coklat.svg', 'cost_price' => 4500, 'sell_price' => 8000, 'stock' => 60],
            ['name' => 'Air Mineral 600ml', 'sku' => 'AIR-600', 'barcode' => '899100100003', 'image_url' => '/product-images/air-mineral.svg', 'cost_price' => 2500, 'sell_price' => 5000, 'stock' => 120],
            ['name' => 'Mie Instan Goreng', 'sku' => 'MIE-GORENG', 'barcode' => '899100100004', 'image_url' => '/product-images/mie-goreng.svg', 'cost_price' => 3000, 'sell_price' => 4500, 'stock' => 100],
            ['name' => 'Oli Mesin 10W-40 4L', 'sku' => 'BKL-OLI-10W40-4L', 'barcode' => '899200100001', 'cost_price' => 245000, 'sell_price' => 325000, 'stock' => 32],
            ['name' => 'Oli Mesin Full Synthetic 0W-20 4L', 'sku' => 'BKL-OLI-0W20-4L', 'barcode' => '899200100002', 'cost_price' => 520000, 'sell_price' => 685000, 'stock' => 18],
            ['name' => 'Filter Oli Toyota Avanza', 'sku' => 'BKL-FILTER-OLI-AVZ', 'barcode' => '899200100003', 'cost_price' => 28000, 'sell_price' => 45000, 'stock' => 80],
            ['name' => 'Filter Udara Honda Brio', 'sku' => 'BKL-FILTER-UDARA-BRIO', 'barcode' => '899200100004', 'cost_price' => 65000, 'sell_price' => 95000, 'stock' => 44],
            ['name' => 'Filter Kabin Innova', 'sku' => 'BKL-FILTER-KABIN-INN', 'barcode' => '899200100005', 'cost_price' => 55000, 'sell_price' => 85000, 'stock' => 38],
            ['name' => 'Busi Iridium Set 4 pcs', 'sku' => 'BKL-BUSI-IRIDIUM-4', 'barcode' => '899200100006', 'cost_price' => 420000, 'sell_price' => 560000, 'stock' => 15],
            ['name' => 'Busi Standar Set 4 pcs', 'sku' => 'BKL-BUSI-STD-4', 'barcode' => '899200100007', 'cost_price' => 90000, 'sell_price' => 140000, 'stock' => 36],
            ['name' => 'Kampas Rem Depan', 'sku' => 'BKL-KAMPAS-REM-DEPAN', 'barcode' => '899200100008', 'cost_price' => 210000, 'sell_price' => 315000, 'stock' => 28],
            ['name' => 'Kampas Rem Belakang', 'sku' => 'BKL-KAMPAS-REM-BELAKANG', 'barcode' => '899200100009', 'cost_price' => 185000, 'sell_price' => 275000, 'stock' => 22],
            ['name' => 'Minyak Rem DOT 4 1L', 'sku' => 'BKL-MINYAK-REM-DOT4', 'barcode' => '899200100010', 'cost_price' => 52000, 'sell_price' => 78000, 'stock' => 50],
            ['name' => 'Coolant Radiator 4L', 'sku' => 'BKL-COOLANT-4L', 'barcode' => '899200100011', 'cost_price' => 88000, 'sell_price' => 135000, 'stock' => 35],
            ['name' => 'Aki NS60 45Ah', 'sku' => 'BKL-AKI-NS60', 'barcode' => '899200100012', 'cost_price' => 720000, 'sell_price' => 950000, 'stock' => 12],
            ['name' => 'Aki DIN55 60Ah', 'sku' => 'BKL-AKI-DIN55', 'barcode' => '899200100013', 'cost_price' => 980000, 'sell_price' => 1250000, 'stock' => 8],
            ['name' => 'Wiper Frameless 22 Inch', 'sku' => 'BKL-WIPER-22', 'barcode' => '899200100014', 'cost_price' => 45000, 'sell_price' => 75000, 'stock' => 60],
            ['name' => 'Lampu LED H4 Set', 'sku' => 'BKL-LED-H4-SET', 'barcode' => '899200100015', 'cost_price' => 235000, 'sell_price' => 375000, 'stock' => 20],
            ['name' => 'Ban 185/65 R15', 'sku' => 'BKL-BAN-185-65-R15', 'barcode' => '899200100016', 'cost_price' => 650000, 'sell_price' => 875000, 'stock' => 16],
            ['name' => 'Ban 215/60 R17 SUV', 'sku' => 'BKL-BAN-215-60-R17', 'barcode' => '899200100017', 'cost_price' => 1180000, 'sell_price' => 1525000, 'stock' => 10],
            ['name' => 'Shockbreaker Depan Set', 'sku' => 'BKL-SHOCK-DEPAN-SET', 'barcode' => '899200100018', 'cost_price' => 1450000, 'sell_price' => 1950000, 'stock' => 6],
            ['name' => 'Shockbreaker Belakang Set', 'sku' => 'BKL-SHOCK-BELAKANG-SET', 'barcode' => '899200100019', 'cost_price' => 920000, 'sell_price' => 1280000, 'stock' => 8],
            ['name' => 'Timing Belt Kit', 'sku' => 'BKL-TIMING-BELT-KIT', 'barcode' => '899200100020', 'cost_price' => 1350000, 'sell_price' => 1850000, 'stock' => 5],
            ['name' => 'Radiator Assy', 'sku' => 'BKL-RADIATOR-ASSY', 'barcode' => '899200100021', 'cost_price' => 1750000, 'sell_price' => 2350000, 'stock' => 4],
            ['name' => 'Kompresor AC Mobil', 'sku' => 'BKL-KOMPRESOR-AC', 'barcode' => '899200100022', 'cost_price' => 2850000, 'sell_price' => 3650000, 'stock' => 3],
            ['name' => 'Freon AC R134a', 'sku' => 'BKL-FREON-R134A', 'barcode' => '899200100023', 'cost_price' => 85000, 'sell_price' => 135000, 'stock' => 40],
            ['name' => 'Jasa Spooring Balancing', 'sku' => 'BKL-JASA-SPOORING', 'barcode' => '899200100024', 'cost_price' => 0, 'sell_price' => 225000, 'fee_amount' => 0, 'product_service_fee' => 25000, 'stock' => 0, 'product_type' => 'service'],
            ['name' => 'Jasa Tune Up Mesin', 'sku' => 'BKL-JASA-TUNE-UP', 'barcode' => '899200100025', 'cost_price' => 0, 'sell_price' => 350000, 'fee_amount' => 0, 'product_service_fee' => 35000, 'stock' => 0, 'product_type' => 'service'],
            ['name' => 'Paket Service Berkala 40.000 KM', 'sku' => 'BKL-PAKET-SERVICE-40K', 'barcode' => '899200100026', 'cost_price' => 1250000, 'sell_price' => 1750000, 'fee_amount' => 50000, 'product_service_fee' => 75000, 'stock' => 20],
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
