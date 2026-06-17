@php
    // Definisi menu: tiap grup punya ikon (path svg) + daftar item [route, label].
    $groups = [
        [
            'label' => 'Point of Sale',
            'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
            'items' => [
                ['v2.pos.transactions', 'Riwayat Transaksi'],
                ['v2.pos.sessions', 'Sesi Kasir'],
            ],
        ],
        [
            'label' => 'Penjualan',
            'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
            'items' => [
                ['v2.sales.quotations', 'Penawaran Harga'],
                ['v2.sales.orders', 'Pesanan Penjualan'],
                ['v2.sales.invoices', 'Faktur Penjualan'],
                ['v2.sales.receivables', 'Daftar Piutang'],
                ['v2.projects.index', 'Proyek'],
            ],
        ],
        [
            'label' => 'Pembelian',
            'icon' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
            'items' => [
                ['v2.purchase.requests', 'Permintaan Pembelian'],
                ['v2.purchase.orders', 'Pesanan Pembelian'],
                ['v2.purchase.invoices', 'Faktur Pembelian'],
                ['v2.purchase.payables', 'Daftar Hutang'],
            ],
        ],
        [
            'label' => 'Persediaan',
            'icon' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
            'items' => [
                ['v2.inventory.adjustments', 'Penyesuaian Stok'],
                ['v2.inventory.transfers', 'Pemindahan Barang'],
                ['v2.inventory.assemblies', 'Perakitan'],
                ['v2.inventory.consignments', 'Konsinyasi'],
                ['v2.inventory.stock-card', 'Kartu Stok'],
            ],
        ],
        [
            'label' => 'Kas & Bank',
            'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
            'items' => [
                ['v2.cash.transactions', 'Transaksi Kas'],
                ['v2.cash.giros', 'Giro Masuk'],
                ['v2.cash.reconciliations', 'Rekonsiliasi Bank'],
            ],
        ],
        [
            'label' => 'Akuntansi',
            'icon' => 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25',
            'items' => [
                ['v2.accounting.accounts', 'Daftar Akun'],
                ['v2.accounting.ledger', 'Buku Besar'],
                ['v2.accounting.journals', 'Jurnal'],
            ],
        ],
        [
            'label' => 'Laporan',
            'icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
            'items' => [
                ['v2.reports.balance-sheet', 'Neraca'],
                ['v2.reports.income-statement', 'Laba Rugi'],
                ['v2.reports.cash-flow', 'Arus Kas'],
                ['v2.reports.trial-balance', 'Neraca Saldo'],
                ['v2.reports.sales', 'Penjualan'],
                ['v2.reports.purchases', 'Pembelian'],
                ['v2.reports.inventory', 'Persediaan'],
                ['v2.reports.warehouse-stock', 'Stok per Gudang'],
                ['v2.reports.tax', 'Pajak'],
            ],
        ],
        [
            'label' => 'Data Master',
            'icon' => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
            'items' => [
                ['v2.stores.index', 'Outlet'],
                ['v2.products.index', 'Produk'],
                ['v2.contacts', 'Kontak'],
                ['v2.vehicles.index', 'Kendaraan'],
                ['v2.units.index', 'Satuan'],
                ['v2.warehouses.index', 'Gudang'],
            ],
        ],
        [
            'label' => 'Absensi & Payroll',
            'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'items' => [
                ['v2.payroll.dashboard', 'Dashboard Payroll'],
                ['v2.employees.index', 'Karyawan'],
                ['v2.shifts.index', 'Master Shift'],
                ['v2.schedules.index', 'Jadwal Shift'],
                ['v2.attendances.index', 'Absensi'],
                ['v2.attendances.weekly', 'Absensi Mingguan'],
                ['v2.payrolls.index', 'Generate Payroll'],
                ['v2.payrolls.recap.salary', 'Rekap Gaji'],
                ['v2.payrolls.recap.loan', 'Rekap Bon'],
            ],
        ],
        [
            'label' => 'Arisan',
            'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
            'items' => [
                ['v2.arisan.dashboard', 'Dashboard Arisan'],
                ['v2.arisan.index', 'Kelompok Arisan'],
            ],
        ],
        [
            'label' => 'Pengaturan & Wilayah',
            'icon' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.241.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.991l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
            'items' => [
                ['v2.departments.index', 'Departemen'],
                ['v2.currencies.index', 'Mata Uang'],
                ['v2.taxes.index', 'Pajak'],
                ['v2.assets.index', 'Harta Tetap'],
                ['v2.settings.project', 'Penawaran Proyek'],
                ['v2.regions.provinces', 'Provinsi'],
                ['v2.regions.regencies', 'Kabupaten/Kota'],
            ],
        ],
    ];

    $has = fn (string $r) => \Illuminate\Support\Facades\Route::has($r);
    $href = fn (string $r, string $label) => $has($r) ? route($r) : route('v2.soon', ['m' => $label]);
    // grup aktif jika salah satu item-nya route aktif
    $isGroupActive = function (array $items) {
        foreach ($items as [$r]) {
            if (\Illuminate\Support\Facades\Route::has($r) && request()->routeIs($r.'*')) {
                return true;
            }
        }
        return false;
    };
@endphp

<div class="space-y-1">
    {{-- Dashboard --}}
    <a href="{{ route('v2.dashboard') }}"
       @class([
           'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
           'bg-indigo-50 font-medium text-indigo-700' => request()->routeIs('v2.dashboard'),
           'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('v2.dashboard'),
       ])>
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 11.2 3.05a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
        Dashboard
    </a>

    @foreach ($groups as $group)
        @php($active = $isGroupActive($group['items']))
        <div x-data="{ open: @json($active) }">
            <button type="button" @click="open = !open"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $group['icon'] }}"/></svg>
                <span class="font-medium">{{ $group['label'] }}</span>
                <svg class="ml-auto h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
            <div x-show="open" x-collapse class="mt-0.5 space-y-0.5 pl-7">
                @foreach ($group['items'] as [$route, $label])
                    @php($itemActive = $has($route) && request()->routeIs($route.'*'))
                    <a href="{{ $href($route, $label) }}"
                       @class([
                           'block rounded-lg px-3 py-1.5 text-sm transition',
                           'bg-indigo-50 font-medium text-indigo-700' => $itemActive,
                           'text-slate-500 hover:bg-slate-100 hover:text-slate-900' => ! $itemActive,
                       ])>{{ $label }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
