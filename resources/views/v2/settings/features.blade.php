@extends('v2.layouts.app')
@section('title', 'Modul & Fitur')
@section('heading', 'Modul & Fitur')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('v2.settings.features.update') }}" class="max-w-2xl">
        @csrf @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 class="text-sm font-semibold text-slate-900">Aktifkan / Nonaktifkan Modul</h2>
            <p class="mt-1 text-xs text-slate-500">Modul yang dinonaktifkan disembunyikan dari menu untuk pengguna non-superuser. Superuser tetap melihat semua modul.</p>

            <div class="mt-5 divide-y divide-slate-100">
                @foreach ($modules as $key => $label)
                    @php($isOn = $enabled[$key] ?? true)
                    <div class="flex items-center justify-between py-3" x-data="{ on: {{ $isOn ? 'true' : 'false' }} }">
                        <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                        <div class="flex items-center gap-3">
                            <span class="w-10 text-right text-xs font-semibold" :class="on ? 'text-indigo-600' : 'text-slate-400'" x-text="on ? 'Aktif' : 'Mati'"></span>
                            <input type="checkbox" name="modules[]" value="{{ $key }}" x-model="on" class="hidden">
                            <button type="button" @click="on = !on"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border transition-colors"
                                    :class="on ? 'bg-indigo-600 border-indigo-600' : 'bg-slate-300 border-slate-400'">
                                <span class="h-5 w-5 rounded-full bg-white shadow ring-1 ring-slate-300 transition-all"
                                      :style="on ? 'margin-left:1.375rem' : 'margin-left:0.125rem'"></span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.dashboard') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Kembali</a>
        </div>
    </form>
@endsection
