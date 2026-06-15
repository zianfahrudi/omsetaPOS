@props([
    'action',
    'backUrl',
    'partnerLabel' => 'Kontak',
    'partnerField' => 'contact_id',
    'partners' => [],
    'refLabel' => null,
    'refField' => null,
    'secondaryLabel' => null,
    'secondaryField' => null,
    'showWarehouse' => false,
    'priceLabel' => 'Harga',
    'priceField' => 'unit_price',
    'products' => [],
    'warehouses' => [],
    'submitLabel' => 'Simpan',
])

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

<form method="POST" action="{{ $action }}" x-data="docForm(@js($products), '{{ $priceField }}')">
    @csrf

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="{{ $lbl }}">{{ $partnerLabel }}</label>
                <select name="{{ $partnerField }}" class="{{ $input }}" required>
                    <option value="">— Pilih {{ strtolower($partnerLabel) }} —</option>
                    @foreach ($partners as $p)
                        <option value="{{ $p->id }}" @selected(old($partnerField) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            @if ($refField)
                <div>
                    <label class="{{ $lbl }}">{{ $refLabel }}</label>
                    <input type="text" name="{{ $refField }}" value="{{ old($refField) }}" class="{{ $input }}">
                </div>
            @endif

            <div>
                <label class="{{ $lbl }}">Tanggal</label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
            </div>

            @if ($secondaryField)
                <div>
                    <label class="{{ $lbl }}">{{ $secondaryLabel }}</label>
                    <input type="date" name="{{ $secondaryField }}" value="{{ old($secondaryField) }}" class="{{ $input }}">
                </div>
            @endif

            @if ($showWarehouse)
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Gudang</label>
                    <select name="warehouse_id" class="{{ $input }}">
                        <option value="">— Gudang default —</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}{{ $w->is_default ? ' (default)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="mt-6">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Item</h2>
                <button type="button" @click="addRow()" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">+ Tambah Baris</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium" style="min-width:220px">Produk</th>
                            <th class="py-2 text-right font-medium" style="width:90px">Qty</th>
                            <th class="py-2 text-right font-medium" style="width:140px">{{ $priceLabel }}</th>
                            <th class="py-2 text-right font-medium" style="width:120px">Pajak</th>
                            <th class="py-2 text-right font-medium" style="width:140px">Subtotal</th>
                            <th class="py-2" style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="i">
                            <tr class="border-b border-slate-100 align-top">
                                <td class="py-2 pr-2">
                                    <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="onProduct(i)" class="{{ $input }}">
                                        <option value="">— Item manual —</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                    <input type="text" :name="`items[${i}][product_name]`" x-model="row.product_name"
                                           x-show="!row.product_id" placeholder="Nama item" class="{{ $input }} mt-1">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" min="0" step="1" :name="`items[${i}][quantity]`" x-model.number="row.quantity" class="{{ $input }} text-right">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" min="0" step="0.01" :name="`items[${i}][${priceField}]`" x-model.number="row.price" class="{{ $input }} text-right">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" min="0" step="0.01" :name="`items[${i}][tax_amount]`" x-model.number="row.tax" class="{{ $input }} text-right">
                                </td>
                                <td class="py-2 pr-2 text-right text-slate-700" x-text="rp(lineTotal(row))"></td>
                                <td class="py-2 text-right">
                                    <button type="button" @click="removeRow(i)" x-show="rows.length > 1" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="w-full max-w-md">
                <label class="{{ $lbl }}">Catatan</label>
                <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
            </div>
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd x-text="rp(subtotal())"></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Pajak</dt><dd x-text="rp(taxTotal())"></dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-semibold"><dt>Total</dt><dd x-text="rp(grandTotal())"></dd></div>
            </dl>
        </div>
    </div>

    <div class="mt-4 flex items-center gap-3">
        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $submitLabel }}</button>
        <a href="{{ $backUrl }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
    </div>
</form>

<script>
    function docForm(products, priceField) {
        return {
            products,
            priceField,
            rows: [{ product_id: '', product_name: '', quantity: 1, price: 0, tax: 0 }],
            addRow() { this.rows.push({ product_id: '', product_name: '', quantity: 1, price: 0, tax: 0 }); },
            removeRow(i) { this.rows.splice(i, 1); if (this.rows.length === 0) this.addRow(); },
            onProduct(i) {
                const row = this.rows[i];
                const p = this.products.find(x => String(x.id) === String(row.product_id));
                if (p) { row.price = p.price; row.product_name = p.name; }
            },
            lineTotal(row) { return (Number(row.quantity) || 0) * (Number(row.price) || 0) + (Number(row.tax) || 0); },
            subtotal() { return this.rows.reduce((s, r) => s + (Number(r.quantity) || 0) * (Number(r.price) || 0), 0); },
            taxTotal() { return this.rows.reduce((s, r) => s + (Number(r.tax) || 0), 0); },
            grandTotal() { return this.subtotal() + this.taxTotal(); },
            rp(v) { return 'Rp ' + (Number(v) || 0).toLocaleString('id-ID'); },
        };
    }
</script>
