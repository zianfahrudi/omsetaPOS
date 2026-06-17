@extends('v2.layouts.app')
@section('title', 'Kelompok Arisan')
@section('heading', 'Kelompok Arisan')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $statusBadge = ['draft' => 'bg-slate-100 text-slate-600', 'active' => 'bg-emerald-50 text-emerald-700', 'completed' => 'bg-indigo-50 text-indigo-700', 'cancelled' => 'bg-rose-50 text-rose-600'];
    $statusLabel = ['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal'];
    $methodLabel = ['random' => 'Acak', 'manual' => 'Manual', 'queue' => 'Urutan'];
@endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kelompok…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.arisan.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Kelompok Baru</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama Kelompok</th>
                        <th class="px-4 py-3 text-right font-medium">Iuran</th>
                        <th class="px-4 py-3 text-center font-medium">Peserta</th>
                        <th class="px-4 py-3 text-center font-medium">Metode</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $g)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('v2.arisan.show', $g->id) }}" class="font-medium text-indigo-600 hover:underline">{{ $g->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $rp($g->contribution_amount) }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $g->members_count }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $methodLabel[$g->draw_method] ?? $g->draw_method }}</td>
                            <td class="px-4 py-3 text-center"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusBadge[$g->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$g->status] ?? ucfirst($g->status) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.arisan.show', $g->id) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Detail</a>
                                    <a href="{{ route('v2.arisan.edit', $g->id) }}" class="rounded-md px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Edit</a>
                                    <form method="POST" action="{{ route('v2.arisan.destroy', $g->id) }}" onsubmit="return confirm('Hapus kelompok arisan ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada kelompok arisan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
