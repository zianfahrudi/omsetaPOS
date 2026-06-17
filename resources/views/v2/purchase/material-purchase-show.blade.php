@extends('v2.layouts.app')
@section('title', 'Belanja Bahan '.$purchase->number)
@section('heading', 'Detail Belanja Bahan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($qtyFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','))

@section('content')
    <a href="{{ route('v2.purchase.materials') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Belanja Bahan</a>

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="border-b border-slate-200 pb-4">
            <p class="text-lg font-bold text-slate-900">{{ $purchase->number }}</p>
            <p class="mt-0.5 text-sm text-slate-500">{{ $purchase->date?->format('d F Y') }}</p>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-400">Supplier</dt><dd class="text-slate-700">{{ $purchase->supplier?->name ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Metode</dt><dd class="text-slate-700">{{ $methodLabels[$purchase->payment_method] ?? $purchase->payment_method }}</dd></div>
            <div><dt class="text-slate-400">Total</dt><dd class="font-semibold text-slate-800">{{ $rp($purchase->total) }}</dd></div>
        </dl>

        <h3 class="mb-2 mt-6 text-sm font-semibold text-slate-900">Item</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Material</th>
                        <th class="py-2 text-right font-medium">Qty</th>
                        <th class="py-2 text-right font-medium">Harga Satuan</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $it)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $it->material?->name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ $qtyFmt($it->quantity) }}{{ $it->material?->unit ? ' '.$it->material->unit : '' }}</td>
                            <td class="py-2.5 text-right">{{ $rp($it->unit_cost) }}</td>
                            <td class="py-2.5 text-right">{{ $rp($it->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-slate-300 font-bold">
                        <td colspan="3" class="py-2.5 text-right">TOTAL</td>
                        <td class="py-2.5 text-right">{{ $rp($purchase->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @if ($purchase->notes)<p class="mt-4 text-sm text-slate-500">Catatan: {{ $purchase->notes }}</p>@endif
    </div>
@endsection
