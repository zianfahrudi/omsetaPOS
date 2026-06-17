@extends('v2.layouts.app')
@section('title', 'Master Shift')
@section('heading', 'Master Shift')

@section('content')
    <div class="flex items-center justify-between">
        <form method="GET" class="flex items-center gap-2"><input type="text" name="q" value="{{ request('q') }}" placeholder="Cari…" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"><button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button></form>
        <a href="{{ route('v2.shifts.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Shift</a>
    </div>
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <th class="px-4 py-3 font-medium">Nama Shift</th>
                <th class="px-4 py-3 font-medium">Jam</th>
                <th class="px-4 py-3 text-right font-medium">Durasi</th>
                <th class="px-4 py-3 text-right font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse ($shifts as $shift)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $shift->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $shift->duration_hours }} jam</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('v2.shifts.edit', $shift) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                            <form method="POST" action="{{ route('v2.shifts.destroy', $shift) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada shift.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $shifts->links() }}</div>
@endsection
