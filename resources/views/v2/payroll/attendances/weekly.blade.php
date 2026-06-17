@extends('v2.layouts.app')
@section('title', 'Absensi Mingguan')
@section('heading', 'Absensi Mingguan')

@php
    $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $start = \Carbon\Carbon::parse($weekStart);
    $prev = $start->copy()->subDays(7)->toDateString();
    $next = $start->copy()->addDays(7)->toDateString();
    $rangeLabel = $start->translatedFormat('d M').' – '.$start->copy()->addDays(6)->translatedFormat('d M Y');
@endphp

@section('content')
    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <a href="{{ route('v2.attendances.weekly', ['week_start' => $prev]) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50">‹</a>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="week_start" value="{{ $weekStart }}" onchange="this.form.submit()" class="{{ $input }}">
            </form>
            <a href="{{ route('v2.attendances.weekly', ['week_start' => $next]) }}" class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50">›</a>
            <span class="ml-1 hidden text-sm font-medium text-slate-600 sm:block">{{ $rangeLabel }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('v2.attendances.index', ['date' => $weekStart]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Mode Harian</a>
            <button type="button" onclick="fillNormal()" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Isi Normal ({{ rtrim(rtrim(number_format($standardHours, 2), '0'), '.') }} jam)</button>
        </div>
    </div>

    <form method="POST" action="{{ route('v2.attendances.weekly.save') }}" id="weekly-form">
        @csrf
        <input type="hidden" name="week_start" value="{{ $weekStart }}">

        <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-slate-500">
                            <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left font-medium">Karyawan</th>
                            @foreach ($dates as $d)
                                <th class="px-2 py-3 text-center font-medium {{ $d->isWeekend() ? 'text-rose-400' : '' }}">
                                    <div>{{ $d->translatedFormat('D') }}</div>
                                    <div class="text-[11px] font-normal text-slate-400">{{ $d->format('d/m') }}</div>
                                </th>
                            @endforeach
                            <th class="px-3 py-3 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $emp)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/60">
                                <td class="sticky left-0 z-10 bg-white px-4 py-2 font-medium text-slate-800">{{ $emp->name }}</td>
                                @foreach ($dates as $d)
                                    @php $key = $d->toDateString(); $val = $grid[$emp->id][$key] ?? null; @endphp
                                    <td class="px-1.5 py-2 text-center">
                                        <input type="number" step="0.25" min="0"
                                               name="hours[{{ $emp->id }}][{{ $key }}]"
                                               value="{{ $val !== null ? rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.') : '' }}"
                                               data-normal="{{ $standardHours }}"
                                               class="cell w-16 rounded-md border border-slate-200 px-2 py-1.5 text-center text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                               oninput="recalcRow(this)">
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right font-semibold text-slate-700 row-total">0</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($dates) + 2 }}" class="px-4 py-12 text-center text-slate-400">
                                <p class="font-medium">Belum ada karyawan per jam.</p>
                                <p class="mt-1 text-xs">Tambah karyawan dengan tipe gaji "Per Jam" terlebih dahulu.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($employees->isNotEmpty())
            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-slate-400">Isi jam dibayar per hari. Kosong = dilewati, 0 = tidak hadir. Klik <strong>Isi Normal</strong> untuk mengisi sel kosong dengan jam standar.</p>
                <button class="rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan Semua</button>
            </div>
        @endif
    </form>

    <script>
        function recalcRow(el) {
            const row = el.closest('tr');
            let sum = 0;
            row.querySelectorAll('.cell').forEach(i => { const v = parseFloat(i.value); if (!isNaN(v)) sum += v; });
            const cell = row.querySelector('.row-total');
            if (cell) cell.textContent = (Math.round(sum * 100) / 100).toString();
        }
        function fillNormal() {
            document.querySelectorAll('#weekly-form .cell').forEach(i => {
                if (i.value === '' || i.value === null) { i.value = i.dataset.normal; }
            });
            document.querySelectorAll('#weekly-form tbody tr').forEach(tr => {
                const c = tr.querySelector('.cell'); if (c) recalcRow(c);
            });
        }
        document.querySelectorAll('#weekly-form tbody tr').forEach(tr => {
            const c = tr.querySelector('.cell'); if (c) recalcRow(c);
        });
    </script>
@endsection
