@extends('v2.layouts.app')
@section('title', 'Riwayat Transaksi POS')
@section('heading', 'Riwayat Transaksi POS')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor / pelanggan / plat…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
        <a href="/kasir" class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buka Kasir</a>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Waktu</th>
                        <th class="px-4 py-3 font-medium">Kasir</th>
                        <th class="px-4 py-3 font-medium">Pelanggan</th>
                        <th class="px-4 py-3 font-medium">Bayar</th>
                        <th class="px-4 py-3 text-right font-medium">Total</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $sale)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('v2.pos.transactions.show', $sale) }}" class="font-medium text-indigo-600 hover:underline">{{ $sale->number }}</a>
                                <a href="{{ route('v2.pos.transactions.receipt', $sale) }}" target="_blank" class="ml-2 text-xs text-slate-400 hover:text-indigo-600">struk</a>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $sale->cashier?->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sale->customer?->name ?: ($sale->customer_name ?: 'Umum') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ strtoupper($sale->payment_method) }}{{ $sale->is_debt ? ' · Hutang' : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($sale->grand_total) }}</td>
                            <td class="px-4 py-3 text-center"><x-v2.status :value="$sale->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
