@extends('v2.layouts.app')
@section('title', isset($journal) ? 'Edit Jurnal '.$journal->number : 'Jurnal Umum Baru')
@section('heading', isset($journal) ? 'Edit Jurnal '.$journal->number : 'Jurnal Umum Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $editing = isset($journal);
    $action = $editing ? route('v2.accounting.journals.update', $journal->id) : route('v2.accounting.journals.store');

    // Baris awal: prioritas old() (validasi gagal), lalu data jurnal (edit), lalu 2 baris kosong.
    if (old('lines')) {
        $initialRows = array_values(array_map(fn ($l) => [
            'account_id' => (string) ($l['account_id'] ?? ''),
            'memo' => $l['memo'] ?? '',
            'debit' => (float) ($l['debit'] ?? 0),
            'credit' => (float) ($l['credit'] ?? 0),
        ], old('lines')));
    } elseif ($editing) {
        $initialRows = $journal->lines->map(fn ($l) => [
            'account_id' => (string) $l->account_id,
            'memo' => $l->memo ?? '',
            'debit' => (float) $l->debit,
            'credit' => (float) $l->credit,
        ])->values()->all();
    } else {
        $initialRows = [
            ['account_id' => '', 'memo' => '', 'debit' => 0, 'credit' => 0],
            ['account_id' => '', 'memo' => '', 'debit' => 0, 'credit' => 0],
        ];
    }

    $fDate = old('date', $editing ? $journal->date?->toDateString() : now()->toDateString());
    $fRef = old('reference', $editing ? $journal->reference : '');
    $fDesc = old('description', $editing ? $journal->description : '');
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" x-data="journalForm(@js($accounts), @js($initialRows))">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="{{ $lbl }}">Tanggal</label>
                    <input type="date" name="date" value="{{ $fDate }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Referensi</label>
                    <input type="text" name="reference" value="{{ $fRef }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $lbl }}">Keterangan</label>
                    <input type="text" name="description" value="{{ $fDesc }}" class="{{ $input }}">
                </div>
            </div>

            <div class="mt-6">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Baris Jurnal</h2>
                    <button type="button" @click="addRow()" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200">+ Tambah Baris</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-slate-500">
                                <th class="py-2 font-medium" style="min-width:240px">Akun</th>
                                <th class="py-2 font-medium">Memo</th>
                                <th class="py-2 text-right font-medium" style="width:150px">Debit</th>
                                <th class="py-2 text-right font-medium" style="width:150px">Kredit</th>
                                <th class="py-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="py-2 pr-2">
                                        <select :name="`lines[${i}][account_id]`" x-model="row.account_id" class="{{ $input }}">
                                            <option value="">— Pilih akun —</option>
                                            <template x-for="a in accounts" :key="a.id">
                                                <option :value="a.id" x-text="a.name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="text" :name="`lines[${i}][memo]`" x-model="row.memo" class="{{ $input }}">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="0" step="0.01" :name="`lines[${i}][debit]`" x-model.number="row.debit" @input="if (row.debit) row.credit = 0" class="{{ $input }} text-right">
                                    </td>
                                    <td class="py-2 pr-2">
                                        <input type="number" min="0" step="0.01" :name="`lines[${i}][credit]`" x-model.number="row.credit" @input="if (row.credit) row.debit = 0" class="{{ $input }} text-right">
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" @click="removeRow(i)" x-show="rows.length > 2" class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td colspan="2" class="py-3 text-right text-slate-700">Total</td>
                                <td class="py-3 text-right" x-text="rp(totalDebit())"></td>
                                <td class="py-3 text-right" x-text="rp(totalCredit())"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="pt-1 text-right text-xs" :class="balanced() ? 'text-emerald-600' : 'text-rose-600'"
                                    x-text="balanced() ? 'Seimbang' : 'Selisih: ' + rp(Math.abs(totalDebit() - totalCredit()))"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50" :disabled="!balanced() || totalDebit() <= 0">{{ $editing ? 'Simpan Perubahan' : 'Posting Jurnal' }}</button>
            <a href="{{ $editing ? route('v2.accounting.journals.show', $journal->id) : route('v2.accounting.journals') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>

    <script>
        function journalForm(accounts, initialRows) {
            return {
                accounts,
                rows: (initialRows && initialRows.length >= 2)
                    ? initialRows.map(r => ({ account_id: String(r.account_id || ''), memo: r.memo || '', debit: Number(r.debit) || 0, credit: Number(r.credit) || 0 }))
                    : [
                        { account_id: '', memo: '', debit: 0, credit: 0 },
                        { account_id: '', memo: '', debit: 0, credit: 0 },
                    ],
                addRow() { this.rows.push({ account_id: '', memo: '', debit: 0, credit: 0 }); },
                removeRow(i) { this.rows.splice(i, 1); },
                totalDebit() { return this.rows.reduce((s, r) => s + (Number(r.debit) || 0), 0); },
                totalCredit() { return this.rows.reduce((s, r) => s + (Number(r.credit) || 0), 0); },
                balanced() { return this.totalDebit() > 0 && Math.abs(this.totalDebit() - this.totalCredit()) < 0.01; },
                rp(v) { return 'Rp ' + (Number(v) || 0).toLocaleString('id-ID'); },
            };
        }
    </script>
@endsection
