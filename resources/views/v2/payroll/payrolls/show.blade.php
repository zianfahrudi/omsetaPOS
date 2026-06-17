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
            <p><span class="text-slate-400 w-28 inline-block">Tarif/Jam</span>: {{ $payroll->employee?->isPiecework() ? 'Borongan / Proyek' : $rp($payroll->employee?->hourly_rate) }}</p>
        </div>

        <table class="w-full text-sm mb-5">
            <thead><tr class="border-b border-slate-200 text-slate-500"><th class="py-2 text-left font-medium">Komponen</th><th class="py-2 text-right font-medium">Jumlah</th></tr></thead>
            <tbody>
                @if (! $payroll->employee?->isPiecework())
                    <tr class="border-b border-slate-100"><td class="py-2">Total Jam Kerja</td><td class="py-2 text-right">{{ number_format($payroll->total_hours, 2) }} jam</td></tr>
                @endif
                <tr class="border-b border-slate-100"><td class="py-2">{{ $payroll->employee?->isPiecework() ? 'Gaji Kotor (Borongan/Proyek)' : 'Gaji Kotor (Jam × Tarif)' }}</td><td class="py-2 text-right font-medium">{{ $rp($payroll->gross_salary) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-emerald-600">+ Bonus</td><td class="py-2 text-right text-emerald-600">{{ $rp($payroll->total_bonus) }}</td></tr>
                @if ((float) $payroll->carry_over != 0)
                    <tr class="border-b border-slate-100"><td class="py-2 text-emerald-600">+ Sisa Gaji Kemarin</td><td class="py-2 text-right text-emerald-600">{{ $rp($payroll->carry_over) }}</td></tr>
                @endif
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Kasbon</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_loan) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Potongan</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_deduction) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Arisan</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_arisan) }}</td></tr>
                <tr class="border-b border-slate-100"><td class="py-2 text-rose-600">− Tabungan</td><td class="py-2 text-right text-rose-600">{{ $rp($payroll->total_savings) }}</td></tr>
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-300"><td class="py-3 font-bold text-slate-900">Take Home Pay</td><td class="py-3 text-right text-xl font-bold text-indigo-600">{{ $rp($payroll->take_home_pay) }}</td></tr>
            </tfoot>
        </table>

        {{-- Edit Sisa Gaji Kemarin (penyesuaian manual) --}}
        @if ($payroll->status !== 'paid')
            <form method="POST" action="{{ route('v2.payrolls.carryover', $payroll) }}" class="mb-5 flex items-end gap-2 rounded-xl bg-slate-50 p-4 print:hidden">
                @csrf
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-slate-500">Sisa Gaji Kemarin / Penyesuaian (+/−)</label>
                    <input type="number" step="0.01" name="carry_over" value="{{ (float) $payroll->carry_over }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            </form>
        @endif

        <div class="flex items-center justify-between text-xs text-slate-400">
            <span>Status: {{ ucfirst($payroll->status) }}</span>
            <div class="flex items-center gap-2 print:hidden">
                @php $waUrl = $payroll->whatsappUrl($companyName ?? null); @endphp
                @if ($waUrl)
                    <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413"/></svg>
                        Kirim WhatsApp
                    </a>
                @else
                    <span class="text-slate-300" title="Nomor HP karyawan belum diisi">WhatsApp (no HP kosong)</span>
                @endif
                <a href="{{ route('v2.payrolls.slip.print', $payroll) }}" target="_blank" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">🖨️ Cetak Slip</a>
            </div>
        </div>
    </div>
@endsection
