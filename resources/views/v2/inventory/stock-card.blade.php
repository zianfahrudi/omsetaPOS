@extends('v2.layouts.app')
@section('title', 'Kartu Stok')
@section('heading', 'Kartu Stok')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Produk</label>
            <select name="product_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" style="min-width:280px">
                @foreach ($products as $p)
                    <option value="{{ $p->id }}" @selected($productId === $p->id)>{{ $p->name }}{{ $p->sku ? ' ('.$p->sku.')' : '' }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if (! $product)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Belum ada produk.</div>
    @else
        <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium text-slate-500">Stok Saat Ini</p>
            <p class="mt-1 text-lg font-bold text-slate-800">{{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit }}</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Jenis</th>
                            <th class="px-4 py-3 font-medium">Keterangan</th>
                            <th class="px-4 py-3 text-right font-medium">Masuk</th>
                            <th class="px-4 py-3 text-right font-medium">Keluar</th>
                            <th class="px-4 py-3 text-right font-medium">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2.5 text-slate-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $movementLabels[$m->type] ?? $m->type }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $m->notes ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-right text-emerald-600">{{ $m->quantity > 0 ? number_format($m->quantity, 0, ',', '.') : '—' }}</td>
                                <td class="px-4 py-2.5 text-right text-rose-600">{{ $m->quantity < 0 ? number_format(abs($m->quantity), 0, ',', '.') : '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-medium">{{ number_format($m->stock_after, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
