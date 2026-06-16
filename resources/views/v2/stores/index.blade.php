@extends('v2.layouts.app')
@section('title', 'Outlet')
@section('heading', 'Outlet / Toko')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / kode…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.stores.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Outlet</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Telepon</th>
                        <th class="px-4 py-3 text-right font-medium">Produk</th>
                        <th class="px-4 py-3 text-right font-medium">Transaksi</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stores as $store)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $store->name }}</p>
                                @if ($store->address)<p class="text-xs text-slate-400">{{ $store->address }}</p>@endif
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-500">{{ $store->code }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $store->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $store->products_count }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $store->sales_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $store->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                    {{ $store->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.stores.edit', $store) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route('v2.stores.destroy', $store) }}" onsubmit="return confirm('Hapus outlet ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada outlet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $stores->links() }}</div>
@endsection
