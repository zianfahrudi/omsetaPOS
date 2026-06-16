@extends('v2.layouts.app')
@section('title', $contact->exists ? 'Edit Kontak' : 'Tambah Kontak')
@section('heading', $contact->exists ? 'Edit Kontak' : 'Tambah Kontak')

@php
    $val = fn ($key, $default = null) => old($key, data_get($contact, $key, $default));
    $action = $contact->exists ? route('v2.contacts.update', $contact) : route('v2.contacts.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl"
          x-data="{
              regencies: @js($regencies),
              districts: [],
              province: '{{ $val('province_id') }}',
              regency: '{{ $val('regency_id') }}',
              district: '{{ $val('district_id') }}',
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
        @if ($contact->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Nama</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $label }}">Kode</label>
                    <input type="text" name="code" value="{{ $val('code') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Tipe</label>
                    <select name="type" class="{{ $input }}" required>
                        @foreach ($typeLabels as $key => $label2)
                            <option value="{{ $key }}" @selected($val('type') === $key)>{{ $label2 }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Telepon</label>
                    <input type="text" name="phone" value="{{ $val('phone') }}" class="{{ $input }}">
                    <p class="mt-1 text-xs text-slate-400">Lengkapi nomor telepon agar mudah dihubungi untuk tindak lanjut transaksi.</p>
                </div>
                <div>
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="email" value="{{ $val('email') }}" class="{{ $input }}">
                    <p class="mt-1 text-xs text-slate-400">Lengkapi alamat email agar mudah dihubungi untuk tindak lanjut transaksi.</p>
                </div>
                <div>
                    <label class="{{ $label }}">NPWP</label>
                    <input type="text" name="tax_number" value="{{ $val('tax_number') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Alamat</label>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ $val('address') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Provinsi</label>
                    <select name="province_id" x-model="province" @change="regency = ''; loadDistricts()" class="{{ $input }}">
                        <option value="">— Pilih provinsi —</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Kabupaten/Kota</label>
                    <select x-model="regency" @change="loadDistricts()" x-effect="filteredRegencies; $nextTick(() => $el.value = regency)" class="{{ $input }}" :class="{ 'opacity-60': !province }">
                        <option value="">— Pilih kabupaten/kota —</option>
                        <template x-for="r in filteredRegencies" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="regency_id" :value="regency">
                </div>
                <div>
                    <label class="{{ $label }}">Kecamatan</label>
                    <select x-model="district" x-effect="districts; $nextTick(() => $el.value = district)" class="{{ $input }}" :class="{ 'opacity-60': !regency }">
                        <option value="">— Pilih kecamatan —</option>
                        <template x-for="d in districts" :key="d.id">
                            <option :value="d.id" x-text="d.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="district_id" :value="district">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ $val('notes') }}</textarea>
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
            <a href="{{ route('v2.contacts') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
