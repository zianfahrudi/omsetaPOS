@extends('v2.layouts.app')
@section('title', 'Pengguna')
@section('heading', 'Pengguna')

@php
    $roleBadge = ['superuser' => 'bg-violet-50 text-violet-700', 'admin' => 'bg-indigo-50 text-indigo-700', 'cashier' => 'bg-amber-50 text-amber-700'];
@endphp

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / email…" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cari</button>
        </form>
        <a href="{{ route('v2.users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Pengguna Baru</a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Peran</th>
                        <th class="px-4 py-3 text-center font-medium">Aktif</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $u)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $u->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $u->email }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $roleBadge[$u->role] ?? 'bg-slate-100 text-slate-600' }}">{{ $roleLabels[$u->role] ?? $u->role }}</span></td>
                            <td class="px-4 py-3 text-center">{!! $u->is_active ? '<span class="text-emerald-600">●</span>' : '<span class="text-slate-300">●</span>' !!}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.users.edit', $u->id) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    @unless ($u->isSuperuser() || $u->id === auth()->id())
                                        <form method="POST" action="{{ route('v2.users.destroy', $u->id) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-600 hover:underline">Hapus</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada pengguna.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
