@extends('v2.layouts.app')
@section('title', $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('heading', $employee->exists ? 'Edit Karyawan' : 'Tambah Karyawan')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $employee->exists ? route('v2.employees.update', $employee) : route('v2.employees.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl" x-data="{ earning: '{{ old('earning_type', $employee->earning_type ?? 'hourly') }}' }">
        @csrf
        @if ($employee->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="{{ $lbl }}">Nama</label><input type="text" name="name" value="{{ old('name', $employee->name) }}" class="{{ $input }}" required></div>
                <div><label class="{{ $lbl }}">Kode</label><input type="text" name="code" value="{{ old('code', $employee->code) }}" class="{{ $input }}"></div>
                <div><label class="{{ $lbl }}">Jabatan</label><input type="text" name="position" value="{{ old('position', $employee->position) }}" class="{{ $input }}"></div>
                <div><label class="{{ $lbl }}">Telepon</label><input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="{{ $input }}"></div>
                <div>
                    <label class="{{ $lbl }}">Tipe Gaji</label>
                    <select name="earning_type" x-model="earning" class="{{ $input }}">
                        <option value="hourly">Per Jam (Absensi)</option>
                        <option value="piecework">Borongan / Proyek</option>
                    </select>
                </div>
                <div x-show="earning === 'hourly'">
                    <label class="{{ $lbl }}">Tarif Per Jam (Rp)</label>
                    <input type="number" step="0.01" min="0" name="hourly_rate" value="{{ old('hourly_rate', $employee->hourly_rate ?? 0) }}" class="{{ $input }} text-right">
                </div>
                <div x-show="earning === 'piecework'" x-cloak class="sm:col-span-2">
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">Gaji dihitung dari item pekerjaan (borongan/proyek) yang diinput di detail karyawan, bukan dari jam absensi.</p>
                </div>
                <div><label class="{{ $lbl }}">Tanggal Bergabung</label><input type="date" name="join_date" value="{{ old('join_date', $employee->join_date?->toDateString()) }}" class="{{ $input }}"></div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Aktif
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.employees.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
