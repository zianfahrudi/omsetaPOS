@extends('v2.layouts.app')
@section('title', 'Laporan Persediaan')
@section('heading', 'Laporan Persediaan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="low" value="1" @checked($lowOnly) onchange="this.form.submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Hanya stok rendah
        </label>
    </form>

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Total Item</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($report['total_items'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">Nilai Persediaan</p>
                <p class="mt-1 text-lg font-bold text-indigo-600">{{ $rp($report['total_value']) }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Produk</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 text-right font-medium">Stok</th>
                            <th class="px-4 py-3 text-right font-medium">HPP/Unit</th>
                            <th class="px-4 py-3 text-right font-medium">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $row['name'] }}
                                    @if ($row['low'])<span class="ml-1 rounded bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-600">rendah</span>@endif
                                    @if ($row['sku'])<p class="text-xs text-slate-400">{{ $row['sku'] }}</p>@endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $row['category'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row['stock'], 0, ',', '.') }} {{ $row['unit'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-500">{{ $rp($row['cost_price']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $rp($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
