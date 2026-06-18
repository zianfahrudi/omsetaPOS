<?php

namespace App\Models;

use App\Support\ActiveStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_id', 'key', 'enabled'])]
class FeatureToggle extends Model
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * Modul + sub-menu yang bisa di-on/off per outlet.
     * Struktur: groupKey => ['label' => ..., 'items' => [routeName => label]].
     * Key item = nama route (dipakai juga untuk memfilter navigasi).
     *
     * @var array<string, array{label:string, items:array<string,string>}>
     */
    public const MODULES = [
        'pos' => ['label' => 'Point of Sale', 'items' => [
            'v2.pos.transactions' => 'Riwayat Transaksi',
            'v2.pos.sessions' => 'Sesi Kasir',
        ]],
        'sales' => ['label' => 'Penjualan', 'items' => [
            'v2.sales.quotations' => 'Penawaran Harga',
            'v2.sales.orders' => 'Pesanan Penjualan',
            'v2.sales.invoices' => 'Faktur Penjualan',
            'v2.sales.receivables' => 'Daftar Piutang',
            'v2.projects.index' => 'Proyek',
        ]],
        'purchase' => ['label' => 'Pembelian', 'items' => [
            'v2.purchase.requests' => 'Permintaan Pembelian',
            'v2.purchase.orders' => 'Pesanan Pembelian',
            'v2.purchase.invoices' => 'Faktur Pembelian',
            'v2.purchase.payables' => 'Daftar Hutang',
            'v2.purchase.materials' => 'Belanja Bahan',
        ]],
        'inventory' => ['label' => 'Persediaan', 'items' => [
            'v2.inventory.adjustments' => 'Penyesuaian Stok',
            'v2.inventory.transfers' => 'Pemindahan Barang',
            'v2.inventory.assemblies' => 'Perakitan',
            'v2.inventory.consignments' => 'Konsinyasi',
            'v2.inventory.stock-card' => 'Kartu Stok',
            'v2.purchase.materials.stock-card' => 'Kartu Stok Bahan',
        ]],
        'cash' => ['label' => 'Kas & Bank', 'items' => [
            'v2.cash.transactions' => 'Transaksi Kas',
            'v2.cash.giros' => 'Giro Masuk',
            'v2.cash.reconciliations' => 'Rekonsiliasi Bank',
        ]],
        'accounting' => ['label' => 'Akuntansi', 'items' => [
            'v2.accounting.accounts' => 'Daftar Akun',
            'v2.accounting.ledger' => 'Buku Besar',
            'v2.accounting.journals' => 'Jurnal',
            'v2.profit-sharing.index' => 'Bagi Hasil Laba',
            'v2.assets.index' => 'Harta Tetap',
        ]],
        'reports' => ['label' => 'Laporan', 'items' => [
            'v2.reports.balance-sheet' => 'Neraca',
            'v2.reports.income-statement' => 'Laba Rugi',
            'v2.reports.cash-flow' => 'Arus Kas',
            'v2.reports.cash-weekly' => 'Rekap Kas Mingguan',
            'v2.reports.trial-balance' => 'Neraca Saldo',
            'v2.reports.sales' => 'Penjualan',
            'v2.reports.purchases' => 'Pembelian',
            'v2.reports.inventory' => 'Persediaan',
            'v2.reports.warehouse-stock' => 'Stok per Gudang',
            'v2.reports.tax' => 'Pajak',
        ]],
        'master' => ['label' => 'Data Master', 'items' => [
            'v2.stores.index' => 'Outlet',
            'v2.products.index' => 'Produk',
            'v2.categories.index' => 'Kategori Produk',
            'v2.contacts' => 'Kontak',
            'v2.vehicles.index' => 'Kendaraan',
            'v2.units.index' => 'Satuan',
            'v2.warehouses.index' => 'Gudang',
            'v2.materials.index' => 'Master Material',
            'v2.material-categories.index' => 'Kategori Material',
            'v2.taxes.index' => 'Pajak',
            'v2.currencies.index' => 'Mata Uang',
            'v2.departments.index' => 'Departemen',
            'v2.positions.index' => 'Jabatan',
        ]],
        'payroll' => ['label' => 'Absensi & Payroll', 'items' => [
            'v2.payroll.dashboard' => 'Dashboard Payroll',
            'v2.employees.index' => 'Karyawan',
            'v2.shifts.index' => 'Master Shift',
            'v2.schedules.index' => 'Jadwal Shift',
            'v2.attendances.index' => 'Absensi',
            'v2.attendances.weekly' => 'Absensi Mingguan',
            'v2.payrolls.index' => 'Generate Payroll',
            'v2.payrolls.recap.salary' => 'Rekap Gaji',
            'v2.payrolls.recap.loan' => 'Rekap Bon',
        ]],
        'arisan' => ['label' => 'Arisan', 'items' => [
            'v2.arisan.dashboard' => 'Dashboard Arisan',
            'v2.arisan.index' => 'Kelompok Arisan',
        ]],
    ];

    /** @var array<int, array<string, bool>> cache per store_id */
    private static array $cache = [];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Semua key yang bisa diatur: key grup + key item (route name).
     *
     * @return array<int, string>
     */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::MODULES as $groupKey => $group) {
            $keys[] = $groupKey;
            foreach (array_keys($group['items']) as $itemKey) {
                $keys[] = $itemKey;
            }
        }

        return $keys;
    }

    /**
     * Apakah modul/menu aktif untuk outlet tertentu (default: outlet aktif).
     * Default true bila belum pernah diatur.
     */
    public static function enabled(string $key, ?int $storeId = null): bool
    {
        $storeId ??= ActiveStore::id();
        if (! $storeId) {
            return true; // tanpa konteks outlet, tampilkan semua
        }

        if (! array_key_exists($storeId, self::$cache)) {
            self::$cache[$storeId] = self::query()
                ->where('store_id', $storeId)
                ->pluck('enabled', 'key')
                ->map(fn ($v) => (bool) $v)
                ->all();
        }

        return self::$cache[$storeId][$key] ?? true;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}
