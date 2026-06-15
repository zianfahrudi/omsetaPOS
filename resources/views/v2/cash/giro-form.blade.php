@extends('v2.layouts.app')
@section('title', 'Giro Masuk Baru')
@section('heading', 'Giro Masuk Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.cash.giros.store') }}" class="max-w-2xl">
        @csrf
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Pelanggan</label>
                    <select name="contact_id" class="{{ $input }}" required>
                        <option value="">— Pilih pelanggan —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('contact_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Nomor Giro/Cek</label>
                    <input type="text" name="giro_number" value="{{ old('giro_number') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Nama Bank</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Terima</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Jatuh Tempo</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nominal</label>
                    <input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" class="{{ $input }}" required>
                </div>
            </div>
            <p class="mt-4 text-xs text-slate-500">Pencatatan: Dr Piutang Giro · Cr Piutang Usaha (pelunasan piutang via giro).</p>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.cash.giros') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
