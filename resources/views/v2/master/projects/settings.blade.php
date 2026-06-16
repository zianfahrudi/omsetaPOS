@extends('v2.layouts.app')
@section('title', 'Pengaturan Penawaran Proyek')
@section('heading', 'Pengaturan Penawaran Proyek')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ route('v2.settings.project.update') }}" class="max-w-xl">
        @csrf @method('PUT')
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <p class="text-sm text-slate-500">Persentase default ini dipakai sebagai nilai awal overhead &amp; profit saat membuat proyek baru. Tetap bisa diubah per proyek.</p>
            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Default Overhead (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="default_overhead_percent" value="{{ old('default_overhead_percent', $company->default_overhead_percent) }}" class="{{ $input }} text-right">
                </div>
                <div>
                    <label class="{{ $lbl }}">Default Profit (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="default_profit_percent" value="{{ old('default_profit_percent', $company->default_profit_percent) }}" class="{{ $input }} text-right">
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.projects.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kembali</a>
        </div>
    </form>
@endsection
