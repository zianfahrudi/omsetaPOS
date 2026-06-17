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
                    <label class="{{ $lbl }}">Nama Material</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Kategori</label>
                    @php $curCat = old('category', $record->category); @endphp
                    <select name="category" class="{{ $input }}">
                        <option value="">— Pilih kategori —</option>
                        @foreach (($categories ?? []) as $cat)<option value="{{ $cat }}" @selected($curCat === $cat)>{{ $cat }}</option>@endforeach
                        @if ($curCat && ! collect($categories ?? [])->contains($curCat))<option value="{{ $curCat }}" selected>{{ $curCat }}</option>@endif
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Kelola pilihan di Data Master → Kategori Material.</p>
                </div>
                <div>
                    <label class="{{ $lbl }}">Satuan</label>
                    <input type="text" name="unit" value="{{ old('unit', $record->unit) }}" list="material-unit-list" class="{{ $input }}" placeholder="Batang / Lembar / m² / Lonjor">
                    <datalist id="material-unit-list">@foreach (($units ?? []) as $u)<option value="{{ $u }}">@endforeach</datalist>
                </div>
                <div>
                    <label class="{{ $lbl }}">Harga</label>
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $record->price ?? 0) }}" class="{{ $input }} text-right" required>
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
