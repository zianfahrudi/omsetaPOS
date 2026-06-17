@extends('v2.layouts.app')
@section('title', 'Perakitan '.$assembly->number)
@section('heading', 'Detail Perakitan')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($statusBadge = ['in_progress' => 'bg-amber-50 text-amber-700', 'completed' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-rose-50 text-rose-600'])
@php($statusLabel = ['in_progress' => 'Sedang Diproses', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'])

@section('content')
    <a href="{{ route('v2.inventory.assemblies') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Perakitan</a>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $assembly->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $assembly->date?->format('d F Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $statusBadge[$assembly->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$assembly->status] ?? $assembly->status }}</span>
                @if ($assembly->status === 'in_progress')
                    <form method="POST" action="{{ route('v2.inventory.assemblies.complete', $assembly->id) }}" onsubmit="return confirm('Selesaikan perakitan? Produk jadi akan masuk ke stok.')">
                        @csrf<button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">✓ Selesai</button>
                    </form>
                    <form method="POST" action="{{ route('v2.inventory.assemblies.cancel', $assembly->id) }}" onsubmit="return confirm('Batalkan perakitan? Stok bahan dikembalikan.')">
                        @csrf<button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Batal</button>
                    </form>
                @endif
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-4">
            <div><dt class="text-slate-400">Produk Jadi</dt><dd class="text-slate-700">{{ $assembly->finishedName() }}@unless($assembly->product_id)<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Manual</span>@endunless</dd></div>
            <div><dt class="text-slate-400">Jumlah Dirakit</dt><dd class="text-slate-700">{{ number_format($assembly->quantity, 0, ',', '.') }}</dd></div>
            <div><dt class="text-slate-400">Total Biaya</dt><dd class="font-semibold text-slate-800">{{ $rp($assembly->total_cost) }}</dd></div>
            <div><dt class="text-slate-400">Selesai</dt><dd class="text-slate-700">{{ $assembly->completed_at?->format('d/m/Y') ?: '—' }}</dd></div>
        </dl>

        <h3 class="mb-2 mt-6 text-sm font-semibold text-slate-900">Komponen</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Komponen</th>
                        <th class="py-2 text-right font-medium">Qty</th>
                        <th class="py-2 text-right font-medium">HPP/Unit</th>
                        <th class="py-2 text-right font-medium">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assembly->components as $c)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $c->product_name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ number_format($c->quantity, 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right">{{ $rp($c->unit_cost) }}</td>
                            <td class="py-2.5 text-right">{{ $rp($c->line_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
