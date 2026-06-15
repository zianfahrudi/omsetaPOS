@props(['title', 'rows' => [], 'labelHead' => 'Nama'])

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-2.5">
        <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="px-4 py-2.5 font-medium">{{ $labelHead }}</th>
                    <th class="px-4 py-2.5 text-right font-medium">Qty</th>
                    <th class="px-4 py-2.5 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-2.5 text-slate-700">{{ $row['label'] }}</td>
                        <td class="px-4 py-2.5 text-right text-slate-500">{{ number_format($row['quantity'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $rp($row['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
