@extends('v2.layouts.app')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('heading', $product->exists ? 'Edit Produk' : 'Tambah Produk')

@php
    $val = fn ($key, $default = null) => old($key, data_get($product, $key, $default));
    $action = $product->exists ? route('v2.products.update', $product) : route('v2.products.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-3xl">
        @csrf
        @if ($product->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Nama Produk</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Toko</label>
                    <select name="store_id" class="{{ $input }}" required>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected($val('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">Kategori</label>
                    <select name="category_id" class="{{ $input }}">
                        <option value="">— Tanpa kategori —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($val('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">SKU</label>
                    <input type="text" name="sku" value="{{ $val('sku') }}" class="{{ $input }}">
                </div>

                <div>
                    <label class="{{ $label }}">Barcode</label>
                    <input type="text" name="barcode" value="{{ $val('barcode') }}" class="{{ $input }}">
                </div>

                <div>
                    <label class="{{ $label }}">Tipe</label>
                    <select name="product_type" class="{{ $input }}" required>
                        <option value="goods" @selected($val('product_type') === 'goods')>Barang</option>
                        <option value="service" @selected($val('product_type') === 'service')>Jasa</option>
                    </select>
                </div>

                <div>
                    <label class="{{ $label }}">Satuan</label>
                    <input type="text" name="unit" value="{{ $val('unit', 'pcs') }}" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Harga Beli</label>
                    <input type="number" step="0.01" min="0" name="cost_price" value="{{ $val('cost_price', 0) }}" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Harga Jual</label>
                    <input type="number" step="0.01" min="0" name="sell_price" value="{{ $val('sell_price', 0) }}" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Stok</label>
                    <input type="number" name="stock" value="{{ $val('stock', 0) }}" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Stok Minimum</label>
                    <input type="number" min="0" name="minimum_stock" value="{{ $val('minimum_stock', 0) }}" class="{{ $input }}" required>
                </div>

                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($val('is_active', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Aktif
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.products.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
