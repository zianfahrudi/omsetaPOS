@extends('v2.layouts.app')
@section('title', 'Bagi Hasil Laba')
@section('heading', 'Bagi Hasil Laba')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">Pembagian laba bersih ke pihak penerima (mis. Owner / Modal). Setiap pencatatan otomatis dijurnal: Dr Laba Ditahan, Cr Hutang Bagi Hasil.</p>
        <a href="{{ route('v2.profit-sharing.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Bagi Hasil Baru</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Periode</th>
                        <th class="px-4 py-3 font-medium">Penerima</th>
                        <th class="px-4 py-3 text-right font-medium">Laba Dibagi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3"><a href="{{ route('v2.profit-sharing.show', $r->id) }}" class="font-medium text-indigo-600 hover:underline">{{ $r->number }}</a></td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->period_from?->format('d/m/y') }} – {{ $r->period_to?->format('d/m/y') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $r->shares->map(fn ($s) => $s->name.' '.rtrim(rtrim(number_format((float) $s->percent, 2, ',', '.'), '0'), ',').'%')->implode(', ') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">{{ $rp($r->base_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada bagi hasil.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
