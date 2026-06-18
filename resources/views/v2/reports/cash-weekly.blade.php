@extends('v2.layouts.app')
@section('title', 'Rekap Kas Mingguan')
@section('heading', 'Rekap Kas Mingguan')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
    <h1 class="mb-4 hidden text-xl font-bold text-slate-900 print:block">Rekap Kas {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</h1>

    <form method="GET" class="no-print mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
        <x-v2.print-button />
    </form>

    @if (empty($weeks))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Belum ada transaksi kas (masuk/keluar) pada periode ini.</div>
    @else
        @foreach ($weeks as $week)
            @php $net = round($week['in'] - $week['out'], 2); @endphp
            <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Minggu {{ $week['no'] }}</h2>
                    <span class="text-xs text-slate-500">{{ count($week['rows']) }} transaksi</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
                                <th class="px-5 py-2 font-medium">Tanggal</th>
                                <th class="px-5 py-2 font-medium">Keterangan</th>
                                <th class="px-5 py-2 text-right font-medium">Uang Keluar</th>
                                <th class="px-5 py-2 text-right font-medium">Uang Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($week['rows'] as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-2 text-slate-500">{{ $row['date']?->format('d/m/Y') }}</td>
                                    <td class="px-5 py-2 text-slate-700">{{ $row['description'] }}</td>
                                    <td class="px-5 py-2 text-right text-rose-600">{{ $row['out'] > 0 ? $rp($row['out']) : '—' }}</td>
                                    <td class="px-5 py-2 text-right text-emerald-600">{{ $row['in'] > 0 ? $rp($row['in']) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200 font-semibold text-slate-900">
                                <td class="px-5 py-2" colspan="2">Total Minggu {{ $week['no'] }}</td>
                                <td class="px-5 py-2 text-right text-rose-700">{{ $rp($week['out']) }}</td>
                                <td class="px-5 py-2 text-right text-emerald-700">{{ $rp($week['in']) }}</td>
                            </tr>
                            <tr class="bg-slate-50">
                                <td class="px-5 py-2 text-right font-medium text-slate-600" colspan="3">Jumlah (Masuk − Keluar)</td>
                                <td class="px-5 py-2 text-right font-bold {{ $net >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $rp($net) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach

        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div><p class="text-xs text-indigo-500">Total Uang Keluar</p><p class="mt-0.5 text-lg font-bold text-rose-700">{{ $rp($grandOut) }}</p></div>
                <div><p class="text-xs text-indigo-500">Total Uang Masuk</p><p class="mt-0.5 text-lg font-bold text-emerald-700">{{ $rp($grandIn) }}</p></div>
                <div><p class="text-xs text-indigo-500">Jumlah Bersih</p><p class="mt-0.5 text-lg font-bold {{ $grandNet >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $rp($grandNet) }}</p></div>
            </div>
        </div>
    @endif
@endsection
