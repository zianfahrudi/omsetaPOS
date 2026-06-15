@extends('v2.layouts.app')
@section('title', 'Rekonsiliasi '.$rec->number)
@section('heading', 'Detail Rekonsiliasi')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.cash.reconciliations') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Rekonsiliasi</a>

    <div class="max-w-lg rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $rec->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $rec->account?->name }} · {{ $rec->statement_date?->format('d F Y') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $rec->status === 'balanced' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                {{ $rec->status === 'balanced' ? 'Seimbang' : 'Selisih' }}
            </span>
        </div>

        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Saldo menurut buku</dt><dd class="font-medium">{{ $rp($rec->book_balance) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Saldo menurut bank</dt><dd class="font-medium">{{ $rp($rec->statement_balance) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-semibold {{ (float) $rec->difference != 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                <dt>Selisih</dt><dd>{{ $rp($rec->difference) }}</dd>
            </div>
        </dl>

        @if ($rec->notes)
            <p class="mt-4 text-sm text-slate-500">{{ $rec->notes }}</p>
        @endif
        @if ((float) $rec->difference != 0)
            <p class="mt-3 text-xs text-amber-600">Ada selisih. Periksa transaksi yang belum tercatat / kesalahan pencatatan.</p>
        @endif
    </div>
@endsection
