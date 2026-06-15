@extends('v2.layouts.app')
@section('title', 'Transaksi Kas Baru')
@section('heading', 'Transaksi Kas Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.cash.transactions.store') }}" class="max-w-2xl"
          x-data="{ type: '{{ old('type', 'in') }}' }">
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            {{-- Jenis transaksi --}}
            <div class="mb-5">
                <label class="{{ $label }}">Jenis Transaksi</label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($typeLabels as $key => $text)
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium transition"
                               :class="type === '{{ $key }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50'">
                            <input type="radio" name="type" value="{{ $key }}" x-model="type" class="sr-only">
                            {{ $text }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Tanggal</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">Nominal</label>
                    <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" class="{{ $input }}" required>
                </div>

                {{-- Akun kas/bank (selalu tampil) --}}
                <div class="sm:col-span-2">
                    <label class="{{ $label }}" x-text="type === 'transfer' ? 'Dari Akun (Kas/Bank)' : 'Akun Kas/Bank'"></label>
                    <select name="account_id" class="{{ $input }}" required>
                        <option value="">— Pilih akun —</option>
                        @foreach ($cashAccounts as $id => $name)
                            <option value="{{ $id }}" @selected(old('account_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Akun tujuan (transfer) --}}
                <div class="sm:col-span-2" x-show="type === 'transfer'" x-cloak>
                    <label class="{{ $label }}">Ke Akun (Kas/Bank)</label>
                    <select name="to_account_id" class="{{ $input }}" :required="type === 'transfer'">
                        <option value="">— Pilih akun —</option>
                        @foreach ($cashAccounts as $id => $name)
                            <option value="{{ $id }}" @selected(old('to_account_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Akun lawan (in/out) --}}
                <div class="sm:col-span-2" x-show="type !== 'transfer'" x-cloak>
                    <label class="{{ $label }}" x-text="type === 'out' ? 'Akun Beban/Tujuan' : 'Akun Sumber Dana'"></label>
                    <select name="counter_account_id" class="{{ $input }}" :required="type !== 'transfer'">
                        <option value="">— Pilih akun —</option>
                        @foreach ($counterAccounts as $id => $name)
                            <option value="{{ $id }}" @selected(old('counter_account_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kontak (opsional, in/out) --}}
                <div class="sm:col-span-2" x-show="type !== 'transfer'" x-cloak>
                    <label class="{{ $label }}">Kontak (opsional)</label>
                    <select name="contact_id" class="{{ $input }}">
                        <option value="">— Tanpa kontak —</option>
                        @foreach ($contacts as $contact)
                            <option value="{{ $contact->id }}" @selected(old('contact_id') == $contact->id)>{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Keterangan</label>
                    <textarea name="description" rows="2" class="{{ $input }}">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.cash.transactions') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
