@extends('v2.layouts.app')
@section('title', 'Slip Gaji · '.$payroll->employee?->name)
@section('heading', 'Slip Gaji')

@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp

@section('content')
    <div class="mb-4"><a href="{{ route('v2.payrolls.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Kembali</a></div>

    <div class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-6 print:border-0 print:shadow-none">
        <div class="text-center border-b border-slate-200 pb-4 mb-4">
            <h2 class="text-lg font-bold text-slate-900">SLIP GAJI</h2>
            <p class="mt-1 text-sm text-slate-500">Periode {{ $payroll->period_start->format('d/m/Y') }} – {{ $payroll->period_end->format('d/m/Y') }}</p>
        </div>

        <div class="text-sm space-y-1 mb-5">
            <p><span class="text-slate-400 w-28 inline-block">Nama</span>: <strong>{{ $payroll->employee?->name }}</strong></p>
            <p><span class="text-slate-400 w-28 inline-block">Jabatan</span>: {{ $payroll->employee?->position ?: '—' }}</p>
            <p><span class="text-slate-400 w-28 inline-block">Tarif/Jam</span>: {{ $rp($payroll->employee?->hourly_rate) }}</p>
        </div>

        <table class="w-full text-sm mb-5">
            <thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 text-left font-medium">Komponen</th><th class="py-2 text-right font-medium">Jumlah</th></tr></thead>
            <tbody>
                <tr class="border-b border-slate-100"><td class="py-2">Total Jam Kerja</td><td class="py-2 text-right">{{ number_format($payroll->total_hours, 2) }} jam</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2">Gaji Kotor (Jam × Tarif)</td><td class="py-2 text-right font-medium">{{ $rp($payroll->gross_salary) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-emerald-600">+ Bonus</td><td class="py-2 text-right text-emerald-600">{{ $rp($payroll->total_bonus) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Kasbon</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_loan) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Arisan</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_arisan) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Tabungan</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_savings) }}</td></tr>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-300"><td class="py-3 font-bold text-slate-900">Take Home Pay</td><td class="py-3 text-right text-xl font-bold text-indigo-600">{{ $rp($payroll->take_home_pay) }}</td></tr>
            </tfoot>
        </table>

        <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Status: {{ ucfirst($payroll->status) }}</span>
            <button onclick="window.print()" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200 print:hidden">🖨️ Cetak</button>
        </div>
    </div>
@endsection
