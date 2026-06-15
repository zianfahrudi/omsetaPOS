@extends('v2.layouts.app')
@section('title', 'Faktur '.$invoice->number)
@section('heading', 'Detail Faktur Penjualan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.sales.invoices') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Faktur Penjualan</a>

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $invoice->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $invoice->date?->format('d F Y') }}</p>
            </div>
            <x-v2.status :value="$invoice->paymentStatus()" />
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-400">Pelanggan</dt><dd class="text-slate-700">{{ $invoice->customer?->name ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Jatuh Tempo</dt><dd class="text-slate-700">{{ $invoice->due_date?->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Ref. Pelanggan</dt><dd class="text-slate-700">{{ $invoice->customer_ref ?: '—' }}</dd></div>
        </dl>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Produk</th>
                        <th class="py-2 text-right font-medium">Qty</th>
                        <th class="py-2 text-right font-medium">Harga</th>
                        <th class="py-2 text-right font-medium">Pajak</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $item->product_name ?: $item->product?->name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->unit_price) }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->tax_amount) }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ $rp($invoice->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pajak</dt><dd>{{ $rp($invoice->tax_total) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold"><dt>Total</dt><dd>{{ $rp($invoice->grand_total) }}</dd></div>
                <div class="flex justify-between text-emerald-600"><dt>Dibayar</dt><dd>{{ $rp($invoice->paid_amount) }}</dd></div>
                <div class="flex justify-between font-semibold {{ (float) $invoice->outstanding_amount > 0 ? 'text-rose-600' : 'text-slate-900' }}"><dt>Sisa</dt><dd>{{ $rp($invoice->outstanding_amount) }}</dd></div>
            </dl>
        </div>

        @if ($invoice->payments->isNotEmpty())
            <div class="mt-6 border-t border-slate-200 pt-4">
                <h3 class="mb-2 text-sm font-semibold text-slate-900">Riwayat Pembayaran</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach ($invoice->payments as $p)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-600">{{ $p->number }}</td>
                                <td class="py-2 text-slate-500">{{ $p->date?->format('d/m/Y') }}</td>
                                <td class="py-2 text-slate-500">{{ $p->method ?: '—' }}</td>
                                <td class="py-2 text-right">{{ $rp($p->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
