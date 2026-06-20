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
                <div><label class="{{ $lbl }}">Jabatan</label>
                    @php $curPos = old('position', $employee->position); @endphp
                    <select name="position" class="{{ $input }}">
                        <option value="">— Pilih jabatan —</option>
                        @foreach (($positions ?? []) as $pos)<option value="{{ $pos }}" @selected($curPos === $pos)>{{ $pos }}</option>@endforeach
                        @if ($curPos && ! collect($positions ?? [])->contains($curPos))<option value="{{ $curPos }}" selected>{{ $curPos }}</option>@endif
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Kelola pilihan di Data Master → Jabatan.</p>
                </div>
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

            <div class="mt-6 border-t border-slate-100 pt-5">
                <h3 class="mb-1 text-sm font-semibold text-slate-700">Akses Presensi Mobile</h3>
                <p class="mb-4 text-xs text-slate-400">Karyawan login di aplikasi mobile pakai nomor telepon &amp; password ini, lalu presensi mandiri di titik lokasi yang ditentukan.</p>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="{{ $lbl }}">Titik Lokasi Presensi</label>
                        @php $curLoc = old('attendance_location_id', $employee->attendance_location_id); @endphp
                        <select name="attendance_location_id" class="{{ $input }}">
                            <option value="">— Titik aktif perusahaan (default) —</option>
                            @foreach (($locations ?? []) as $loc)<option value="{{ $loc->id }}" @selected((int) $curLoc === $loc->id)>{{ $loc->name }} ({{ $loc->radius_meters }} m)</option>@endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Kelola di Absensi → Titik Lokasi Presensi.</p>
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Password Mobile</label>
                        <input type="password" name="password" value="" class="{{ $input }}" autocomplete="new-password" placeholder="{{ $employee->exists ? 'Kosongkan jika tidak diubah' : 'Min. 6 karakter' }}">
                        <p class="mt-1 text-xs text-slate-400">Min. 6 karakter. Kosongkan untuk menonaktifkan login mobile.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.employees.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
