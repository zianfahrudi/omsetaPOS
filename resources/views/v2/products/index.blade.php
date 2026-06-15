@extends('v2.layouts.app')
@section('title', 'Produk')
@section('heading', 'Produk')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex w-full max-w-sm items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, SKU, barcode…"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.products.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Produk</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Toko</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 text-right font-medium">Harga Jual</th>
                        <th class="px-4 py-3 text-right font-medium">Stok</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $product->sku ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $product->store?->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $product->category?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($product->sell_price) }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="@if ($product->stock <= $product->minimum_stock) text-rose-600 font-semibold @endif">{{ number_format($product->stock, 0, ',', '.') }}</span>
                                <span class="text-slate-400">{{ $product->unit }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.products.edit', $product) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route('v2.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Tidak ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
