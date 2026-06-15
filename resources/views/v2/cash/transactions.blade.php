@extends('v2.layouts.app')
@section('title', 'Transaksi Kas')
@section('heading', 'Transaksi Kas & Bank')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor / keterangan…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="">Semua tipe</option>
            @foreach ($typeLabels as $key => $label)
                <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Tanggal</th>
                        <th class="px-4 py-3 font-medium">Tipe</th>
                        <th class="px-4 py-3 font-medium">Akun</th>
                        <th class="px-4 py-3 font-medium">Keterangan</th>
                        <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $r)
                        @php($color = $r->type === 'in' ? 'text-emerald-600' : ($r->type === 'out' ? 'text-rose-600' : 'text-slate-700'))
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $r->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $r->type === 'in' ? 'bg-emerald-50 text-emerald-700' : ($r->type === 'out' ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700') }}">
                                    {{ $typeLabels[$r->type] ?? $r->type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $r->account?->name ?: '—' }}
                                @if ($r->type === 'transfer' && $r->toAccount)
                                    <span class="text-slate-400">→ {{ $r->toAccount->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-right font-medium {{ $color }}">{{ $rp($r->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
