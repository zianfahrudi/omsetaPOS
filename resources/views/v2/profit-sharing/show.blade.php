@extends('v2.layouts.app')
@section('title', 'Bagi Hasil '.$distribution->number)
@section('heading', 'Detail Bagi Hasil')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.profit-sharing.index') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali</a>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $distribution->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $distribution->date?->format('d F Y') }}</p>
            </div>
            <form method="POST" action="{{ route('v2.profit-sharing.destroy', $distribution->id) }}" onsubmit="return confirm('Batalkan bagi hasil ini? Jurnal terkait akan dihapus.')">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Batalkan</button>
            </form>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-400">Periode Laba</dt><dd class="text-slate-700">{{ $distribution->period_from?->format('d/m/Y') }} – {{ $distribution->period_to?->format('d/m/Y') }}</dd></div>
            <div><dt class="text-slate-400">Laba Bersih Periode</dt><dd class="text-slate-700">{{ $rp($distribution->net_income) }}</dd></div>
            <div><dt class="text-slate-400">Laba Dibagikan</dt><dd class="font-semibold text-slate-800">{{ $rp($distribution->base_amount) }}</dd></div>
        </dl>
        @if ($distribution->notes)
            <p class="mt-3 text-sm text-slate-500">Catatan: {{ $distribution->notes }}</p>
        @endif

        <h3 class="mb-2 mt-6 text-sm font-semibold text-slate-900">Pembagian</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Pihak</th>
                        <th class="py-2 text-right font-medium">Persentase</th>
                        <th class="py-2 text-right font-medium">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($distribution->shares as $s)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">{{ $s->name }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $s->percent, 2, ',', '.'), '0'), ',') }}%</td>
                            <td class="py-2.5 text-right">{{ $rp($s->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold text-slate-900">
                        <td class="pt-3" colspan="2">Total</td>
                        <td class="pt-3 text-right">{{ $rp($distribution->shares->sum('amount')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-5 rounded-xl bg-slate-50 p-4 text-xs text-slate-500">
            Jurnal: <strong class="text-slate-700">Dr Laba Ditahan</strong> {{ $rp($distribution->base_amount) }} · <strong class="text-slate-700">Cr Hutang Bagi Hasil</strong> {{ $rp($distribution->base_amount) }}.
            Pembayaran ke penerima dicatat lewat Kas Keluar terhadap akun Hutang Bagi Hasil.
        </div>
    </div>
@endsection
