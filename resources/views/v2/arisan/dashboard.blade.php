@extends('v2.layouts.app')
@section('title', 'Dashboard Arisan')
@section('heading', 'Dashboard Arisan')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $cards = [
        ['label' => 'Arisan Aktif', 'value' => $activeGroups.' kelompok', 'accent' => 'indigo', 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72M18 18.72v-.003c0-1.113-.285-2.16-.786-3.07M18 18.72A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['label' => 'Total Peserta', 'value' => $totalMembers.' orang', 'accent' => 'sky', 'icon' => 'M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z'],
        ['label' => 'Dana Terkumpul', 'value' => $rp($totalCollected), 'accent' => 'emerald', 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['label' => 'Periode Berjalan', 'value' => $runningPeriods.' periode', 'accent' => 'violet', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
    ];
    $accents = [
        'indigo' => 'bg-indigo-50 text-indigo-600', 'sky' => 'bg-sky-50 text-sky-600',
        'violet' => 'bg-violet-50 text-violet-600', 'emerald' => 'bg-emerald-50 text-emerald-600',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Ringkasan kegiatan arisan karyawan</p>
        <a href="{{ route('v2.arisan.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Kelola Kelompok</a>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($cards as $c)
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="grid h-10 w-10 place-items-center rounded-xl {{ $accents[$c['accent']] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $c['icon'] }}"/></svg>
                </div>
                <p class="mt-3 text-xs font-medium text-slate-500">{{ $c['label'] }}</p>
                <p class="mt-1 text-xl font-bold text-slate-800">{{ $c['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Pemenang Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                    <th class="px-5 py-3 font-medium">Tanggal</th>
                    <th class="px-5 py-3 font-medium">Kelompok</th>
                    <th class="px-5 py-3 font-medium">Periode</th>
                    <th class="px-5 py-3 font-medium">Pemenang</th>
                    <th class="px-5 py-3 text-right font-medium">Dana</th>
                </tr></thead>
                <tbody>
                    @forelse ($recent as $p)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500">{{ optional($p->period_date)->format('d/m/Y') }}</td>
                            <td class="px-5 py-3"><a href="{{ route('v2.arisan.show', $p->arisan_group_id) }}" class="font-medium text-indigo-600 hover:underline">{{ $p->group?->name }}</a></td>
                            <td class="px-5 py-3 text-slate-500">#{{ $p->period_no }}</td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $p->winner?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-800">{{ $rp($p->payout?->amount ?? $p->total_collected) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada pemenang arisan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
