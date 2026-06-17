@extends('v2.layouts.app')
@section('title', 'Jadwal Shift')
@section('heading', 'Jadwal Shift')

@php
    $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $fmtDate = \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y');
    $grouped = $schedules->groupBy('employee_id');
    $totalShift = $schedules->count();
    $initial = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name), 0, 1));
@endphp

@section('content')
    <div x-data="{ showAdd: {{ $schedules->isEmpty() ? 'true' : 'false' }} }">
        {{-- Toolbar --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" class="{{ $input }}">
                    <select name="employee_id" onchange="this.form.submit()" class="{{ $input }}">
                        <option value="">Semua karyawan</option>
                        @foreach ($employees as $e)<option value="{{ $e->id }}" @selected($employeeId == $e->id)>{{ $e->name }}</option>@endforeach
                    </select>
                </form>
                <button type="button" @click="showAdd = !showAdd" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Jadwal
                </button>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span class="font-medium text-slate-700">{{ $fmtDate }}</span>
                <span>{{ $grouped->count() }} karyawan terjadwal</span>
                <span>{{ $totalShift }} shift</span>
            </div>
        </div>

        {{-- Form tambah (collapsible) --}}
        <div x-show="showAdd" x-collapse style="display:none">
            <form method="POST" action="{{ route('v2.schedules.store') }}" class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-5">
                @csrf
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Tambah Jadwal</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Karyawan</label>
                        <select name="employee_id" class="{{ $input }} w-full bg-white" required>@foreach($employees as $e)<option value="{{ $e->id }}" @selected($employeeId==$e->id)>{{ $e->name }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Shift</label>
                        <select name="shift_ids[]" class="{{ $input }} w-full bg-white" required><option value="">— Pilih shift —</option>@foreach($shifts as $sh)<option value="{{ $sh->id }}">{{ $sh->name }} ({{ substr($sh->start_time,0,5) }}–{{ substr($sh->end_time,0,5) }})</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-medium text-slate-500">Tanggal</label>
                        <input type="date" name="work_date" value="{{ $date }}" class="{{ $input }} w-full bg-white" required></div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
                    <button type="button" @click="showAdd = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:bg-white">Tutup</button>
                </div>
                <p class="mt-2 text-xs text-slate-400">Tambahkan beberapa shift (mis. Pagi lalu Siang) untuk karyawan yang sama di tanggal ini.</p>
            </form>
        </div>

        {{-- Daftar jadwal per karyawan --}}
        @if ($grouped->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-14 text-center">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                </div>
                <p class="mt-3 font-medium text-slate-600">Belum ada jadwal pada {{ $fmtDate }}</p>
                <p class="mt-1 text-xs text-slate-400">Klik "Tambah Jadwal" untuk menentukan shift karyawan.</p>
            </div>
        @else
            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($grouped as $rows)
                    @php $emp = $rows->first()->employee; $totalJam = $rows->sum(fn ($r) => (float) ($r->shift?->duration_hours ?? 0)); @endphp
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300 hover:shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 place-items-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ $initial($emp?->name) }}</div>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $emp?->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $rows->count() }} shift</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ rtrim(rtrim(number_format($totalJam, 2, ',', '.'), '0'), ',') }} jam</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($rows->sortBy('shift.start_time') as $s)
                                <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-2.5 pr-1.5 text-sm">
                                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                    <span class="font-medium text-slate-700">{{ $s->shift?->name }}</span>
                                    <span class="text-xs text-slate-400">{{ substr($s->shift?->start_time,0,5) }}–{{ substr($s->shift?->end_time,0,5) }}</span>
                                    <form method="POST" action="{{ route('v2.schedules.destroy', $s) }}" onsubmit="return confirm('Hapus shift ini dari jadwal?')">
                                        @csrf @method('DELETE')
                                        <button class="grid h-5 w-5 place-items-center rounded-full text-slate-400 transition hover:bg-rose-100 hover:text-rose-600" title="Hapus">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                        </button>
                                    </form>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
