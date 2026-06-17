@extends('v2.layouts.app')
@section('title', 'Perakitan Baru')
@section('heading', 'Perakitan Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.inventory.assemblies.store') }}" x-data="assemblyForm(@js($products), @js($materials))">
        @csrf
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Produk Jadi</label>
                    <div class="mb-2 flex gap-4 text-sm">
                        <label class="inline-flex items-center gap-1.5"><input type="radio" x-model="finishedMode" value="product" class="text-indigo-600"> Dari Produk</label>
                        <label class="inline-flex items-center gap-1.5"><input type="radio" x-model="finishedMode" value="manual" class="text-indigo-600"> Isi Manual</label>
                    </div>
                    <select name="product_id" x-show="finishedMode === 'product'" class="{{ $input }}">
                        <option value="">— Pilih produk jadi —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="product_name" x-show="finishedMode === 'manual'" x-cloak value="{{ old('product_name') }}" class="{{ $input }}" placeholder="Nama produk jadi (mis: Kusen Aluminium 2 Pintu)">
                    <p x-show="finishedMode === 'manual'" x-cloak class="mt-1 text-xs text-slate-400">Produk baru otomatis dibuat di Master Produk, stok bertambah, harga modal = biaya material.</p>
                </div>
                <div>
                    <label class="{{ $lbl }}">Jumlah Dirakit</label>
                    <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" class="{{ $input }}">
                </div>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Komponen (dari Master Material)</h2>
                    <button type="button" @click="addRow()" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">+ Tambah Komponen</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
                                <th class="py-2 font-medium" style="min-width:240px">Material</th>
                                <th class="py-2 text-right font-medium" style="width:140px">Harga Satuan</th>
                                <th class="py-2 text-right font-medium" style="width:120px">Qty</th>
                                <th class="py-2 text-right font-medium" style="width:150px">Subtotal</th>
                                <th class="py-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-2">
                                        <select :name="`components[${i}][material_id]`" x-model="row.material_id" class="{{ $input }}">
                                            <option value="">— Pilih material —</option>
                                            <template x-for="m in materials" :key="m.id">
                                                <option :value="m.id" x-text="(m.unit ? (m.name + ' (' + m.unit + ')') : m.name) + ' — stok ' + m.stock"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2 text-right text-slate-500" x-text="rp(priceOf(row))"></td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="0" step="1" :name="`components[${i}][quantity]`" x-model.number="row.quantity" class="{{ $input }} text-right">
                                    </td>
                                    <td class="py-2 pr-2 text-right text-slate-700" x-text="rp(lineCost(row))"></td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removeRow(i)" x-show="rows.length > 1" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td colspan="3" class="py-3 text-right text-slate-700">Estimasi Biaya Produksi</td>
                                <td class="py-3 text-right" x-text="rp(totalCost())"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if ($materials->isEmpty())
                    <p class="mt-2 text-xs text-amber-600">Belum ada data material. Tambahkan dulu di <a href="{{ route('v2.materials.index') }}" class="underline">Master Material</a>.</p>
                @endif
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.inventory.assemblies') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>

    <script>
        function assemblyForm(products, materials) {
            return {
                products,
                materials,
                finishedMode: 'product',
                rows: [{ material_id: '', quantity: 1 }],
                find(row) { return this.materials.find(m => String(m.id) === String(row.material_id)); },
                priceOf(row) { const m = this.find(row); return m ? m.cost : 0; },
                lineCost(row) { const m = this.find(row); return m ? (Number(row.quantity) || 0) * m.cost : 0; },
                totalCost() { return this.rows.reduce((s, r) => s + this.lineCost(r), 0); },
                addRow() { this.rows.push({ material_id: '', quantity: 1 }); },
                removeRow(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.addRow(); },
                rp(v) { return 'Rp ' + (Number(v) || 0).toLocaleString('id-ID'); },
            };
        }
    </script>
@endsection
