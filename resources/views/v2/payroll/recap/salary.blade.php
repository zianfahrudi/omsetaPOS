@extends('v2.layouts.app')
@section('title', 'Rekap Gaji')
@section('heading', 'Rekap Gaji Karyawan')

@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
            <x-v2.month-picker name="month" :value="$month" />
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Karyawan</label>
                <select name="employee_id" onchange="this.form.submit()"
                        class="w-52 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua Karyawan</option>
                    @foreach ($employees as $e)
                        <option value="{{ $e->id }}" @selected($employeeId === $e->id)>{{ $e->name }}@if($e->code) ({{ $e->code }})@endif</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tampilkan</button>
        </form>
        <a href="{{ route('v2.payrolls.recap.salary.print', ['month' => $month, 'employee_id' => $employeeId]) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m11.32-4.171L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Z"/></svg>
            Cetak
        </a>
    </div>

    <p class="mt-4 text-sm text-slate-500">Periode <strong class="text-slate-700">{{ $periodLabel }}</strong> — {{ $payrolls->count() }} karyawan</p>

    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Karyawan</th>
                        <th class="px-4 py-3 text-right font-medium">Jam</th>
                        <th class="px-4 py-3 text-right font-medium">Gaji Kotor</th>
                        <th class="px-4 py-3 text-right font-medium">Bonus</th>
                        <th class="px-4 py-3 text-right font-medium">Bon</th>
                        <th class="px-4 py-3 text-right font-medium">Potongan</th>
                        <th class="px-4 py-3 text-right font-medium">Arisan</th>
                        <th class="px-4 py-3 text-right font-medium">Tabungan</th>
                        <th class="px-4 py-3 text-right font-medium">Take Home Pay</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payrolls as $p)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $p->employee?->name ?? '—' }}<span class="ml-1 text-xs text-slate-400">{{ $p->employee?->code }}</span></td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($p->total_hours, 1) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $rp($p->gross_salary) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">{{ $rp($p->total_bonus) }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ $rp($p->total_loan) }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ $rp($p->total_deduction) }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ $rp($p->total_arisan) }}</td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $rp($p->total_savings) }}</td>
                            <td class="px-4 py-3 text-right font-bold text-indigo-600">{{ $rp($p->take_home_pay) }}</td>
                            <td class="px-4 py-3 text-center"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ ucfirst($p->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-10 text-center text-slate-400">Belum ada payroll untuk periode ini. Generate dulu di menu <a href="{{ route('v2.payrolls.index') }}" class="text-indigo-600 hover:underline">Generate Payroll</a>.</td></tr>
                    @endforelse
                </tbody>
                @if ($payrolls->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-800">
                            <td class="px-4 py-3">TOTAL</td>
                            <td class="px-4 py-3 text-right">{{ number_format($totals['total_hours'], 1) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($totals['gross_salary']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ $rp($totals['total_bonus']) }}</td>
                            <td class="px-4 py-3 text-right text-rose-700">{{ $rp($totals['total_loan']) }}</td>
                            <td class="px-4 py-3 text-right text-rose-700">{{ $rp($totals['total_deduction']) }}</td>
                            <td class="px-4 py-3 text-right text-rose-700">{{ $rp($totals['total_arisan']) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($totals['total_savings']) }}</td>
                            <td class="px-4 py-3 text-right text-indigo-700">{{ $rp($totals['take_home_pay']) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
