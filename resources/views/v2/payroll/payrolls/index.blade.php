@extends('v2.layouts.app')
@section('title', 'Generate Payroll')
@section('heading', 'Generate Payroll')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $statusBadge = ['draft' => 'bg-slate-100 text-slate-600', 'approved' => 'bg-amber-50 text-amber-700', 'paid' => 'bg-emerald-50 text-emerald-700'];
    $statusLabel = ['draft' => 'Draft', 'approved' => 'Disetujui', 'paid' => 'Dibayar'];
    $totalDeduction = $totals['total_loan'] + $totals['total_deduction'] + $totals['total_savings'];
    $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    // Field tersembunyi periode, diteruskan ke setiap aksi (generate/approve/bayar).
    $periodFields = [
        'period_type' => $period['period_type'],
        'month' => $period['month'],
        'week_start' => $period['week_start'],
        'start' => $period['start'],
        'end' => $period['end'],
    ];
@endphp

@section('content')
    {{-- Toolbar: pilih periode + aksi --}}
    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" x-data="{ type: '{{ $period['period_type'] }}' }" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Tipe Periode</label>
                <select name="period_type" x-model="type" onchange="this.form.requestSubmit()" class="{{ $input }}">
                    <option value="monthly">Bulanan</option>
                    <option value="weekly">Mingguan</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            {{-- Bulanan --}}
            <div x-show="type === 'monthly'">
                <x-v2.month-picker name="month" :value="$period['month']" label="Bulan" />
            </div>

            {{-- Mingguan: tanggal mulai, otomatis 7 hari --}}
            <div x-show="type === 'weekly'" x-cloak>
                <label class="mb-1 block text-xs font-medium text-slate-500">Mulai Minggu (7 hari)</label>
                <input type="date" name="week_start" value="{{ $period['week_start'] }}" onchange="this.form.requestSubmit()" class="{{ $input }}">
            </div>

            {{-- Custom: rentang bebas --}}
            <div x-show="type === 'custom'" x-cloak class="flex items-end gap-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Dari</label>
                    <input type="date" name="start" value="{{ $period['start'] }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Sampai</label>
                    <input type="date" name="end" value="{{ $period['end'] }}" class="{{ $input }}">
                </div>
                <button class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Tampilkan</button>
            </div>
        </form>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('v2.payrolls.generate') }}"
                  onsubmit="return confirm('Generate / hitung ulang payroll {{ $periodLabel }} untuk semua karyawan aktif? Payroll yang sudah dibayar tidak diubah.')">
                @csrf
                @foreach ($periodFields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                <button class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                    {{ $payrolls->isEmpty() ? 'Generate Payroll' : 'Hitung Ulang' }}
                </button>
            </form>
            @if ($draftCount > 0)
                <form method="POST" action="{{ route('v2.payrolls.bulk.approve') }}">
                    @csrf
                    @foreach ($periodFields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <button class="rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-100">Setujui Semua ({{ $draftCount }})</button>
                </form>
            @endif
            @if ($approvedCount > 0)
                <form method="POST" action="{{ route('v2.payrolls.bulk.pay') }}"
                      onsubmit="return confirm('Tandai {{ $approvedCount }} payroll sebagai DIBAYAR? Kasbon terkait akan ditandai terpotong.')">
                    @csrf
                    @foreach ($periodFields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Bayar Semua ({{ $approvedCount }})</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Karyawan Aktif</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ $activeEmployees }}</p>
            <p class="mt-0.5 text-xs text-slate-400">{{ $payrolls->count() }} sudah digenerate</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Total Gaji Kotor</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ $rp($totals['gross_salary']) }}</p>
            <p class="mt-0.5 text-xs text-emerald-600">+ bonus {{ $rp($totals['total_bonus']) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Total Potongan</p>
            <p class="mt-1 text-xl font-bold text-rose-600">{{ $rp($totalDeduction) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">kasbon + potongan + tabungan</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <p class="text-xs font-medium text-indigo-500">Total Take Home Pay</p>
            <p class="mt-1 text-xl font-bold text-indigo-700">{{ $rp($totals['take_home_pay']) }}</p>
            <p class="mt-0.5 text-xs text-indigo-400">periode {{ $periodLabel }}</p>
        </div>
    </div>

    {{-- Status ringkas --}}
    @if ($payrolls->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-2 text-xs">
            <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">Draft: {{ $draftCount }}</span>
            <span class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-700">Disetujui: {{ $approvedCount }}</span>
            <span class="rounded-full bg-emerald-50 px-3 py-1 font-medium text-emerald-700">Dibayar: {{ $paidCount }}</span>
        </div>
    @endif

    {{-- Tabel --}}
    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Karyawan</th>
                        <th class="px-4 py-3 text-right font-medium">Jam</th>
                        <th class="px-4 py-3 text-right font-medium">Gaji Kotor</th>
                        <th class="px-4 py-3 text-right font-medium">Bonus</th>
                        <th class="px-4 py-3 text-right font-medium">Potongan</th>
                        <th class="px-4 py-3 text-right font-medium">Take Home Pay</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payrolls as $p)
                        @php $deduction = (float) $p->total_loan + (float) $p->total_deduction + (float) $p->total_savings; @endphp
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('v2.payrolls.show', $p) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $p->employee?->name }}</a>
                                <span class="block text-xs text-slate-400">{{ $p->employee?->isPiecework() ? 'Borongan/Proyek' : $rp($p->employee?->hourly_rate).'/jam' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ number_format($p->total_hours, 1) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $rp($p->gross_salary) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">{{ $p->total_bonus > 0 ? '+'.$rp($p->total_bonus) : '—' }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">{{ $deduction > 0 ? '−'.$rp($deduction) : '—' }}</td>
                            <td class="px-4 py-3 text-right font-bold {{ $p->take_home_pay < 0 ? 'text-rose-600' : 'text-indigo-600' }}">{{ $rp($p->take_home_pay) }}</td>
                            <td class="px-4 py-3 text-center"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusBadge[$p->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$p->status] ?? ucfirst($p->status) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('v2.payrolls.show', $p) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Slip</a>
                                    @if ($p->status === 'draft')
                                        <form method="POST" action="{{ route('v2.payrolls.approve', $p) }}">@csrf<button class="rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-100">Setujui</button></form>
                                    @endif
                                    @if ($p->status === 'approved')
                                        <form method="POST" action="{{ route('v2.payrolls.paid', $p) }}">@csrf<button class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">Bayar</button></form>
                                    @endif
                                    @if ($p->status !== 'paid')
                                        <form method="POST" action="{{ route('v2.payrolls.destroy', $p) }}" onsubmit="return confirm('Hapus payroll ini?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center">
                            <p class="text-slate-400">Belum ada payroll untuk {{ $periodLabel }}.</p>
                            <p class="mt-1 text-xs text-slate-400">Klik <strong>Generate Payroll</strong> untuk menghitung dari absensi, bonus, dan potongan periode ini.</p>
                        </td></tr>
                    @endforelse
                </tbody>
                @if ($payrolls->isNotEmpty())
                    <tfoot>
                        <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-800">
                            <td class="px-4 py-3">TOTAL</td>
                            <td></td>
                            <td class="px-4 py-3 text-right">{{ $rp($totals['gross_salary']) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">{{ $rp($totals['total_bonus']) }}</td>
                            <td class="px-4 py-3 text-right text-rose-700">{{ $rp($totalDeduction) }}</td>
                            <td class="px-4 py-3 text-right text-indigo-700">{{ $rp($totals['take_home_pay']) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
