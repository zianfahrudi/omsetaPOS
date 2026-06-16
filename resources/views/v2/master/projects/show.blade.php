@extends('v2.layouts.app')
@section('title', 'Proyek '.$project->name)
@section('heading', 'Detail Proyek')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $profit = $project->tentativeProfit();
@endphp

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('v2.projects.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Proyek</a>
        <a href="{{ route('v2.projects.edit', $project->id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit Proyek</a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $project->name }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $project->code ?: '—' }} · {{ $project->customer?->name ?: 'Tanpa pelanggan' }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $statusLabels[$project->status] ?? $project->status }}</span>
        </div>

        {{-- Ringkasan keuangan --}}
        <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">Nilai Kontrak</p>
                <p class="mt-1 text-lg font-bold text-slate-800">{{ $rp($project->contract_value) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">DP Diterima</p>
                <p class="mt-1 text-lg font-bold text-emerald-600">{{ $rp($project->down_payment) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">Sisa Tagihan</p>
                <p class="mt-1 text-lg font-bold text-rose-600">{{ $rp($project->remainingBill()) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">Material</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $rp($project->costByType('material')) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">Upah</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $rp($project->costByType('upah')) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-medium text-slate-500">Operasional</p>
                <p class="mt-1 text-sm font-bold text-slate-800">{{ $rp($project->costByType('operasional')) }}</p>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-900 p-4 text-white">
            <div>
                <p class="text-xs font-medium text-slate-300">Total Biaya</p>
                <p class="text-lg font-bold">{{ $rp($project->totalCost()) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium text-slate-300">Laba Sementara (Kontrak − Biaya)</p>
                <p class="text-2xl font-bold {{ $profit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $rp($profit) }}</p>
            </div>
        </div>
    </div>

    {{-- Rincian biaya --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Rincian Biaya</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Jenis</th>
                            <th class="py-2 font-medium">Keterangan</th>
                            <th class="py-2 text-right font-medium">Qty</th>
                            <th class="py-2 text-right font-medium">Harga</th>
                            <th class="py-2 text-right font-medium">Jumlah</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->costs->sortBy('type') as $cost)
                            <tr class="border-b border-slate-100">
                                <td class="py-2.5"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $costLabels[$cost->type] ?? $cost->type }}</span></td>
                                <td class="py-2.5 text-slate-700">{{ $cost->product?->name ?: ($cost->description ?: '—') }}</td>
                                <td class="py-2.5 text-right text-slate-500">{{ rtrim(rtrim(number_format($cost->quantity, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="py-2.5 text-right text-slate-500">{{ $rp($cost->unit_cost) }}</td>
                                <td class="py-2.5 text-right font-medium">{{ $rp($cost->amount) }}</td>
                                <td class="py-2.5 text-right">
                                    <form method="POST" action="{{ route('v2.projects.costs.destroy', [$project->id, $cost->id]) }}" onsubmit="return confirm('Hapus biaya ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada biaya.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tambah biaya --}}
        <form method="POST" action="{{ route('v2.projects.costs.store', $project->id) }}" class="rounded-2xl border border-slate-200 bg-white p-5"
              x-data="{
                  products: @js($products),
                  type: 'material',
                  productId: '',
                  qty: 1,
                  unitCost: 0,
                  onProduct() { const p = this.products.find(x => String(x.id) === String(this.productId)); if (p) this.unitCost = p.cost; },
                  get amount() { return (Number(this.qty)||0) * (Number(this.unitCost)||0); },
                  rp(v){ return 'Rp ' + (Number(v)||0).toLocaleString('id-ID'); }
              }">
            @csrf
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Tambah Biaya</h2>
            <div class="space-y-3">
                <div>
                    <label class="{{ $lbl }}">Jenis Biaya</label>
                    <select name="type" x-model="type" class="{{ $input }}">
                        <option value="material">Material (dari produk)</option>
                        <option value="upah">Upah</option>
                        <option value="operasional">Operasional</option>
                    </select>
                </div>
                <div x-show="type === 'material'">
                    <label class="{{ $lbl }}">Produk</label>
                    <select name="product_id" x-model="productId" @change="onProduct()" class="{{ $input }}">
                        <option value="">— Pilih produk —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type !== 'material'">
                    <label class="{{ $lbl }}">Keterangan</label>
                    <input type="text" name="description" class="{{ $input }}" placeholder="Mis: upah tukang / sewa alat">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $lbl }}">Qty</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" x-model.number="qty" class="{{ $input }} text-right">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Harga Satuan</label>
                        <input type="number" step="0.01" min="0" name="unit_cost" x-model.number="unitCost" class="{{ $input }} text-right">
                    </div>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-slate-500">Jumlah</span>
                    <strong x-text="rp(amount)"></strong>
                </div>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tambah Biaya</button>
            </div>
        </form>
    </div>
@endsection
