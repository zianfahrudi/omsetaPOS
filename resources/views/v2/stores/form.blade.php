@extends('v2.layouts.app')
@section('title', $store->exists ? 'Edit Outlet' : 'Tambah Outlet')
@section('heading', $store->exists ? 'Edit Outlet' : 'Tambah Outlet')

@php
    $val = fn ($key, $default = null) => old($key, data_get($store, $key, $default));
    $action = $store->exists ? route('v2.stores.update', $store) : route('v2.stores.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl">
        @csrf
        @if ($store->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Nama Outlet</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">Kode</label>
                    <input type="text" name="code" value="{{ $val('code') }}" class="{{ $input }}" placeholder="OUTLET-01" required>
                </div>
                <div>
                    <label class="{{ $label }}">Telepon</label>
                    <input type="text" name="phone" value="{{ $val('phone') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Alamat</label>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ $val('address') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($val('is_active', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Aktif
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.stores.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
