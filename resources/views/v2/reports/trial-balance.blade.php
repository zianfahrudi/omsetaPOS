@extends('v2.layouts.app')
@section('title', 'Neraca Saldo')
@section('heading', 'Neraca Saldo')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Per Tanggal</label>
            <input type="date" name="as_of" value="{{ $asOf }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Akun</th>
                        <th class="px-4 py-3 text-right font-medium">Debit</th>
                        <th class="px-4 py-3 text-right font-medium">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-2.5 font-mono text-slate-500">{{ $row['code'] }}</td>
                            <td class="px-4 py-2.5 text-slate-700">{{ $row['name'] }}</td>
                            <td class="px-4 py-2.5 text-right">{{ $row['debit'] ? $rp($row['debit']) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right">{{ $row['credit'] ? $rp($row['credit']) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400">Belum ada saldo.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-semibold text-slate-900">
                        <td colspan="2" class="px-4 py-3 text-right">Total</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totalDebit) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totalCredit) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="px-4 pb-3 text-right text-xs {{ abs($totalDebit - $totalCredit) < 0.01 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ abs($totalDebit - $totalCredit) < 0.01 ? 'Seimbang' : 'Tidak seimbang' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
