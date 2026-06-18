@extends('v2.layouts.app')
@section('title', $user->exists ? 'Edit Pengguna' : 'Pengguna Baru')
@section('heading', $user->exists ? 'Edit Pengguna' : 'Pengguna Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $user->exists ? route('v2.users.update', $user->id) : route('v2.users.store');
    $selectedStores = old('stores', $selectedStores);
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ $action }}" class="max-w-2xl" x-data="{ role: '{{ old('role', $user->role) }}' }">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="{{ $lbl }}">Nama</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Peran</label>
                    <select name="role" x-model="role" class="{{ $input }}" required>
                        @foreach ($roleLabels as $val => $label)
                            <option value="{{ $val }}" @selected(old('role', $user->role) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Password {{ $user->exists ? '(kosongkan bila tak diubah)' : '' }}</label>
                    <input type="password" name="password" class="{{ $input }}" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                </div>
                <div>
                    <label class="{{ $lbl }}">Ulangi Password</label>
                    <input type="password" name="password_confirmation" class="{{ $input }}" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                <label for="is_active" class="text-sm text-slate-700">Akun aktif</label>
            </div>

            {{-- Outlet: penting untuk kasir agar bisa transaksi --}}
            <div class="mt-5" x-show="role === 'cashier' || role === 'admin'">
                <label class="{{ $lbl }}">Outlet yang Diakses <span class="text-xs font-normal text-slate-400">(wajib untuk kasir)</span></label>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @forelse ($stores as $store)
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <input type="checkbox" name="stores[]" value="{{ $store->id }}" @checked(in_array($store->id, $selectedStores)) class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                            {{ $store->name }}
                        </label>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada outlet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.users.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
