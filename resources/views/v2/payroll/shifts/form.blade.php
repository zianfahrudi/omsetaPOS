@extends('v2.layouts.app')
@section('title', $shift->exists ? 'Edit Shift' : 'Tambah Shift')
@section('heading', $shift->exists ? 'Edit Shift' : 'Tambah Shift')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $shift->exists ? route('v2.shifts.update', $shift) : route('v2.shifts.store');
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 max-w-lg rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ $action }}" class="max-w-lg">
        @csrf
        @if ($shift->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-5">
            <div><label class="{{ $lbl }}">Nama Shift</label><input type="text" name="name" value="{{ old('name', $shift->name) }}" class="{{ $input }}" required></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="{{ $lbl }}">Jam Mulai</label><input type="time" name="start_time" value="{{ old('start_time', $shift->start_time ? substr($shift->start_time,0,5) : '') }}" class="{{ $input }}" required></div>
                <div><label class="{{ $lbl }}">Jam Selesai</label><input type="time" name="end_time" value="{{ old('end_time', $shift->end_time ? substr($shift->end_time,0,5) : '') }}" class="{{ $input }}" required></div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Aktif</label>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.shifts.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
