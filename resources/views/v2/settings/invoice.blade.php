@extends('v2.layouts.app')
@section('title', 'Pengaturan Faktur')
@section('heading', 'Pengaturan Faktur')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('v2.settings.invoice.update') }}" class="max-w-2xl">
        @csrf @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Identitas Perusahaan</h2>
            <p class="mt-1 text-xs text-slate-500">Tampil di kepala faktur (dan dokumen lain).</p>
            <div class="mt-4 space-y-5">
                <div>
                    <label class="{{ $lbl }}">Nama Perusahaan</label>
                    <input type="text" name="name" maxlength="150" value="{{ old('name', $company->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Alamat</label>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ old('address', $company->address) }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Telepon</label>
                        <input type="text" name="phone" maxlength="50" value="{{ old('phone', $company->phone) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Email</label>
                        <input type="email" name="email" maxlength="150" value="{{ old('email', $company->email) }}" class="{{ $input }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Nomor &amp; Jatuh Tempo</h2>
            <p class="mt-1 text-xs text-slate-500">Prefix dipakai untuk menyusun nomor faktur, mis. <code>INV/PRJ-01/20260617</code>.</p>
            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Prefix Nomor Faktur</label>
                    <input type="text" name="invoice_prefix" maxlength="20" value="{{ old('invoice_prefix', $company->invoice_prefix) }}" class="{{ $input }}" placeholder="INV">
                </div>
                <div>
                    <label class="{{ $lbl }}">Jatuh Tempo Default (hari)</label>
                    <input type="number" min="0" max="365" name="invoice_due_days" value="{{ old('invoice_due_days', $company->invoice_due_days) }}" class="{{ $input }} text-right">
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Rekening Pembayaran</h2>
            <p class="mt-1 text-xs text-slate-500">Ditampilkan di bagian bawah faktur sebagai tujuan pembayaran.</p>
            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="{{ $lbl }}">Nama Bank</label>
                    <input type="text" name="invoice_bank_name" maxlength="100" value="{{ old('invoice_bank_name', $company->invoice_bank_name) }}" class="{{ $input }}" placeholder="BCA">
                </div>
                <div>
                    <label class="{{ $lbl }}">No. Rekening</label>
                    <input type="text" name="invoice_bank_account" maxlength="50" value="{{ old('invoice_bank_account', $company->invoice_bank_account) }}" class="{{ $input }}" placeholder="1234567890">
                </div>
                <div>
                    <label class="{{ $lbl }}">Atas Nama</label>
                    <input type="text" name="invoice_bank_holder" maxlength="100" value="{{ old('invoice_bank_holder', $company->invoice_bank_holder) }}" class="{{ $input }}">
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Tanda Tangan &amp; Catatan</h2>
            <div class="mt-4 space-y-5">
                <div>
                    <label class="{{ $lbl }}">Nama Penanda Tangan</label>
                    <input type="text" name="invoice_signature_name" maxlength="100" value="{{ old('invoice_signature_name', $company->invoice_signature_name) }}" class="{{ $input }}" placeholder="Nama / jabatan">
                </div>
                <div>
                    <label class="{{ $lbl }}">Catatan Kaki Faktur</label>
                    <textarea name="invoice_note" rows="3" class="{{ $input }}" placeholder="Mis. Pembayaran paling lambat 14 hari setelah faktur diterbitkan.">{{ old('invoice_note', $company->invoice_note) }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.projects.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kembali</a>
        </div>
    </form>
@endsection
