@extends('v2.layouts.app')
@section('title', 'Generate Payroll')
@section('heading', 'Payroll')

@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp
@php $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500'; @endphp

@section('content')
    <form method="POST" action="{{ route('v2.payrolls.generate') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-5">
        @csrf
        <div><label class="mb-1 block text-xs font-medium text-slate-500">Periode Awal</label><input type="date" name="period_start" value="{{ $defaultStart }}" class="{{ $input }}" required></div>
        <div><label class="mb-1 block text-xs font-medium text-slate-500">Periode Akhir</label><input type="date" name="period_end" value="{{ $defaultEnd }}" class="{{ $input }}" required></div>
        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Generate Payroll</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                <th class="px-4 py-3 font-medium">Karyawan</th>
                <th class="px-4 py-3 font-medium">Periode</th>
                <th class="px-4 py-3 text-right font-medium">Jam</th>
                <th class="px-4 py-3 text-right font-medium">Gaji Kotor</th>
                <th class="px-4 py-3 text-right font-medium">THP</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 text-right font-medium">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse ($payrolls as $p)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $p->employee?->name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $p->period_start->format('d/m') }} – {{ $p->period_end->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($p->total_hours, 1) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($p->gross_salary) }}</td>
                        <td class="px-4 py-3 text-right font-bold text-indigo-600">{{ $rp($p->take_home_pay) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ ucfirst($p->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('v2.payrolls.show', $p) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Slip</a>
                                @if($p->status==='draft')<form method="POST" action="{{ route('v2.payrolls.approve', $p) }}">@csrf<button class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Approve</button></form>@endif
                                @if($p->status==='approved')<form method="POST" action="{{ route('v2.payrolls.paid', $p) }}">@csrf<button class="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700 hover:bg-sky-100">Bayar</button></form>@endif
                                @if($p->status!=='paid')<form method="POST" action="{{ route('v2.payrolls.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button></form>@endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada payroll.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    <div class="mt-4">{{ $payrolls->links() }}</div>
@endsection
