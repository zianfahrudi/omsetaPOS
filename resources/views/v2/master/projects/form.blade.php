@extends('v2.layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').$label)
@section('heading', ($record->exists ? 'Edit ' : 'Tambah ').$label)

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $record->exists ? route($routeBase.'.update', $record->id) : route($routeBase.'.store');
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl"
          x-data="{
              regencies: @js($regencies),
              districts: [],
              province: '{{ old('province_id', $record->province_id) }}',
              regency: '{{ old('regency_id', $record->regency_id) }}',
              district: '{{ old('district_id', $record->district_id) }}',
              get filteredRegencies() { return this.regencies.filter(r => String(r.province_id) === String(this.province)); },
              loadDistricts(keep = false) {
                  if (! keep) { this.district = ''; }
                  this.districts = [];
                  if (! this.regency) { return; }
                  fetch('{{ route('v2.regions.districts') }}?regency_id=' + this.regency)
                      .then(r => r.json()).then(d => { this.districts = d; });
              },
              init() { if (this.regency) { this.loadDistricts(true); } }
          }">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Proyek</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Pelanggan</label>
                    <select name="contact_id" class="{{ $input }}">
                        <option value="">— Tanpa pelanggan —</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('contact_id', $record->contact_id) == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $record->start_date?->toDateString()) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $record->end_date?->toDateString()) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Provinsi</label>
                    <select name="province_id" x-model="province" @change="regency = ''; loadDistricts()" class="{{ $input }}">
                        <option value="">— Pilih provinsi —</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Kabupaten/Kota</label>
                    <select x-model="regency" @change="loadDistricts()" x-effect="filteredRegencies; $nextTick(() => $el.value = regency)" class="{{ $input }}" :class="{ 'opacity-60': !province }">
                        <option value="">— Pilih kabupaten/kota —</option>
                        <template x-for="r in filteredRegencies" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="regency_id" :value="regency">
                </div>
                <div>
                    <label class="{{ $lbl }}">Kecamatan</label>
                    <select x-model="district" x-effect="districts; $nextTick(() => $el.value = district)" class="{{ $input }}" :class="{ 'opacity-60': !regency }">
                        <option value="">— Pilih kecamatan —</option>
                        <template x-for="d in districts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="district_id" :value="district">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Alamat Lengkap</label>
                    <textarea name="location" rows="2" class="{{ $input }}" placeholder="Nama jalan, nomor, RT/RW, kelurahan, patokan">{{ old('location', $record->location) }}</textarea>
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
