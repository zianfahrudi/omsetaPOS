@extends('v2.layouts.app')
@section('title', 'Buku Besar')
@section('heading', 'Buku Besar')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Akun</label>
            <select name="account_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" style="min-width:280px">
                @foreach ($accounts as $acc)
                    <option value="{{ $acc->id }}" @selected($accountId === $acc->id)>{{ $acc->code }} - {{ $acc->name }}</option>
                @endforeach
            </select>
        </div>
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

    @if (! $account)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Pilih akun.</div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Nomor</th>
                            <th class="px-4 py-3 font-medium">Keterangan</th>
                            <th class="px-4 py-3 text-right font-medium">Debit</th>
                            <th class="px-4 py-3 text-right font-medium">Kredit</th>
                            <th class="px-4 py-3 text-right font-medium">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <td class="px-4 py-2.5 text-slate-500" colspan="5">Saldo Awal</td>
                            <td class="px-4 py-2.5 text-right font-medium">{{ $rp($opening) }}</td>
                        </tr>
                        @forelse ($entries as $e)
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2.5 text-slate-500">{{ \Illuminate\Support\Carbon::parse($e['date'])->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-slate-700">{{ $e['number'] }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $e['description'] ?: '—' }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $e['debit'] ? $rp($e['debit']) : '—' }}</td>
                                <td class="px-4 py-2.5 text-right">{{ $e['credit'] ? $rp($e['credit']) : '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-medium">{{ $rp($e['balance']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada mutasi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
