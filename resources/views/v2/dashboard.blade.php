@extends('v2.layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['Penjualan Bulan Ini', $metrics['sales'], 'text-emerald-600'],
            ['Pembelian Bulan Ini', $metrics['purchases'], 'text-amber-600'],
            ['Saldo Kas & Bank', $metrics['cash_bank'], 'text-indigo-600'],
            ['Piutang Usaha', $metrics['receivable'], 'text-sky-600'],
            ['Hutang Usaha', $metrics['payable'], 'text-rose-600'],
        ] as [$label, $val, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-xl font-bold {{ $color }}">{{ $rp($val) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Transaksi Terakhir</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Nomor</th>
                            <th class="py-2 font-medium">Tanggal</th>
                            <th class="py-2 font-medium">Toko</th>
                            <th class="py-2 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSales as $sale)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 font-medium text-slate-700">{{ $sale->number }}</td>
                                <td class="py-2 text-slate-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-2 text-slate-500">{{ $sale->store?->name }}</td>
                                <td class="py-2 text-right">{{ $rp($sale->grand_total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-slate-400">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold text-slate-900">Posisi Keuangan</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Total Aset</dt><dd class="font-semibold">{{ $rp($balanceSheet['total_assets']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Total Liabilitas</dt><dd class="font-semibold">{{ $rp($balanceSheet['total_liabilities']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Total Ekuitas</dt><dd class="font-semibold">{{ $rp($balanceSheet['total_equity']) }}</dd></div>
                <div class="mt-2 border-t border-slate-200 pt-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ ($balanceSheet['balanced'] ?? true) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ ($balanceSheet['balanced'] ?? true) ? 'Neraca seimbang' : 'Neraca tidak seimbang' }}
                    </span>
                </div>
            </dl>
            <a href="{{ route('v2.reports.balance-sheet') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:underline">Lihat Neraca lengkap →</a>
        </div>
    </div>
@endsection
