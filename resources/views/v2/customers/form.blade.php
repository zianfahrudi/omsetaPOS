@extends('v2.layouts.app')
@section('title', $customer->exists ? 'Edit Pelanggan' : 'Tambah Pelanggan')
@section('heading', $customer->exists ? 'Edit Pelanggan' : 'Tambah Pelanggan')

@php
    $val = fn ($key, $default = null) => old($key, data_get($customer, $key, $default));
    $action = $customer->exists ? route('v2.customers.update', $customer) : route('v2.customers.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $label = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl"
          x-data="{
              regencies: @js($regencies),
              province: '{{ $val('province_id') }}',
              regency: '{{ $val('regency_id') }}',
              get filtered() { return this.regencies.filter(r => String(r.province_id) === String(this.province)); }
          }">
        @csrf
        @if ($customer->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Nama</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" required>
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
                    <label class="{{ $label }}">Telepon</label>
                    <input type="text" name="phone" value="{{ $val('phone') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="email" value="{{ $val('email') }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Alamat</label>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ $val('address') }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Provinsi</label>
                    <select name="province_id" x-model="province" @change="regency = ''" class="{{ $input }}">
                        <option value="">— Pilih provinsi —</option>
                        @foreach ($provinces as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Kabupaten/Kota</label>
                    <select x-model="regency" class="{{ $input }}" :class="{ 'opacity-60': !province }">
                        <option value="">— Pilih kabupaten/kota —</option>
                        <template x-for="r in filtered" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="regency_id" :value="regency">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ $val('notes') }}</textarea>
                </div>
            </div>

            @if ($customer->exists)
                <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-400">Kunjungan</dt><dd class="font-medium">{{ (int) $customer->visit_count }}</dd></div>
                    <div><dt class="text-slate-400">Total Belanja</dt><dd class="font-medium">Rp {{ number_format((float) $customer->total_spent, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-slate-400">Sisa Hutang</dt><dd class="font-medium text-rose-600">Rp {{ number_format((float) $customer->outstanding_debt, 0, ',', '.') }}</dd></div>
                </div>
            @endif
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.customers.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
