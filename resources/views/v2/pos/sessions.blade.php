@extends('v2.layouts.app')
@section('title', 'Sesi Kasir')
@section('heading', 'Sesi Kasir')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    {{-- Status sesi aktif / buka sesi --}}
    @if ($openSession)
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5" x-data="{ open: false }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Sesi aktif: {{ $openSession->number }}</p>
                    <p class="mt-0.5 text-sm text-emerald-700">{{ $openSession->store?->name }} · dibuka {{ $openSession->opened_at?->format('d/m/Y H:i') }} · Kas awal {{ $rp($openSession->opening_cash) }}</p>
                </div>
                <button @click="open = !open" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Tutup Sesi</button>
            </div>
            <form x-show="open" x-cloak method="POST" action="{{ route('v2.pos.sessions.close', $openSession) }}" class="mt-4 border-t border-emerald-200 pt-4">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Kas Akhir (hasil hitung fisik)</label>
                        <input type="number" step="0.01" min="0" name="closing_cash" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                        <input type="text" name="notes" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Sistem hitung kas seharusnya = kas awal + penjualan tunai selama sesi, lalu bandingkan dengan kas akhir.</p>
                <button class="mt-3 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Konfirmasi Tutup Sesi</button>
            </form>
        </div>
    @else
        <form method="POST" action="{{ route('v2.pos.sessions.open') }}" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Buka Sesi Kasir</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Toko</label>
                    <select name="store_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Kas Awal</label>
                    <input type="number" step="0.01" min="0" name="opening_cash" value="{{ old('opening_cash', 0) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                </div>
                <div class="flex items-end">
                    <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Buka Sesi</button>
                </div>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">Kasir</th>
                        <th class="px-4 py-3 font-medium">Buka</th>
                        <th class="px-4 py-3 font-medium">Tutup</th>
                        <th class="px-4 py-3 text-right font-medium">Kas Awal</th>
                        <th class="px-4 py-3 text-right font-medium">Penjualan Tunai</th>
                        <th class="px-4 py-3 text-right font-medium">Kas Akhir</th>
                        <th class="px-4 py-3 text-right font-medium">Selisih</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $s)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $s->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->cashier?->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->opened_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $s->closed_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($s->opening_cash) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($s->cash_sales_total) }}</td>
                            <td class="px-4 py-3 text-right">{{ $s->closed_at ? $rp($s->closing_cash) : '—' }}</td>
                            <td class="px-4 py-3 text-right {{ (float) $s->cash_difference != 0 ? 'text-rose-600 font-medium' : 'text-slate-500' }}">{{ $s->closed_at ? $rp($s->cash_difference) : '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $s->isOpen() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $s->isOpen() ? 'Terbuka' : 'Tutup' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada sesi kasir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
