@extends('v2.layouts.app')
@section('title', 'Laporan Pajak')
@section('heading', 'Laporan Pajak (PPN)')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.reports._period')

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">PPN Keluaran</p>
                <p class="mt-1 text-lg font-bold text-emerald-600">{{ $rp($report['output']) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">PPN Masukan</p>
                <p class="mt-1 text-lg font-bold text-amber-600">{{ $rp($report['input']) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">PPN Kurang/(Lebih) Bayar</p>
                <p class="mt-1 text-lg font-bold {{ $report['net'] >= 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $rp($report['net']) }}</p>
            </div>
        </div>
        <p class="mb-4 text-sm text-slate-500">{{ $report['status'] }}</p>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Nomor</th>
                            <th class="px-4 py-3 font-medium">Keterangan</th>
                            <th class="px-4 py-3 text-right font-medium">Keluaran</th>
                            <th class="px-4 py-3 text-right font-medium">Masukan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row['number'] }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $row['description'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['output'] ? $rp($row['output']) : '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['input'] ? $rp($row['input']) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada transaksi pajak.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
