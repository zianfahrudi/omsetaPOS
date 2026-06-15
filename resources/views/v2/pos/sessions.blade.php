@extends('v2.layouts.app')
@section('title', 'Sesi Kasir')
@section('heading', 'Sesi Kasir')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor sesi…"
               class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Kasir</th>
                        <th class="px-4 py-3 font-medium">Buka</th>
                        <th class="px-4 py-3 font-medium">Tutup</th>
                        <th class="px-4 py-3 text-right font-medium">Kas Awal</th>
                        <th class="px-4 py-3 text-right font-medium">Penjualan Tunai</th>
                        <th class="px-4 py-3 text-right font-medium">Selisih</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $s)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $s->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->cashier?->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->opened_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->closed_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($s->opening_cash) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($s->cash_sales_total) }}</td>
                            <td class="px-4 py-3 text-right {{ (float) $s->cash_difference != 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }}">{{ $rp($s->cash_difference) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $s->isOpen() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $s->isOpen() ? 'Terbuka' : 'Tutup' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada sesi kasir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
