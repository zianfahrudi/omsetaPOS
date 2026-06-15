@extends('v2.layouts.app')
@section('title', 'Pesanan Pembelian')
@section('heading', 'Pesanan Pembelian')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @include('v2.purchase._search')

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Pemasok</th>
                        <th class="px-4 py-3 font-medium">Estimasi Terima</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $r->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $r->supplier?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->expected_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($r->grand_total) }}</td>
                            <td class="px-4 py-3 text-center"><x-v2.status :value="$r->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
