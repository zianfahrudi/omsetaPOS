@extends('v2.layouts.app')
@section('title', 'Absensi')
@section('heading', 'Absensi')

@php
    $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $statusBadge = [
        'present' => 'bg-emerald-50 text-emerald-700',
        'late' => 'bg-amber-50 text-amber-700',
        'absent' => 'bg-rose-50 text-rose-600',
        'leave' => 'bg-sky-50 text-sky-700',
        'sick' => 'bg-orange-50 text-orange-700',
        'holiday' => 'bg-indigo-50 text-indigo-700',
    ];
    $statusLabel = [
        'present' => 'Hadir', 'late' => 'Telat', 'absent' => 'Tidak Hadir',
        'leave' => 'Izin', 'sick' => 'Sakit', 'holiday' => 'Libur',
    ];
    $cHadir = $attendances->whereIn('status', ['present', 'late'])->count();
    $cTelat = $attendances->where('status', 'late')->count();
    $cAbsen = $attendances->where('status', 'absent')->count();
    $totalJam = $attendances->sum(fn ($a) => $a->payableHours());
    $fmtDate = \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y');
@endphp

@section('content')
    <div x-data="{ showAdd: false }">
        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="{{ $input }}">
                <span class="hidden text-sm text-slate-500 sm:block">{{ $fmtDate }}</span>
            </form>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('v2.attendances.from-schedule') }}">
                    @csrf <input type="hidden" name="work_date" value="{{ $date }}">
                    <button class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Buat dari Jadwal
                    </button>
                </form>
                <button type="button" @click="showAdd = !showAdd" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Manual</button>
            </div>
        </div>

        {{-- KPI --}}
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-medium text-slate-500">Hadir</p><p class="mt-1 text-xl font-bold text-emerald-600">{{ $cHadir }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-medium text-slate-500">Telat</p><p class="mt-1 text-xl font-bold text-amber-600">{{ $cTelat }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-medium text-slate-500">Tidak Hadir</p><p class="mt-1 text-xl font-bold text-rose-600">{{ $cAbsen }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs font-medium text-slate-500">Total Jam Dibayar</p><p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($totalJam, 2) }}</p></div>
        </div>

        {{-- Form tambah manual (collapsible) --}}
        <div x-show="showAdd" x-collapse style="display:none">
            <form method="POST" action="{{ route('v2.attendances.store') }}" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5">
                @csrf
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Tambah Absensi Manual</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <div class="col-span-2 sm:col-span-1"><label class="mb-1 block text-xs font-medium text-slate-500">Karyawan</label>
                        <select name="employee_id" class="{{ $input }} w-full" required>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Shift</label>
                        <select name="shift_id" class="{{ $input }} w-full"><option value="">—</option>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Tanggal</label><input type="date" name="work_date" value="{{ $date }}" class="{{ $input }} w-full" required></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Check In</label><input type="time" name="check_in" class="{{ $input }} w-full"></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Check Out</label><input type="time" name="check_out" class="{{ $input }} w-full"></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                        <select name="status" class="{{ $input }} w-full">@foreach($statuses as $st)<option value="{{ $st }}">{{ $statusLabel[$st] ?? ucfirst($st) }}</option>@endforeach</select></div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
                    <button type="button" @click="showAdd = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100">Batal</button>
                </div>
            </form>
        </div>

        {{-- Tabel absensi --}}
        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Karyawan</th>
                        <th class="px-4 py-3 font-medium">Shift</th>
                        <th class="px-4 py-3 text-center font-medium">Check In / Out</th>
                        <th class="px-4 py-3 text-right font-medium">Jam Kerja</th>
                        <th class="px-4 py-3 text-center font-medium">Jam Dibayar</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($attendances as $a)
                            <tr class="border-b border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $a->employee?->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $a->shift?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-slate-600">{{ $a->check_in?->format('H:i') ?? '—' }}</span>
                                        <span class="text-slate-300">→</span>
                                        <span class="text-slate-600">{{ $a->check_out?->format('H:i') ?? '—' }}</span>
                                        @if (!$a->check_in)
                                            <form method="POST" action="{{ route('v2.attendances.checkin', $a) }}">@csrf<button class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">In</button></form>
                                        @elseif (!$a->check_out)
                                            <form method="POST" action="{{ route('v2.attendances.checkout', $a) }}">@csrf<button class="rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100">Out</button></form>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-500">{{ number_format($a->total_hours, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <input type="number" step="0.01" min="0" name="paid_hours" form="att-{{ $a->id }}" value="{{ $a->paid_hours ?? $a->total_hours }}" class="{{ $input }} w-20 text-right">
                                </td>
                                <td class="px-4 py-3">
                                    <select name="status" form="att-{{ $a->id }}" class="{{ $input }}">
                                        @foreach ($statuses as $st)<option value="{{ $st }}" @selected($a->status === $st)>{{ $statusLabel[$st] ?? ucfirst($st) }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <form id="att-{{ $a->id }}" method="POST" action="{{ route('v2.attendances.update', $a) }}">@csrf @method('PUT')<button class="rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Simpan</button></form>
                                        <form method="POST" action="{{ route('v2.attendances.destroy', $a) }}" onsubmit="return confirm('Hapus absensi ini?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">✕</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <p class="font-medium">Belum ada absensi pada {{ $fmtDate }}</p>
                                <p class="mt-1 text-xs">Gunakan "Buat dari Jadwal" atau "Tambah Manual" di atas.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Ubah <strong>Jam Dibayar</strong> & <strong>Status</strong> langsung di baris, lalu klik Simpan. Jam dibayar inilah yang dipakai saat generate payroll.</p>
    </div>
@endsection
