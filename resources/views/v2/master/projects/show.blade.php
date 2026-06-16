@extends('v2.layouts.app')
@section('title', 'Proyek '.$project->name)
@section('heading', 'Detail Proyek & Penawaran')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $qtyFmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $subtotal = $project->penawaranSubtotal();
@endphp

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('v2.projects.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Kembali ke Proyek</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('v2.projects.print', $project->id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cetak / Export</a>
            <a href="{{ route('v2.projects.edit', $project->id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit Proyek</a>
        </div>
    </div>

    {{-- Identitas proyek --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $project->name }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $project->code ?: '—' }} · {{ $project->customer?->name ?: 'Tanpa pelanggan' }}</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $statusLabels[$project->status] ?? $project->status }}</span>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
            <div><dt class="text-slate-400">Provinsi</dt><dd class="text-slate-700">{{ $project->province?->name ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Kabupaten/Kota</dt><dd class="text-slate-700">{{ $project->regency?->name ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Kecamatan</dt><dd class="text-slate-700">{{ $project->district?->name ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Pelanggan</dt><dd class="text-slate-700">{{ $project->customer?->name ?: '—' }}</dd></div>
            <div class="col-span-2 lg:col-span-4"><dt class="text-slate-400">Alamat Lengkap</dt><dd class="text-slate-700">{{ $project->location ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Tanggal Mulai</dt><dd class="text-slate-700">{{ $project->start_date?->format('d/m/Y') ?: '—' }}</dd></div>
            <div><dt class="text-slate-400">Tanggal Selesai</dt><dd class="text-slate-700">{{ $project->end_date?->format('d/m/Y') ?: '—' }}</dd></div>
        </dl>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Daftar bahan / RAB --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Daftar Bahan / Biaya</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Item</th>
                            <th class="py-2 text-right font-medium">Qty</th>
                            <th class="py-2 text-right font-medium">Harga Satuan</th>
                            <th class="py-2 text-right font-medium">Total</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($project->costs->sortBy('type') as $cost)
                            <tr class="border-b border-slate-100">
                                <td class="py-2.5 text-slate-700">
                                    {{ $cost->product?->name ?: ($cost->description ?: '—') }}
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ $costLabels[$cost->type] ?? $cost->type }}</span>
                                </td>
                                <td class="py-2.5 text-right text-slate-500">{{ $qtyFmt($cost->quantity) }}{{ $cost->unit ? ' '.$cost->unit : '' }}</td>
                                <td class="py-2.5 text-right text-slate-500">{{ $rp($cost->unit_cost) }}</td>
                                <td class="py-2.5 text-right font-medium">{{ $rp($cost->amount) }}</td>
                                <td class="py-2.5 text-right">
                                    <form method="POST" action="{{ route('v2.projects.costs.destroy', [$project->id, $cost->id]) }}" onsubmit="return confirm('Hapus item ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">Belum ada bahan / biaya.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td colspan="3" class="py-2.5 text-right font-medium text-slate-500">Subtotal</td>
                            <td class="py-2.5 text-right font-bold text-slate-800">{{ $rp($subtotal) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Tambah bahan / biaya --}}
        <form method="POST" action="{{ route('v2.projects.costs.store', $project->id) }}" class="rounded-2xl border border-slate-200 bg-white p-5"
              x-data="{
                  products: @js($products),
                  type: 'material',
                  productId: '',
                  qty: 1,
                  unit: '',
                  unitCost: 0,
                  onProduct() { const p = this.products.find(x => String(x.id) === String(this.productId)); if (p) { this.unitCost = p.cost; if (p.unit) this.unit = p.unit; } },
                  get amount() { return (Number(this.qty)||0) * (Number(this.unitCost)||0); },
                  rp(v){ return 'Rp ' + (Number(v)||0).toLocaleString('id-ID'); }
              }">
            @csrf
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Tambah Bahan / Biaya</h2>
            <div class="space-y-3">
                <div>
                    <label class="{{ $lbl }}">Jenis</label>
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
                        <label class="{{ $lbl }}">Satuan</label>
                        <select name="unit" x-model="unit" class="{{ $input }}">
                            <option value="">— Pilih satuan —</option>
                            @foreach ($units as $u)
                                <option value="{{ $u }}">{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="{{ $lbl }}">Harga Satuan</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" x-model.number="unitCost" class="{{ $input }} text-right">
                </div>
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                    <span class="text-slate-500">Total</span>
                    <strong x-text="rp(amount)"></strong>
                </div>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tambah</button>
            </div>
        </form>
    </div>

    {{-- Penawaran: overhead, profit, total --}}
    <form method="POST" action="{{ route('v2.projects.penawaran.update', $project->id) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6"
          x-data="{
              subtotal: {{ (float) $subtotal }},
              overhead: {{ (float) $project->overhead_percent }},
              profit: {{ (float) $project->profit_percent }},
              get overheadAmt(){ return Math.round(this.subtotal * (Number(this.overhead)||0) / 100); },
              get profitAmt(){ return Math.round(this.subtotal * (Number(this.profit)||0) / 100); },
              get total(){ return this.subtotal + this.overheadAmt + this.profitAmt; },
              rp(v){ return 'Rp ' + (Number(v)||0).toLocaleString('id-ID'); }
          }">
        @csrf @method('PUT')
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Penawaran (RAB)</h2>
            <a href="{{ route('v2.settings.project') }}" class="text-xs font-medium text-indigo-600 hover:underline">Atur default %</a>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Subtotal Bahan / Biaya</span>
                    <strong class="text-slate-800">{{ $rp($subtotal) }}</strong>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $lbl }}">Overhead (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="overhead_percent" x-model.number="overhead" class="{{ $input }} text-right">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Profit (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="profit_percent" x-model.number="profit" class="{{ $input }} text-right">
                    </div>
                </div>
                <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan Persentase</button>
            </div>

            <div class="rounded-xl bg-slate-50 p-4 text-sm">
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">Subtotal</span>
                    <span class="font-medium text-slate-800">{{ $rp($subtotal) }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">Overhead (<span x-text="overhead"></span>%)</span>
                    <span class="font-medium text-slate-800" x-text="rp(overheadAmt)"></span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">Profit (<span x-text="profit"></span>%)</span>
                    <span class="font-medium text-slate-800" x-text="rp(profitAmt)"></span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-3">
                    <span class="font-semibold text-slate-900">Total Penawaran</span>
                    <span class="text-xl font-bold text-indigo-600" x-text="rp(total)"></span>
                </div>
            </div>
        </div>
    </form>

    {{-- Status proyek: setujui penawaran --}}
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Status Proyek</h2>
                <p class="mt-1 text-xs text-slate-500">Status saat ini: <span class="font-medium text-slate-700">{{ $statusLabels[$project->status] ?? $project->status }}</span></p>
            </div>
            @if ($subtotal > 0 && $project->status === 'planned')
                <form method="POST" action="{{ route('v2.projects.approve', $project->id) }}">
                    @csrf
                    <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">✓ Setujui Penawaran</button>
                </form>
            @endif
        </div>
    </div>

    {{-- DP: hanya saat penawaran disetujui atau berjalan --}}
    @if ($subtotal > 0 && in_array($project->status, ['approved', 'active']))
    <form method="POST" action="{{ route('v2.projects.dp.update', $project->id) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6"
          x-data="{
              total: {{ (float) $project->totalPenawaran() }},
              dp: {{ (float) $project->down_payment }},
              get sisa(){ return Math.max(this.total - (Number(this.dp)||0), 0); },
              rp(v){ return 'Rp ' + (Number(v)||0).toLocaleString('id-ID'); }
          }">
        @csrf
        <h2 class="text-sm font-semibold text-slate-900">Uang Muka (DP)</h2>
        <p class="mt-1 text-xs text-slate-500">Diisi setelah penawaran disepakati pelanggan. Nilai kontrak dikunci ke total penawaran saat DP disimpan.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Total Penawaran</span>
                    <strong class="text-slate-800">{{ $rp($project->totalPenawaran()) }}</strong>
                </div>
                <div>
                    <label class="{{ $lbl }}">Jumlah DP</label>
                    <input type="number" step="0.01" min="0" name="down_payment" x-model.number="dp" class="{{ $input }} text-right">
                </div>
                <button class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan DP</button>
            </div>
            <div class="rounded-xl bg-slate-50 p-4 text-sm">
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">Nilai Kontrak (= Total Penawaran)</span>
                    <span class="font-medium text-slate-800">{{ $rp($project->totalPenawaran()) }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-500">DP Diterima</span>
                    <span class="font-medium text-emerald-600" x-text="rp(dp)"></span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-3">
                    <span class="font-semibold text-slate-900">Sisa Tagihan</span>
                    <span class="text-lg font-bold text-rose-600" x-text="rp(sisa)"></span>
                </div>
            </div>
        </div>
    </form>
    @endif
@endsection
