@extends('v2.layouts.app')
@section('title', 'Konsinyasi')
@section('heading', 'Penjualan Konsinyasi')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor…"
                   class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.inventory.consignments.create') }}" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Konsinyasi</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Penerima Titipan</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai Titipan</th>
                        <th class="px-4 py-3 text-right font-medium">Terjual</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $c)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('v2.inventory.consignments.show', $c) }}" class="font-medium text-indigo-600 hover:underline">{{ $c->number }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $c->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $c->consignee?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($c->total_cost) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">{{ $rp($c->total_sold) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $c->isOpen() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $c->isOpen() ? 'Berjalan' : 'Selesai' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada konsinyasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
