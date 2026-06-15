@extends('v2.layouts.app')
@section('title', 'Daftar Akun')
@section('heading', 'Daftar Akun')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama akun…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option value="">Semua tipe</option>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $typeLabels[$type] }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
    </form>

    @forelse ($types as $type)
        @php($rows = $grouped[$type] ?? collect())
        @continue($rows->isEmpty())
        <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2.5">
                <h2 class="text-sm font-semibold text-slate-900">{{ $typeLabels[$type] }}</h2>
                <span class="text-xs text-slate-400">{{ $rows->count() }} akun</span>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    @foreach ($rows as $acc)
                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                            <td class="w-28 px-4 py-2.5 font-mono text-slate-500">{{ $acc->code }}</td>
                            <td class="px-4 py-2.5 text-slate-800">
                                <span @class(['font-semibold' => ! $acc->is_postable])>{{ $acc->name }}</span>
                                @if ($acc->is_system)
                                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">sistem</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right text-xs text-slate-400">{{ $acc->normal_balance === 'debit' ? 'D' : 'K' }}</td>
                            <td class="w-20 px-4 py-2.5 text-right">
                                @unless ($acc->is_active)
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600">nonaktif</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-slate-400">Belum ada akun.</div>
    @endforelse
@endsection
