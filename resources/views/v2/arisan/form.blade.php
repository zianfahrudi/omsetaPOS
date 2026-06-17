@extends('v2.layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').'Kelompok Arisan')
@section('heading', ($record->exists ? 'Edit ' : 'Tambah ').'Kelompok Arisan')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $record->exists ? route('v2.arisan.update', $record->id) : route('v2.arisan.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-xl">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Kelompok</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Iuran per Periode (Rp)</label>
                    <input type="number" step="0.01" min="0" name="contribution_amount" value="{{ old('contribution_amount', $record->contribution_amount) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Metode Undian</label>
                    <select name="draw_method" class="{{ $input }}">
                        @foreach (['random' => 'Acak', 'manual' => 'Manual', 'queue' => 'Urutan Antrian'] as $val => $text)
                            <option value="{{ $val }}" @selected(old('draw_method', $record->draw_method) === $val)>{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ old('start_date', optional($record->start_date)->format('Y-m-d')) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date', optional($record->end_date)->format('Y-m-d')) }}" class="{{ $input }}">
                </div>
                @if ($record->exists)
                    <div class="sm:col-span-2">
                        <label class="{{ $lbl }}">Status</label>
                        <select name="status" class="{{ $input }}">
                            @foreach (['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal'] as $val => $text)
                                <option value="{{ $val }}" @selected(old('status', $record->status) === $val)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Catatan</label>
                    <textarea name="notes" rows="3" class="{{ $input }}">{{ old('notes', $record->notes) }}</textarea>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.arisan.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
