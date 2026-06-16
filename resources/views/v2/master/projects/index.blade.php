@extends('v2.layouts.app')
@section('title', $label)
@section('heading', $label)

@php
    $badge = [
        'planned' => 'bg-slate-100 text-slate-600',
        'approved' => 'bg-indigo-50 text-indigo-700',
        'active' => 'bg-amber-50 text-amber-700',
        'completed' => 'bg-sky-50 text-sky-700',
        'paid' => 'bg-emerald-50 text-emerald-700',
        'on_hold' => 'bg-orange-50 text-orange-700',
        'cancelled' => 'bg-rose-50 text-rose-600',
    ];
@endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route($routeBase.'.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah {{ $label }}</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        @foreach ($columns as $header => $fn)
                            <th class="px-4 py-3 font-medium">{{ $header }}</th>
                        @endforeach
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            @foreach ($columns as $header => $fn)
                                <td class="px-4 py-3 text-slate-700">{!! $fn($record) !!}</td>
                            @endforeach
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $badge[$record->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabels[$record->status] ?? $record->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('v2.projects.status.update', $record->id) }}" class="flex items-center gap-1">
                                        @csrf
                                        <select name="status" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                            @foreach ($statusLabels as $key => $text)
                                                <option value="{{ $key }}" @selected($record->status === $key)>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                        <noscript><button class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600">Ubah</button></noscript>
                                    </form>
                                    <a href="{{ route('v2.projects.show', $record->id) }}" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Detail</a>
                                    <a href="{{ route($routeBase.'.edit', $record->id) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route($routeBase.'.destroy', $record->id) }}" onsubmit="return confirm('Hapus {{ strtolower($label) }} ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + 2 }}" class="px-4 py-10 text-center text-slate-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
