@extends('v2.layouts.app')
@section('title', 'Pemindahan Barang')
@section('heading', 'Pemindahan Barang')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor…"
                   class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.inventory.transfers.create') }}" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Pemindahan</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Dari Gudang</th>
                        <th class="px-4 py-3 font-medium">Ke Gudang</th>
                        <th class="px-4 py-3 text-right font-medium">Jml Item</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $r->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $r->fromWarehouse?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $r->toWarehouse?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $r->items->count() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada pemindahan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
