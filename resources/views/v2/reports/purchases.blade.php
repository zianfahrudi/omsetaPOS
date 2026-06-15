@extends('v2.layouts.app')
@section('title', 'Laporan Pembelian')
@section('heading', 'Laporan Pembelian')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.reports._period')

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Total Pembelian</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ $rp($report['total']) }}</p>
        </div>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-v2.analysis-table title="Per Produk" :rows="$report['by_product']" label-head="Produk" />
            <x-v2.analysis-table title="Per Pemasok" :rows="$report['by_supplier']" label-head="Pemasok" />
        </div>
    @endif
@endsection
