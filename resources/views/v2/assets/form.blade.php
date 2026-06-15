@extends('v2.layouts.app')
@section('title', $asset->exists ? 'Edit Harta Tetap' : 'Harta Tetap Baru')
@section('heading', $asset->exists ? 'Edit Harta Tetap' : 'Harta Tetap Baru')

@php
    $val = fn ($key, $default = null) => old($key, data_get($asset, $key, $default));
    $action = $asset->exists ? route('v2.assets.update', $asset) : route('v2.assets.store');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-2xl">
        @csrf
        @if ($asset->exists) @method('PUT') @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Aset</label>
                    <input type="text" name="name" value="{{ $val('name') }}" class="{{ $input }}" placeholder="Mesin CNC / Kendaraan Operasional" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Kode</label>
                    <input type="text" name="code" value="{{ $val('code') }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Tanggal Perolehan</label>
                    <input type="date" name="acquisition_date" value="{{ $val('acquisition_date') instanceof \Illuminate\Support\Carbon ? $val('acquisition_date')->toDateString() : $val('acquisition_date') }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Harga Perolehan</label>
                    <input type="number" step="0.01" min="0" name="acquisition_cost" value="{{ $val('acquisition_cost', 0) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Nilai Residu</label>
                    <input type="number" step="0.01" min="0" name="salvage_value" value="{{ $val('salvage_value', 0) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Masa Manfaat (bulan)</label>
                    <input type="number" min="1" name="useful_life_months" value="{{ $val('useful_life_months', 60) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Akun Aset</label>
                    <select name="asset_account_id" class="{{ $input }}">
                        <option value="">— Default —</option>
                        @foreach ($assetAccounts as $a)
                            <option value="{{ $a->id }}" @selected($val('asset_account_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Akun Akumulasi Penyusutan</label>
                    <select name="accumulated_account_id" class="{{ $input }}">
                        <option value="">— Default —</option>
                        @foreach ($assetAccounts as $a)
                            <option value="{{ $a->id }}" @selected($val('accumulated_account_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Akun Beban Penyusutan</label>
                    <select name="expense_account_id" class="{{ $input }}">
                        <option value="">— Default —</option>
                        @foreach ($expenseAccounts as $a)
                            <option value="{{ $a->id }}" @selected($val('expense_account_id') == $a->id)>{{ $a->code }} - {{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Catatan</label>
                    <textarea name="notes" rows="2" class="{{ $input }}">{{ $val('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.assets.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
