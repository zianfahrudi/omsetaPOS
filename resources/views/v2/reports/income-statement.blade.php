@extends('v2.layouts.app')
@section('title', 'Laba Rugi')
@section('heading', 'Laba Rugi')

@section('content')
    <h1 class="mb-4 hidden text-xl font-bold text-slate-900 print:block">Laba Rugi {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</h1>
    <form method="GET" class="no-print mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
        <x-v2.print-button />
    </form>

    @if (! $report)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Perusahaan belum dikonfigurasi.</div>
    @else
        @php
            $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
            $groupedTable = function (string $title, $groups, $total) use ($rp) {
                $html = '<div class="rounded-2xl border border-slate-200 bg-white p-5">';
                $html .= '<h2 class="mb-3 text-sm font-semibold text-slate-900">'.e($title).'</h2>';
                $html .= '<table class="w-full text-sm"><tbody>';
                foreach ($groups as $group) {
                    $multi = count($group['rows']) > 1 || $group['group_name'] !== 'Lainnya';
                    if ($multi) {
                        $html .= '<tr class="bg-slate-50/60"><td class="py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500" colspan="2">'.e($group['group_name']).'</td></tr>';
                    }
                    foreach ($group['rows'] as $row) {
                        $label = ($row['code'] ? $row['code'].' · ' : '').$row['name'];
                        $pad = $multi ? ' pl-4' : '';
                        $html .= '<tr class="border-b border-slate-100"><td class="py-2 text-slate-600'.$pad.'">'.e($label).'</td><td class="py-2 text-right text-slate-800">'.$rp($row['amount']).'</td></tr>';
                    }
                    if ($multi) {
                        $html .= '<tr class="border-b border-slate-100 text-xs text-slate-500"><td class="py-1 pl-4">Subtotal '.e($group['group_name']).'</td><td class="py-1 text-right font-medium">'.$rp($group['subtotal']).'</td></tr>';
                    }
                }
                $html .= '</tbody><tfoot><tr class="font-semibold"><td class="pt-3 text-slate-900">Total '.e($title).'</td><td class="pt-3 text-right text-slate-900">'.$rp($total).'</td></tr></tfoot>';
                $html .= '</table></div>';
                return $html;
            };
        @endphp

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {!! $groupedTable('Pendapatan', $report['revenue_groups'], $report['total_revenue']) !!}
            {!! $groupedTable('Beban', $report['expense_groups'], $report['total_expense']) !!}
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-900">Laba (Rugi) Bersih</span>
                <span class="text-lg font-bold {{ $report['net_income'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $rp($report['net_income']) }}</span>
            </div>
        </div>
    @endif
@endsection
