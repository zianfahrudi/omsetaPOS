@extends('v2.layouts.app')
@section('title', 'Kirim Konsinyasi')
@section('heading', 'Kirim Konsinyasi')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.inventory.consignments.store') }}" x-data="consignForm(@js($products))">
        @csrf
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Penerima Titipan (Consignee)</label>
                    <select name="contact_id" class="{{ $input }}" required>
                        <option value="">— Pilih penerima —</option>
                        @foreach ($consignees as $c)
                            <option value="{{ $c->id }}" @selected(old('contact_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Barang Dititipkan</h2>
                    <button type="button" @click="addRow()" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">+ Tambah Baris</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
                                <th class="py-2 font-medium" style="min-width:240px">Produk</th>
                                <th class="py-2 text-right font-medium" style="width:100px">Stok</th>
                                <th class="py-2 text-right font-medium" style="width:110px">Qty</th>
                                <th class="py-2 text-right font-medium" style="width:150px">Harga Jual</th>
                                <th class="py-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-2">
                                        <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="onProduct(i)" class="{{ $input }}">
                                            <option value="">— Pilih produk —</option>
                                            <template x-for="p in products" :key="p.id">
                                                <option :value="p.id" x-text="p.name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2 text-right text-slate-500" x-text="stockOf(row)"></td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="0" step="1" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="{{ $input }} text-right">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="0" step="0.01" :name="`items[${i}][unit_price]`" x-model.number="row.price" class="{{ $input }} text-right">
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 max-w-md">
                <label class="{{ $lbl }}">Catatan</label>
                <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Kirim Konsinyasi</button>
            <a href="{{ route('v2.inventory.consignments') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>

    <script>
        function consignForm(products) {
            return {
                products,
                rows: [{ product_id: '', quantity: 1, price: 0 }],
                find(row) { return this.products.find(p => String(p.id) === String(row.product_id)); },
                stockOf(row) { const p = this.find(row); return p ? p.stock : '—'; },
                onProduct(i) { const p = this.find(this.rows[i]); if (p) this.rows[i].price = p.price; },
                addRow() { this.rows.push({ product_id: '', quantity: 1, price: 0 }); },
                removeRow(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.addRow(); },
            };
        }
    </script>
@endsection
