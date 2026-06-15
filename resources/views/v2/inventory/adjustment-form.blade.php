@extends('v2.layouts.app')
@section('title', 'Penyesuaian Stok Baru')
@section('heading', 'Penyesuaian Stok Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.inventory.adjustments.store') }}" class="max-w-2xl"
          x-data="{ products: @js($products), pid: '{{ old('product_id') }}', qtyAfter: {{ old('quantity_after', 0) }},
                    get current() { const p = this.products.find(x => String(x.id) === String(this.pid)); return p ? p.stock : 0; },
                    get diff() { return (Number(this.qtyAfter) || 0) - this.current; } }">
        @csrf
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Produk</label>
                    <select name="product_id" x-model="pid" class="{{ $input }}" required>
                        <option value="">— Pilih produk —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Stok Sistem</label>
                    <input type="text" class="{{ $input }} bg-slate-50" x-bind:value="current" readonly>
                </div>
                <div>
                    <label class="{{ $lbl }}">Stok Aktual (hasil hitung)</label>
                    <input type="number" min="0" name="quantity_after" x-model.number="qtyAfter" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm">Selisih:
                        <span class="font-semibold" :class="diff < 0 ? 'text-rose-600' : (diff > 0 ? 'text-emerald-600' : 'text-slate-600')"
                              x-text="(diff > 0 ? '+' : '') + diff"></span>
                    </p>
                </div>
                <div>
                    <label class="{{ $lbl }}">Alasan</label>
                    <select name="reason" class="{{ $input }}" required>
                        @foreach ($reasonLabels as $key => $text)
                            <option value="{{ $key }}" @selected(old('reason') === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan & Posting</button>
            <a href="{{ route('v2.inventory.adjustments') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
