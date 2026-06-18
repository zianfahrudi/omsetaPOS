@extends('v2.layouts.app')
@section('title', 'Bagi Hasil Baru')
@section('heading', 'Bagi Hasil Laba Baru')

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
@endphp

@section('content')
    <a href="{{ route('v2.profit-sharing.index') }}" class="mb-4 inline-block text-sm font-medium text-indigo-600 hover:underline">← Kembali</a>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    {{-- Filter periode (reload untuk hitung ulang laba bersih) --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="{{ $lbl }}">Periode Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="{{ $input }}">
        </div>
        <div>
            <label class="{{ $lbl }}">Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="{{ $input }}">
        </div>
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Hitung Laba</button>
    </form>

    <form method="POST" action="{{ route('v2.profit-sharing.store') }}"
          x-data="{
              base: {{ (float) old('base_amount', max($netIncome, 0)) }},
              shares: {{ \Illuminate\Support\Js::from(old('shares', $defaultShares)) }},
              rp(v){ return 'Rp ' + (Math.round(Number(v)||0)).toLocaleString('id-ID'); },
              get totalPercent(){ return this.shares.reduce((s,r)=>s+(Number(r.percent)||0),0); },
              amountOf(p){ return (Number(this.base)||0) * (Number(p)||0) / 100; },
              addRow(){ this.shares.push({name:'', percent:0}); },
              removeRow(i){ this.shares.splice(i,1); }
          }">
        @csrf
        <input type="hidden" name="period_from" :value="'{{ $from }}'">
        <input type="hidden" name="period_to" :value="'{{ $to }}'">

        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Laba Periode {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</h2>
                <span class="text-xs text-slate-400">Laba bersih (laporan laba rugi): <strong class="{{ $netIncome >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Rp {{ number_format($netIncome, 0, ',', '.') }}</strong></span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="{{ $lbl }}">Tanggal Pencatatan</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Laba yang Dibagikan</label>
                    <input type="number" step="0.01" min="1" name="base_amount" x-model.number="base" class="{{ $input }} text-right" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" maxlength="500" class="{{ $input }}">
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">Pembagian</h2>
                <button type="button" @click="addRow()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">+ Tambah Pihak</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Nama Pihak</th>
                            <th class="py-2 text-right font-medium">Persentase (%)</th>
                            <th class="py-2 text-right font-medium">Nominal</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in shares" :key="i">
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-2">
                                    <input type="text" :name="`shares[${i}][name]`" x-model="row.name" maxlength="100" class="{{ $input }}" placeholder="Owner / Modal">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" step="0.01" min="0" max="100" :name="`shares[${i}][percent]`" x-model.number="row.percent" class="{{ $input }} text-right">
                                </td>
                                <td class="py-2 pr-2 text-right text-slate-700" x-text="rp(amountOf(row.percent))"></td>
                                <td class="py-2 text-right">
                                    <button type="button" @click="removeRow(i)" class="text-rose-500 hover:text-rose-700">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold text-slate-900">
                            <td class="pt-3">Total</td>
                            <td class="pt-3 text-right" :class="totalPercent > 100 ? 'text-rose-600' : 'text-slate-900'" x-text="totalPercent.toFixed(2).replace('.',',') + '%'"></td>
                            <td class="pt-3 text-right" x-text="rp(amountOf(totalPercent))"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-400" x-show="totalPercent > 100" style="display:none">Total persentase melebihi 100%.</p>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan &amp; Jurnal</button>
            <a href="{{ route('v2.profit-sharing.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>
@endsection
