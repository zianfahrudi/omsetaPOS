@props([
    'action',
    'backUrl',
    'number',
    'items' => [],
])

@php
    $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

<form method="POST" action="{{ $action }}" class="max-w-3xl">
    @csrf
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <p class="mb-4 text-sm text-slate-500">Faktur <span class="font-medium text-slate-800">{{ $number }}</span>. Isi kuantitas barang yang diretur (0 = tidak diretur).</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Produk</th>
                        <th class="py-2 text-right font-medium">Qty Faktur</th>
                        <th class="py-2 text-right font-medium" style="width:140px">Qty Retur</th>
                    </tr>
                </thead>
                <tbody>
                    @php($i = 0)
                    @forelse ($items as $item)
                        @continue(! $item->product_id)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 text-slate-700">
                                {{ $item->product_name ?: $item->product?->name }}
                                <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                            </td>
                            <td class="py-2 text-right text-slate-500">{{ (int) $item->quantity }}</td>
                            <td class="py-2 text-right">
                                <input type="number" min="0" max="{{ (int) $item->quantity }}" value="0"
                                       name="items[{{ $i }}][quantity]" class="{{ $input }} w-28 text-right">
                            </td>
                        </tr>
                        @php($i++)
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-400">Faktur tidak punya item barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5 max-w-md">
            <label class="mb-1 block text-sm font-medium text-slate-700">Alasan Retur</label>
            <input type="text" name="reason" value="{{ old('reason') }}" class="{{ $input }} w-full" placeholder="Barang rusak / tidak sesuai">
        </div>
    </div>
    <div class="mt-4 flex items-center gap-3">
        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Proses Retur</button>
        <a href="{{ $backUrl }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
    </div>
</form>
