@extends('v2.layouts.app')
@section('title', 'Transaksi '.$sale->number)
@section('heading', 'Detail Transaksi POS')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.pos.transactions') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Riwayat</a>

    @if ($sale->status !== 'void' && in_array(auth()->user()->role, ['admin', 'superuser'], true))
        <form method="POST" action="{{ route('v2.pos.transactions.void', $sale) }}" class="mb-4" onsubmit="return confirm('Batalkan (void) transaksi ini? Stok dikembalikan & jurnal dibalik.')">
            @csrf
            <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Batalkan Transaksi (Void)</button>
        </form>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $sale->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $sale->created_at->format('d F Y H:i') }}</p>
            </div>
            <x-v2.status :value="$sale->status" />
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-400">Toko</dt><dd class="text-slate-700">{{ $sale->store?->name }}</dd></div>
            <div><dt class="text-slate-400">Kasir</dt><dd class="text-slate-700">{{ $sale->cashier?->name }}</dd></div>
            <div><dt class="text-slate-400">Pelanggan</dt><dd class="text-slate-700">{{ $sale->customer?->name ?: ($sale->customer_name ?: 'Umum') }}</dd></div>
            <div><dt class="text-slate-400">Metode</dt><dd class="text-slate-700">{{ strtoupper($sale->payment_method) }}</dd></div>
            @if ($sale->vehicle_plate_number)
                <div><dt class="text-slate-400">Plat</dt><dd class="text-slate-700">{{ $sale->vehicle_plate_number }}</dd></div>
            @endif
        </dl>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Produk</th>
                        <th class="py-2 text-right font-medium">Qty</th>
                        <th class="py-2 text-right font-medium">Harga</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $item->product_name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ (int) $item->quantity }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->unit_price) }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ $rp($sale->subtotal) }}</dd></div>
                @if ((float) $sale->discount_total > 0)
                    <div class="flex justify-between"><dt class="text-slate-500">Diskon</dt><dd>-{{ $rp($sale->discount_total) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt class="text-slate-500">Service Fee</dt><dd>{{ $rp($sale->service_fee_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pajak</dt><dd>{{ $rp($sale->tax_total) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Total</dt><dd>{{ $rp($sale->grand_total) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Dibayar</dt><dd>{{ $rp($sale->paid_amount) }}</dd></div>
                <div class="flex justify-between text-emerald-600"><dt>Kembalian</dt><dd>{{ $rp($sale->change_amount) }}</dd></div>
                @if ((float) $sale->debt_amount > 0)
                    <div class="flex justify-between font-semibold text-rose-600"><dt>Hutang</dt><dd>{{ $rp($sale->debt_amount) }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
@endsection
