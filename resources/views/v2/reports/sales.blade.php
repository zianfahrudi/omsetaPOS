@extends('v2.layouts.app')
@section('title', 'Laporan Penjualan')
@section('heading', 'Laporan Penjualan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.reports._period')

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Total Penjualan</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $rp($report['total']) }}</p>
        </div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-v2.analysis-table title="Per Kategori" :rows="$report['by_category']" label-head="Kategori" />
            <x-v2.analysis-table title="Per Produk" :rows="$report['by_product']" label-head="Produk" />
            <x-v2.analysis-table title="Per Pelanggan" :rows="$report['by_customer']" label-head="Pelanggan" />
        </div>
    @endif
@endsection
