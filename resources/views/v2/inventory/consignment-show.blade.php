@extends('v2.layouts.app')
@section('title', 'Konsinyasi '.$consignment->number)
@section('heading', 'Detail Konsinyasi')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.inventory.consignments') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Konsinyasi</a>

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $consignment->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $consignment->consignee?->name }} · {{ $consignment->date?->format('d F Y') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $consignment->isOpen() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                {{ $consignment->isOpen() ? 'Berjalan' : 'Selesai' }}
            </span>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Produk</th>
                        <th class="py-2 text-right font-medium">Dikirim</th>
                        <th class="py-2 text-right font-medium">Terjual</th>
                        <th class="py-2 text-right font-medium">Diretur</th>
                        <th class="py-2 text-right font-medium">Sisa</th>
                        <th class="py-2 text-right font-medium">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consignment->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $item->product_name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ $item->quantity }}</td>
                            <td class="py-2.5 text-right text-emerald-600">{{ $item->sold_quantity }}</td>
                            <td class="py-2.5 text-right text-slate-500">{{ $item->returned_quantity }}</td>
                            <td class="py-2.5 text-right font-medium">{{ $item->remaining() }}</td>
                            <td class="py-2.5 text-right">{{ $rp($item->unit_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($consignment->isOpen())
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Catat penjualan --}}
            <form method="POST" action="{{ route('v2.inventory.consignments.settle', $consignment) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                @csrf
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Catat Penjualan (Settle)</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Produk</th>
                            <th class="py-2 text-right font-medium">Sisa</th>
                            <th class="py-2 text-right font-medium" style="width:120px">Qty Terjual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consignment->items as $idx => $item)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-700">{{ $item->product_name }}<input type="hidden" name="lines[{{ $idx }}][item_id]" value="{{ $item->id }}"></td>
                                <td class="py-2 text-right text-slate-500">{{ $item->remaining() }}</td>
                                <td class="py-2 text-right">
                                    <input type="number" min="0" max="{{ $item->remaining() }}" value="0" name="lines[{{ $idx }}][sold_quantity]"
                                           class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" @disabled($item->remaining() <= 0)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Dana Masuk ke</label>
                        <select name="cash_account_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                            @foreach ($cashAccounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                    </div>
                </div>
                <button class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Catat Penjualan</button>
            </form>

            {{-- Retur titipan --}}
            <form method="POST" action="{{ route('v2.inventory.consignments.return', $consignment) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                @csrf
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Retur Barang Titipan</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Produk</th>
                            <th class="py-2 text-right font-medium">Sisa</th>
                            <th class="py-2 text-right font-medium" style="width:120px">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consignment->items as $idx => $item)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 text-slate-700">{{ $item->product_name }}<input type="hidden" name="lines[{{ $idx }}][item_id]" value="{{ $item->id }}"></td>
                                <td class="py-2 text-right text-slate-500">{{ $item->remaining() }}</td>
                                <td class="py-2 text-right">
                                    <input type="number" min="0" max="{{ $item->remaining() }}" value="0" name="lines[{{ $idx }}][quantity]"
                                           class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" @disabled($item->remaining() <= 0)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4 max-w-[200px]">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                    <input type="date" name="date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                </div>
                <button class="mt-4 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Proses Retur</button>
            </form>
        </div>
    @endif
@endsection
