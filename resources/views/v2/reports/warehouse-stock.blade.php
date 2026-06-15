@extends('v2.layouts.app')
@section('title', 'Stok per Gudang')
@section('heading', 'Stok per Gudang')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Gudang</label>
            <select name="warehouse_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" style="min-width:220px">
                <option value="">Semua gudang</option>
                @foreach ($warehouses as $w)
                    <option value="{{ $w->id }}" @selected($warehouseId === $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
    </form>

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Total Nilai Stok</p>
            <p class="mt-1 text-lg font-bold text-indigo-600">{{ $rp($report['total_value']) }}</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Gudang</th>
                            <th class="px-4 py-3 font-medium">Produk</th>
                            <th class="px-4 py-3 text-right font-medium">Qty</th>
                            <th class="px-4 py-3 text-right font-medium">HPP/Unit</th>
                            <th class="px-4 py-3 text-right font-medium">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2.5 text-slate-600">{{ $row['warehouse'] }}</td>
                                <td class="px-4 py-2.5 text-slate-700">{{ $row['product'] }}@if ($row['sku'])<span class="text-xs text-slate-400"> · {{ $row['sku'] }}</span>@endif</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($row['quantity'], 0, ',', '.') }} {{ $row['unit'] }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-500">{{ $rp($row['cost_price']) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $rp($row['value']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
