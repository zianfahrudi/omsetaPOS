@extends('v2.layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').$label)
@section('heading', ($record->exists ? 'Edit ' : 'Tambah ').$label)

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $record->exists ? route($routeBase.'.update', $record->id) : route($routeBase.'.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-xl">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Pajak</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" placeholder="PPN 11%" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tipe</label>
                    <select name="type" class="{{ $input }}" required>
                        @foreach ($typeLabels as $key => $text)
                            <option value="{{ $key }}" @selected(old('type', $record->type) === $key)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tarif (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="rate" value="{{ old('rate', $record->rate ?? 0) }}" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Akun Pajak</label>
                    <select name="account_id" class="{{ $input }}">
                        <option value="">— Tidak ada —</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('account_id', $record->account_id) == $acc->id)>{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Aktif
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route($routeBase.'.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
