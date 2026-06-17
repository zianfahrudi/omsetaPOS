@extends('v2.layouts.app')
@section('title', 'Dashboard Payroll')
@section('heading', 'Dashboard Payroll')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $periode = \Carbon\Carbon::parse($start)->translatedFormat('F Y');
    $statusBadge = ['draft' => 'bg-slate-100 text-slate-600', 'approved' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-emerald-50 text-emerald-700'];
    $statusLabel = ['draft' => 'Draft', 'approved' => 'Disetujui', 'paid' => 'Dibayar'];
    $cards = [
        ['label' => 'Karyawan Aktif', 'value' => $totalEmployees, 'accent' => 'indigo', 'icon' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['label' => 'Total Jam ('.$periode.')', 'value' => number_format($totalHours, 1).' jam', 'accent' => 'sky', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Total Payroll', 'value' => $rp($totalPayroll), 'accent' => 'violet', 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
        ['label' => 'Total Bonus', 'value' => $rp($totalBonus), 'accent' => 'emerald', 'icon' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['label' => 'Kasbon Berjalan', 'value' => $rp($totalLoan), 'accent' => 'rose', 'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z'],
    ];
    $accents = [
        'indigo' => 'bg-indigo-50 text-indigo-600', 'sky' => 'bg-sky-50 text-sky-600',
        'violet' => 'bg-violet-50 text-violet-600', 'emerald' => 'bg-emerald-50 text-emerald-600', 'rose' => 'bg-rose-50 text-rose-600',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Ringkasan periode <strong class="text-slate-700">{{ $periode }}</strong></p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('v2.attendances.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Absensi</a>
            <a href="{{ route('v2.payrolls.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Generate Payroll</a>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-5">
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
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Payroll Terbaru</h3>
            <a href="{{ route('v2.payrolls.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                    <th class="px-5 py-3 font-medium">Karyawan</th>
                    <th class="px-5 py-3 font-medium">Periode</th>
                    <th class="px-5 py-3 text-right font-medium">Take Home Pay</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                </tr></thead>
                <tbody>
                    @forelse ($recentPayrolls as $p)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-5 py-3"><a href="{{ route('v2.payrolls.show', $p) }}" class="font-medium text-indigo-600 hover:underline">{{ $p->employee?->name }}</a></td>
                            <td class="px-5 py-3 text-slate-500">{{ $p->period_start->format('d/m') }} – {{ $p->period_end->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-800">{{ $rp($p->take_home_pay) }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusBadge[$p->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$p->status] ?? ucfirst($p->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">Belum ada payroll. Mulai dari <a href="{{ route('v2.payrolls.index') }}" class="text-indigo-600 hover:underline">Generate Payroll</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
