@extends('v2.layouts.app')
@section('title', 'Karyawan')
@section('heading', 'Karyawan')

@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari…" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.employees.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Karyawan</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <th class="px-4 py-3 font-medium">Nama</th>
                <th class="px-4 py-3 font-medium">Jabatan</th>
                <th class="px-4 py-3 text-right font-medium">Tarif/Jam</th>
                <th class="px-4 py-3 text-center font-medium">Status</th>
                <th class="px-4 py-3 text-right font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse ($employees as $emp)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3"><a href="{{ route('v2.employees.show', $emp) }}" class="font-medium text-indigo-600 hover:underline">{{ $emp->name }}</a>@if($emp->code)<span class="ml-1 text-xs text-slate-400">{{ $emp->code }}</span>@endif</td>
                        <td class="px-4 py-3 text-slate-500">{{ $emp->position ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ $emp->isPiecework() ? '—' : $rp($emp->hourly_rate) }}@if($emp->isPiecework())<span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Borongan</span>@endif</td>
                        <td class="px-4 py-3 text-center">
                            @if ($emp->is_active)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Aktif</span>
                            @else<span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600">Nonaktif</span>@endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('v2.employees.edit', $emp) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                <form method="POST" action="{{ route('v2.employees.destroy', $emp) }}" onsubmit="return confirm('Hapus karyawan ini?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada karyawan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
@endsection
