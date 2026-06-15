@extends('v2.layouts.app')
@section('title', 'Laporan Arus Kas')
@section('heading', 'Laporan Arus Kas')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.reports._period')

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['Saldo Awal', $report['opening'], 'text-slate-800'],
                ['Kas Masuk', $report['total_in'], 'text-emerald-600'],
                ['Kas Keluar', $report['total_out'], 'text-rose-600'],
                ['Saldo Akhir', $report['closing'], 'text-indigo-600'],
            ] as [$label, $val, $color])
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-bold {{ $color }}">{{ $rp($val) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Kelompok</th>
                            <th class="px-4 py-3 text-right font-medium">Masuk</th>
                            <th class="px-4 py-3 text-right font-medium">Keluar</th>
                            <th class="px-4 py-3 text-right font-medium">Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['groups'] as $g)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-3 text-slate-700">{{ $g['label'] }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600">{{ $rp($g['in']) }}</td>
                                <td class="px-4 py-3 text-right text-rose-600">{{ $rp($g['out']) }}</td>
                                <td class="px-4 py-3 text-right font-medium {{ $g['net'] >= 0 ? 'text-slate-800' : 'text-rose-600' }}">{{ $rp($g['net']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Tidak ada pergerakan kas.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold text-slate-900">
                            <td class="px-4 py-3">Arus Kas Bersih</td>
                            <td class="px-4 py-3 text-right">{{ $rp($report['total_in']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($report['total_out']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($report['net']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@endsection
