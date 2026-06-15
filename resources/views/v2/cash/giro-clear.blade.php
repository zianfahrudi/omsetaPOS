@extends('v2.layouts.app')
@section('title', 'Cairkan Giro')
@section('heading', 'Cairkan Giro')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.cash.giros.clear.store', $giro) }}" class="max-w-lg">
        @csrf
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <dl class="mb-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm">
                <div><dt class="text-slate-400">Giro</dt><dd class="font-medium text-slate-800">{{ $giro->number }}</dd></div>
                <div><dt class="text-slate-400">Pelanggan</dt><dd class="font-medium text-slate-800">{{ $giro->customer?->name ?: '—' }}</dd></div>
                <div><dt class="text-slate-400">No. Giro</dt><dd>{{ $giro->giro_number ?: '—' }}</dd></div>
                <div><dt class="text-slate-400">Nominal</dt><dd class="font-semibold text-emerald-600">{{ $rp($giro->amount) }}</dd></div>
            </dl>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Masuk ke Akun Bank/Kas</label>
                    <select name="bank_account_id" class="{{ $input }}" required>
                        <option value="">— Pilih akun —</option>
                        @foreach ($bankAccounts as $a)
                            <option value="{{ $a->id }}" @selected(old('bank_account_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Cair</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">Pencatatan: Dr Bank/Kas · Cr Piutang Giro.</p>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Cairkan</button>
            <a href="{{ route('v2.cash.giros') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
