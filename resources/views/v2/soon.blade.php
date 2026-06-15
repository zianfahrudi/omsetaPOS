@extends('v2.layouts.app')
@section('title', $module)
@section('heading', $module)

@section('content')
    <div class="grid place-items-center rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
        <div class="max-w-sm">
            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl bg-indigo-50 text-2xl">🚧</div>
            <h2 class="text-lg font-semibold text-slate-900">{{ $module }}</h2>
            <p class="mt-1 text-sm text-slate-500">Modul ini sedang disiapkan untuk Admin v2. Sementara ini Anda masih bisa mengaksesnya lewat panel lama.</p>
            <a href="{{ route('v2.dashboard') }}" class="mt-5 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Kembali ke Dashboard</a>
        </div>
    </div>
@endsection
