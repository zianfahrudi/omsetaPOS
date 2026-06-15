@extends('v2.layouts.app')
@section('title', 'Rekonsiliasi Bank')
@section('heading', 'Rekonsiliasi Bank')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-sm text-slate-500">Cocokkan saldo buku dengan rekening koran bank.</h2>
        <a href="{{ route('v2.cash.reconciliations.create') }}" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Rekonsiliasi</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Akun</th>
                        <th class="px-4 py-3 font-medium">Tgl Rekening Koran</th>
                        <th class="px-4 py-3 text-right font-medium">Saldo Buku</th>
                        <th class="px-4 py-3 text-right font-medium">Saldo Bank</th>
                        <th class="px-4 py-3 text-right font-medium">Selisih</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('v2.cash.reconciliations.show', $r) }}" class="font-medium text-indigo-600 hover:underline">{{ $r->number }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $r->account?->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->statement_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($r->book_balance) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($r->statement_balance) }}</td>
                            <td class="px-4 py-3 text-right {{ (float) $r->difference != 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }}">{{ $rp($r->difference) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $r->status === 'balanced' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $r->status === 'balanced' ? 'Seimbang' : 'Selisih' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada rekonsiliasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
