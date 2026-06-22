@extends('v2.layouts.app')
@section('title', 'Jurnal '.$journal->number)
@section('heading', 'Detail Jurnal')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <a href="{{ route('v2.accounting.journals') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Jurnal</a>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $journal->number }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $journal->date?->format('d F Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($journal->isManual())
                    <a href="{{ route('v2.accounting.journals.edit', $journal->id) }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">Edit</a>
                    <form method="POST" action="{{ route('v2.accounting.journals.destroy', $journal->id) }}" onsubmit="return confirm('Hapus jurnal {{ $journal->number }}? Tindakan ini tidak bisa dibatalkan.')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-100">Hapus</button>
                    </form>
                @endif
                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $journal->isPosted() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                    {{ $journal->isPosted() ? 'Posted' : ($journal->status ?: 'draft') }}
                </span>
            </div>
        </div>

        <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-400">Tipe</dt><dd class="text-slate-700">{{ $journal->type ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Referensi</dt><dd class="text-slate-700">{{ $journal->reference ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Dibuat oleh</dt><dd class="text-slate-700">{{ $journal->createdBy?->name ?: '—' }}</dd></div>
            <div class="sm:col-span-3"><dt class="text-slate-400">Keterangan</dt><dd class="text-slate-700">{{ $journal->description ?: '—' }}</dd></div>
        </dl>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Akun</th>
                        <th class="py-2 font-medium">Memo</th>
                        <th class="py-2 text-right font-medium">Debit</th>
                        <th class="py-2 text-right font-medium">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal->lines as $line)
                        <tr class="border-b border-slate-100">
                            <td class="py-2.5 text-slate-700">
                                <span class="font-mono text-xs text-slate-400">{{ $line->account?->code }}</span>
                                {{ $line->account?->name }}
                            </td>
                            <td class="py-2.5 text-slate-500">{{ $line->memo ?: '—' }}</td>
                            <td class="py-2.5 text-right">{{ (float) $line->debit ? $rp($line->debit) : '—' }}</td>
                            <td class="py-2.5 text-right">{{ (float) $line->credit ? $rp($line->credit) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-semibold text-slate-900">
                        <td colspan="2" class="pt-3 text-right">Total</td>
                        <td class="pt-3 text-right">{{ $rp($journal->total_debit) }}</td>
                        <td class="pt-3 text-right">{{ $rp($journal->total_credit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @unless ($journal->isBalanced())
            <p class="mt-3 text-sm font-medium text-rose-600">⚠ Jurnal tidak seimbang.</p>
        @endunless
    </div>
@endsection
