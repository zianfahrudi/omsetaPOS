@extends('v2.layouts.app')
@section('title', 'Kartu Stok Bahan')
@section('heading', 'Kartu Stok Bahan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($qtyFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','))

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
            <x-v2.month-picker name="month" :value="$month" />
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tampilkan</button>
        </form>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm">
            <p class="text-indigo-500">Nilai Stok Bahan (akhir)</p>
            <p class="mt-0.5 text-xl font-bold text-indigo-700">{{ $rp($totalValue) }}</p>
        </div>
    </div>

    <p class="mb-2 text-sm text-slate-500">Periode <strong class="text-slate-700">{{ $periodLabel }}</strong></p>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama Bahan</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 text-right font-medium">Harga</th>
                        <th class="px-4 py-3 text-right font-medium">Stok Awal</th>
                        <th class="px-4 py-3 text-right font-medium">Masuk</th>
                        <th class="px-4 py-3 text-right font-medium">Keluar</th>
                        <th class="px-4 py-3 text-right font-medium">Stok Akhir</th>
                        <th class="px-4 py-3 text-right font-medium">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-2.5 text-slate-700">{{ $r['name'] }}{{ $r['unit'] ? ' ('.$r['unit'].')' : '' }}</td>
                            <td class="px-4 py-2.5 text-slate-500">{{ $r['category'] ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-slate-500">{{ $rp($r['price']) }}</td>
                            <td class="px-4 py-2.5 text-right text-slate-500">{{ $qtyFmt($r['awal']) }}</td>
                            <td class="px-4 py-2.5 text-right text-emerald-600">{{ $r['masuk'] > 0 ? $qtyFmt($r['masuk']) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right text-rose-600">{{ $r['keluar'] > 0 ? $qtyFmt($r['keluar']) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-slate-800">{{ $qtyFmt($r['akhir']) }}</td>
                            <td class="px-4 py-2.5 text-right text-slate-700">{{ $rp($r['value']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada material.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-800">
                            <td class="px-4 py-3" colspan="7">TOTAL NILAI STOK</td>
                            <td class="px-4 py-3 text-right">{{ $rp($totalValue) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
