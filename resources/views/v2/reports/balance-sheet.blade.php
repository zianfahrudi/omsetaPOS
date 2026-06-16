@extends('v2.layouts.app')
@section('title', 'Neraca')
@section('heading', 'Neraca')

@section('content')
    <h1 class="mb-4 hidden text-xl font-bold text-slate-900 print:block">Neraca per {{ \Illuminate\Support\Carbon::parse($asOf)->format('d F Y') }}</h1>
    <form method="GET" class="no-print mb-4 flex items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Per Tanggal</label>
            <input type="date" name="as_of" value="{{ $asOf }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
        <x-v2.print-button />
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

        <div class="mb-4">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $report['balanced'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                {{ $report['balanced'] ? 'Neraca seimbang' : 'Neraca tidak seimbang' }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="space-y-6">
                {!! $sectionTable('Aset', $report['assets'], $report['total_assets']) !!}
            </div>
            <div class="space-y-6">
                {!! $sectionTable('Liabilitas', $report['liabilities'], $report['total_liabilities']) !!}
                {!! $sectionTable('Ekuitas', $report['equity'], $report['total_equity']) !!}
            </div>
        </div>
    @endif
@endsection
