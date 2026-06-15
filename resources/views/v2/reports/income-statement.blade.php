@extends('v2.layouts.app')
@section('title', 'Laba Rugi')
@section('heading', 'Laba Rugi')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
    </form>

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        @php
            $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
            $sectionTable = function (string $title, $rows, $total) use ($rp) {
                $html = '<div class="rounded-2xl border border-slate-200 bg-white p-5">';
                $html .= '<h2 class="mb-3 text-sm font-semibold text-slate-900">'.e($title).'</h2>';
                $html .= '<table class="w-full text-sm"><tbody>';
                foreach ($rows as $row) {
                    $html .= '<tr class="border-b border-slate-100"><td class="py-2 text-slate-600">'.e(($row['code'] ? $row['code'].' · ' : '').$row['name']).'</td><td class="py-2 text-right text-slate-800">'.$rp($row['amount']).'</td></tr>';
                }
                $html .= '</tbody><tfoot><tr class="font-semibold"><td class="pt-3 text-slate-900">Total '.e($title).'</td><td class="pt-3 text-right text-slate-900">'.$rp($total).'</td></tr></tfoot>';
                $html .= '</table></div>';
                return $html;
            };
        @endphp

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {!! $sectionTable('Pendapatan', $report['revenue'], $report['total_revenue']) !!}
            {!! $sectionTable('Beban', $report['expense'], $report['total_expense']) !!}
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-900">Laba (Rugi) Bersih</span>
                <span class="text-lg font-bold {{ $report['net_income'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $rp($report['net_income']) }}</span>
            </div>
        </div>
    @endif
@endsection
