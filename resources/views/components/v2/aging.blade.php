@props([
    'report' => null,
    'asOf' => null,
    'action',
    'partnerLabel' => 'Kontak',
])

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $bucketLabels = [
        'current' => 'Belum jatuh tempo',
        '1_30' => '1–30 hari',
        '31_60' => '31–60 hari',
        '61_90' => '61–90 hari',
        'over_90' => '> 90 hari',
    ];
@endphp

<form method="GET" action="{{ $action }}" class="mb-4 flex items-end gap-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Per Tanggal</label>
        <input type="date" name="as_of" value="{{ $asOf }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    </div>
    <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
</form>

@if (! $report)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
@else
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach ($bucketLabels as $key => $text)
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <p class="text-[11px] font-medium text-slate-500">{{ $text }}</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $rp($report['buckets'][$key] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">{{ $partnerLabel }}</th>
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-right font-medium">Umur (hari)</th>
                        <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $row['party'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $row['number'] }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ \Illuminate\Support\Carbon::parse($row['due'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right {{ $row['overdue_days'] > 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }}">{{ $row['overdue_days'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Tidak ada tagihan outstanding.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-semibold text-slate-900">
                        <td colspan="4" class="px-4 py-3 text-right">Total</td>
                        <td class="px-4 py-3 text-right">{{ $rp($report['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif
