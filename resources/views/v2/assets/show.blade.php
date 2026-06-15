@extends('v2.layouts.app')
@section('title', $asset->name)
@section('heading', 'Detail Harta Tetap')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.assets.index') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Harta Tetap</a>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('v2.assets.edit', $asset) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</a>
        @if ($asset->remainingDepreciable() > 0)
            <form method="POST" action="{{ route('v2.assets.depreciate', $asset) }}" onsubmit="return confirm('Posting penyusutan bulan ini?')">
                @csrf
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Susutkan Bulan Ini ({{ $rp($asset->monthlyDepreciation()) }})</button>
            </form>
        @endif
    </div>

    <div class="max-w-xl rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $asset->name }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $asset->code ?: '—' }} · Perolehan {{ $asset->acquisition_date?->format('d/m/Y') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $asset->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $statusLabels[$asset->status] ?? $asset->status }}
            </span>
        </div>

        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Harga Perolehan</dt><dd class="font-medium">{{ $rp($asset->acquisition_cost) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Nilai Residu</dt><dd>{{ $rp($asset->salvage_value) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Masa Manfaat</dt><dd>{{ $asset->useful_life_months }} bulan</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Penyusutan / bulan</dt><dd>{{ $rp($asset->monthlyDepreciation()) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Akumulasi Penyusutan</dt><dd>{{ $rp($asset->accumulated_depreciation) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold"><dt>Nilai Buku</dt><dd>{{ $rp($asset->bookValue()) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Terakhir disusutkan</dt><dd>{{ $asset->last_depreciated_at?->format('d/m/Y') ?: '—' }}</dd></div>
        </dl>

        @if ($asset->notes)
            <p class="mt-4 text-sm text-slate-500">{{ $asset->notes }}</p>
        @endif
    </div>
@endsection
