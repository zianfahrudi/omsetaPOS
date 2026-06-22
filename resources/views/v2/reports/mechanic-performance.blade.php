@extends('v2.layouts.app')
@section('title', 'Performa Mekanik')
@section('heading', 'Performa Mekanik')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.reports._period')

    @if (! $company)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Petugas</th>
                            <th class="px-4 py-3 font-medium">Nomor Transaksi</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah Jasa</th>
                            <th class="px-4 py-3 text-right font-medium">Total Nilai Jasa</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2.5 text-slate-700">{{ $row->employee_name }}@if ($row->employee_code)<span class="text-xs text-slate-400"> · {{ $row->employee_code }}</span>@endif</td>
                                <td class="px-4 py-2.5 text-slate-600">
                                    @php($numbers = array_filter(explode(',', (string) $row->sale_numbers)))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($numbers as $no)
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">{{ $no }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($row->service_count, 0, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $rp($row->service_total) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($row->sale_count, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada data pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot>
                            <tr class="border-t border-slate-200 bg-slate-50 font-semibold text-slate-700">
                                <td class="px-4 py-3" colspan="2">Total</td>
                                <td class="px-4 py-3 text-right">{{ number_format($rows->sum('service_count'), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($rows->sum('service_total')) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($rows->sum('sale_count'), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
@endsection
