@extends('v2.layouts.app')
@section('title', $vehicle->exists ? 'Edit Kendaraan' : 'Tambah Kendaraan')
@section('heading', $vehicle->exists ? 'Edit Kendaraan' : 'Tambah Kendaraan')

@php
    $val = fn ($key, $default = null) => old($key, data_get($vehicle, $key, $default));
    $action = $vehicle->exists ? route('v2.vehicles.update', $vehicle) : route('v2.vehicles.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl">
        @csrf
        @if ($vehicle->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Plat Nomor</label>
                    <input type="text" name="plate_number" value="{{ $val('plate_number') }}" class="{{ $input }}" placeholder="DD 1234 XY" required>
                </div>
                <div>
                    <label class="{{ $label }}">Nama/Tipe Kendaraan</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" placeholder="Avanza Hitam">
                </div>
                <div>
                    <label class="{{ $label }}">Toko</label>
                    <select name="store_id" class="{{ $input }}" required>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected($val('store_id') == $store->id)>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Pemilik (Pelanggan)</label>
                    <select name="customer_id" class="{{ $input }}" required>
                        <option value="">— Pilih pemilik —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected($val('customer_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Kilometer</label>
                    <input type="number" min="0" name="mileage" value="{{ $val('mileage') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ $val('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.vehicles.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
