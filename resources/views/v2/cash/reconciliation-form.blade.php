@extends('v2.layouts.app')
@section('title', 'Rekonsiliasi Bank Baru')
@section('heading', 'Rekonsiliasi Bank Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.cash.reconciliations.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Akun Kas/Bank</label>
                    <select name="account_id" class="{{ $input }}" required>
                        <option value="">— Pilih akun —</option>
                        @foreach ($bankAccounts as $a)
                            <option value="{{ $a->id }}" @selected(old('account_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Rekening Koran</label>
                    <input type="date" name="statement_date" value="{{ old('statement_date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Saldo Menurut Bank</label>
                    <input type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance') }}" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">Saldo buku diambil otomatis dari buku besar akun pada tanggal tersebut, lalu dibandingkan dengan saldo bank.</p>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Proses Rekonsiliasi</button>
            <a href="{{ route('v2.cash.reconciliations') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
