@extends('v2.layouts.app')
@section('title', 'Giro Masuk')
@section('heading', 'Giro Masuk')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
@php($colors = ['received' => 'amber', 'deposited' => 'sky', 'cleared' => 'emerald', 'rejected' => 'rose'])

@section('content')
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor / no. giro…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">Semua status</option>
                @foreach ($statusLabels as $key => $text)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $text }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
        </form>
        <a href="{{ route('v2.cash.giros.create') }}" class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Giro Masuk</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nomor</th>
                        <th class="px-4 py-3 font-medium">No. Giro</th>
                        <th class="px-4 py-3 font-medium">Pelanggan</th>
                        <th class="px-4 py-3 font-medium">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-right font-medium">Nominal</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $g)
                        @php($c = $colors[$g->status] ?? 'slate')
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $g->number }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $g->giro_number ?: '—' }}<br><span class="text-xs text-slate-400">{{ $g->bank_name }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $g->customer?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $g->due_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($g->amount) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full bg-{{ $c }}-50 px-2 py-0.5 text-[11px] font-medium text-{{ $c }}-700">{{ $statusLabels[$g->status] ?? $g->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($g->status === 'received')
                                        <form method="POST" action="{{ route('v2.cash.giros.deposit', $g) }}">@csrf
                                            <button class="rounded-md px-2 py-1 text-xs font-medium text-sky-600 hover:bg-sky-50">Setor</button>
                                        </form>
                                    @endif
                                    @if ($g->isOpen())
                                        <a href="{{ route('v2.cash.giros.clear', $g) }}" class="rounded-md px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50">Cairkan</a>
                                        <form method="POST" action="{{ route('v2.cash.giros.reject', $g) }}" onsubmit="return confirm('Tandai giro ini ditolak?')">@csrf
                                            <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Tolak</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada giro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
@endsection
