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
                            <th class="py-2 pr-2 font-medium">No</th>
                            <th class="py-2 font-medium">Item</th>
                            <th class="py-2 text-right font-medium">Qty</th>
                            <th class="py-2 text-right font-medium">Harga Satuan</th>
                            <th class="py-2 text-right font-medium">Total</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    @if ($project->costs->isEmpty())
                        <tbody><tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada bahan / biaya.</td></tr></tbody>
                    @endif
                    @foreach ($project->costs as $cost)
                        <tbody x-data="{ edit: false }">
                            <tr class="border-b border-slate-100">
                                <td class="py-2.5 pr-2 text-slate-400">{{ $loop->iteration }}</td>
                                <td class="py-2.5 text-slate-700">
                                    {{ $cost->product?->name ?: ($cost->description ?: '—') }}
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ $costLabels[$cost->type] ?? $cost->type }}</span>
                                    @if ($cost->group_name)<span class="ml-1 rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-medium text-sky-600">{{ $cost->group_name }}</span>@endif
                                </td>
                                <td class="py-2.5 text-right text-slate-500">{{ $qtyFmt($cost->quantity) }}{{ $cost->unit ? ' '.$cost->unit : '' }}</td>
                                <td class="py-2.5 text-right text-slate-500">{{ $rp($cost->unit_cost) }}</td>
                                <td class="py-2.5 text-right font-medium">{{ $rp($cost->amount) }}</td>
                                <td class="py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click="edit = !edit" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</button>
                                        <form method="POST" action="{{ route('v2.projects.costs.destroy', [$project->id, $cost->id]) }}" onsubmit="return confirm('Hapus item ini?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="edit" x-cloak style="display:none" class="bg-indigo-50/40">
                                <td colspan="6" class="px-2 py-3">
                                    <form method="POST" action="{{ route('v2.projects.costs.update', [$project->id, $cost->id]) }}" class="grid grid-cols-2 gap-2 sm:grid-cols-6 items-end">
                                        @csrf @method('PUT')
                                        <div class="col-span-2">
                                            <label class="mb-1 block text-xs text-slate-500">Nama/Keterangan</label>
                                            <input type="text" name="description" value="{{ $cost->product?->name ?: $cost->description }}" class="{{ $input }}" @if($cost->product_id) readonly @endif>
                                        </div>
                                        <div class="col-span-2">
                                            <label class="mb-1 block text-xs text-slate-500">Kelompok</label>
                                            <input type="text" name="group_name" value="{{ $cost->group_name }}" class="{{ $input }}">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs text-slate-500">Jenis</label>
                                            <select name="type" class="{{ $input }}">
                                                @foreach ($costLabels as $k => $v)<option value="{{ $k }}" @selected($cost->type === $k)>{{ $v }}</option>@endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs text-slate-500">Qty</label>
                                            <input type="number" step="0.01" min="0.01" name="quantity" value="{{ (float) $cost->quantity }}" class="{{ $input }} text-right">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs text-slate-500">Satuan</label>
                                            <input type="text" name="unit" value="{{ $cost->unit }}" class="{{ $input }}">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs text-slate-500">Harga Satuan</label>
                                            <input type="number" step="0.01" min="0" name="unit_cost" value="{{ (float) $cost->unit_cost }}" class="{{ $input }} text-right">
                                        </div>
                                        <div class="col-span-2 sm:col-span-6 flex gap-2">
                                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-700">Simpan</button>
                                            <button type="button" @click="edit = false" class="rounded-lg px-4 py-2 text-xs font-medium text-slate-500 hover:bg-slate-100">Batal</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td colspan="4" class="py-2.5 text-right font-medium text-slate-500">Subtotal</td>
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
                  materials: @js($materials),
                  type: 'material',
                  productId: '',
                  materialId: '',
                  name: '',
                  group: '',
                  qty: 1,
                  unit: '',
                  unitCost: 0,
                  onProduct() { const p = this.products.find(x => String(x.id) === String(this.productId)); if (p) { this.unitCost = p.cost; if (p.unit) this.unit = p.unit; this.name = p.name; this.materialId = ''; } },
                  onMaterial() { const m = this.materials.find(x => String(x.id) === String(this.materialId)); if (m) { this.unitCost = m.cost; if (m.unit) this.unit = m.unit; this.name = m.name; this.productId = ''; } },
                  get amount() { return (Number(this.qty)||0) * (Number(this.unitCost)||0); },
                  rp(v){ return 'Rp ' + (Number(v)||0).toLocaleString('id-ID'); }
              }">
            @csrf
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Tambah Bahan / Biaya</h2>
            <div class="space-y-3">
                <div>
                    <label class="{{ $lbl }}">Jenis</label>
                    <select name="type" x-model="type" class="{{ $input }}">
                        <option value="material">Material</option>
                        <option value="upah">Upah</option>
                        <option value="operasional">Operasional</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Kelompok <span class="text-slate-400">(opsional)</span></label>
                    <input type="text" name="group_name" x-model="group" list="group-list" class="{{ $input }}" placeholder="Mis: Pekerjaan Aluminium">
                    <datalist id="group-list">@foreach ($project->costs->pluck('group_name')->filter()->unique() as $g)<option value="{{ $g }}">@endforeach</datalist>
                </div>
                <div x-show="type === 'material'">
                    <label class="{{ $lbl }}">Ambil dari Produk <span class="text-slate-400">(opsional)</span></label>
                    <select name="product_id" x-model="productId" @change="onProduct()" class="{{ $input }}">
                        <option value="">— Ketik manual / pilih produk —</option>
                        @foreach ($products as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'material'">
                    <label class="{{ $lbl }}">Ambil dari Master Material <span class="text-slate-400">(opsional)</span></label>
                    <select x-model="materialId" @change="onMaterial()" class="{{ $input }}">
                        <option value="">— Pilih material —</option>
                        @foreach ($materials as $m)
                            <option value="{{ $m['id'] }}">{{ $m['name'] }}@if($m['unit']) ({{ $m['unit'] }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $lbl }}">Nama / Keterangan Item</label>
                    <input type="text" name="description" x-model="name" class="{{ $input }}" placeholder="Mis: Casement / Upah tukang / Sewa alat">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $lbl }}">Qty</label>
                        <input type="number" step="0.01" min="0.01" name="quantity" x-model.number="qty" class="{{ $input }} text-right">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Satuan</label>
                        <input type="text" name="unit" x-model="unit" list="unit-list" class="{{ $input }}" placeholder="unit/btg/m²...">
                        <datalist id="unit-list">@foreach ($units as $u)<option value="{{ $u }}">@endforeach</datalist>
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
              tax: {{ (float) $project->tax_percent }},
              roundUnit: {{ (float) $project->rounding_unit }},
              get overheadAmt(){ return Math.round(this.subtotal * (Number(this.overhead)||0) / 100); },
              get profitAmt(){ return Math.round(this.subtotal * (Number(this.profit)||0) / 100); },
              get base(){ return this.subtotal + this.overheadAmt + this.profitAmt; },
              get taxAmt(){ return Math.round(this.base * (Number(this.tax)||0) / 100); },
              get preRound(){ return this.base + this.taxAmt; },
              get roundAmt(){ const u = Number(this.roundUnit)||0; return u > 0 ? (Math.ceil(this.preRound / u) * u - this.preRound) : 0; },
              get total(){ return this.preRound + this.roundAmt; },
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
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="{{ $lbl }}">Overhead (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="overhead_percent" x-model.number="overhead" class="{{ $input }} text-right">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">Profit (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="profit_percent" x-model.number="profit" class="{{ $input }} text-right">
                    </div>
                    <div>
                        <label class="{{ $lbl }}">PPN (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_percent" x-model.number="tax" class="{{ $input }} text-right">
                    </div>
                </div>
                <div>
                    <label class="{{ $lbl }}">Pembulatan ke</label>
                    <select name="rounding_unit" x-model.number="roundUnit" class="{{ $input }}">
                        <option value="0">Tanpa pembulatan</option>
                        <option value="100">Rp 100</option>
                        <option value="1000">Rp 1.000</option>
                        <option value="10000">Rp 10.000</option>
                        <option value="100000">Rp 100.000</option>
                    </select>
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
                <div class="flex items-center justify-between py-1" x-show="(Number(tax)||0) > 0">
                    <span class="text-slate-500">PPN (<span x-text="tax"></span>%)</span>
                    <span class="font-medium text-slate-800" x-text="rp(taxAmt)"></span>
                </div>
                <div class="flex items-center justify-between py-1" x-show="(Number(roundUnit)||0) > 0 && roundAmt > 0">
                    <span class="text-slate-500">Pembulatan</span>
                    <span class="font-medium text-slate-800" x-text="rp(roundAmt)"></span>
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

    {{-- Realisasi Biaya: Anggaran (RAB) vs Aktual --}}
    @php
        $catLabel = ['material' => 'Material', 'upah' => 'Upah', 'operasional' => 'Operasional', 'lainnya' => 'Lainnya'];
        $kontrak = $project->effectiveContractValue();
    @endphp
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Realisasi Biaya — Anggaran (RAB) vs Aktual</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-slate-500">
                            <th class="py-2 font-medium">Kategori</th>
                            <th class="py-2 text-right font-medium">Anggaran (RAB)</th>
                            <th class="py-2 text-right font-medium">Realisasi</th>
                            <th class="py-2 text-right font-medium">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['material', 'upah', 'operasional', 'lainnya'] as $cat)
                            @php
                                $rab = in_array($cat, ['material','upah','operasional']) ? $project->costByType($cat) : 0;
                                $real = $project->actualByCategory($cat);
                                $sel = $rab - $real;
                            @endphp
                            @if ($rab > 0 || $real > 0)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 text-slate-700">{{ $catLabel[$cat] }}</td>
                                    <td class="py-2 text-right text-slate-500">{{ $rp($rab) }}</td>
                                    <td class="py-2 text-right text-slate-700">{{ $rp($real) }}</td>
                                    <td class="py-2 text-right font-medium {{ $sel < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $rp($sel) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 font-bold text-slate-800">
                            <td class="py-2">TOTAL</td>
                            <td class="py-2 text-right">{{ $rp($project->totalCost()) }}</td>
                            <td class="py-2 text-right">{{ $rp($project->actualCostTotal()) }}</td>
                            <td class="py-2 text-right {{ ($project->totalCost() - $project->actualCostTotal()) < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $rp($project->totalCost() - $project->actualCostTotal()) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Daftar realisasi --}}
            <div class="mt-4 divide-y divide-slate-100 border-t border-slate-200 pt-2">
                @forelse ($project->expenses as $ex)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <p class="font-medium text-slate-700">{{ $ex->description ?: $catLabel[$ex->category] ?? $ex->category }}
                                <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">{{ $catLabel[$ex->category] ?? $ex->category }}</span>
                            </p>
                            <p class="text-xs text-slate-400">{{ $ex->date->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-rose-600">{{ $rp($ex->amount) }}</span>
                            <form method="POST" action="{{ route('v2.projects.expenses.destroy', [$project->id, $ex->id]) }}" onsubmit="return confirm('Hapus realisasi ini?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-slate-400">Belum ada realisasi biaya.</p>
                @endforelse
            </div>
        </div>

        {{-- Tambah realisasi + ringkasan laba --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-sm">
                <div class="flex items-center justify-between py-1"><span class="text-indigo-500">Nilai Kontrak</span><span class="font-semibold text-indigo-800">{{ $rp($kontrak) }}</span></div>
                <div class="flex items-center justify-between py-1"><span class="text-indigo-500">Laba Estimasi (vs RAB)</span><span class="font-medium text-indigo-700">{{ $rp($project->estimatedGrossProfit()) }}</span></div>
                <div class="mt-2 flex items-center justify-between border-t border-indigo-200 pt-2">
                    <span class="font-semibold text-indigo-900">Laba Aktual</span>
                    <span class="text-lg font-bold {{ $project->actualGrossProfit() < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ $rp($project->actualGrossProfit()) }}</span>
                </div>
            </div>
            <form method="POST" action="{{ route('v2.projects.expenses.store', $project->id) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                @csrf
                <h2 class="mb-3 text-sm font-semibold text-slate-900">Catat Realisasi Biaya</h2>
                <div class="space-y-3">
                    <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                    <div><label class="{{ $lbl }}">Kategori</label>
                        <select name="category" class="{{ $input }}">@foreach ($expenseCategories as $cat)<option value="{{ $cat }}">{{ $catLabel[$cat] ?? ucfirst($cat) }}</option>@endforeach</select>
                    </div>
                    <div><label class="{{ $lbl }}">Keterangan</label><input type="text" name="description" class="{{ $input }}" placeholder="Mis: beli kaca / bayar tukang"></div>
                    <div><label class="{{ $lbl }}">Jumlah</label><input type="number" step="0.01" min="0.01" name="amount" class="{{ $input }} text-right" required></div>
                    <button class="w-full rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Catat</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Termin Pembayaran --}}
    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
        @php $totalPaid = $project->totalPaidTerms(); $sisaTermin = max($kontrak - $totalPaid, 0); @endphp
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Termin Pembayaran</h2>
            <span class="text-xs text-slate-400">Nilai kontrak {{ $rp($kontrak) }}</span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Total Termin</p><p class="mt-0.5 font-bold text-slate-800">{{ $rp($project->totalTerms()) }}</p></div>
            <div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-600">Sudah Dibayar</p><p class="mt-0.5 font-bold text-emerald-700">{{ $rp($totalPaid) }}</p></div>
            <div class="rounded-xl bg-rose-50 p-3"><p class="text-xs text-rose-600">Sisa Tagihan</p><p class="mt-0.5 font-bold text-rose-700">{{ $rp($sisaTermin) }}</p></div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-slate-500">
                        <th class="py-2 font-medium">Termin</th>
                        <th class="py-2 font-medium">Jatuh Tempo</th>
                        <th class="py-2 text-right font-medium">Jumlah</th>
                        <th class="py-2 text-center font-medium">Status</th>
                        <th class="py-2 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($project->paymentTerms as $term)
                        <tr class="border-b border-slate-100">
                            <td class="py-2 text-slate-700">{{ $term->name }}@if($term->note)<span class="block text-xs text-slate-400">{{ $term->note }}</span>@endif</td>
                            <td class="py-2 text-slate-500">{{ $term->due_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="py-2 text-right font-medium">{{ $rp($term->amount) }}</td>
                            <td class="py-2 text-center">
                                @if ($term->is_paid)
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Lunas{{ $term->paid_date ? ' · '.$term->paid_date->format('d/m') : '' }}</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Belum</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <form method="POST" action="{{ route('v2.projects.terms.pay', [$project->id, $term->id]) }}" class="flex items-center gap-1">@csrf
                                        @unless ($term->is_paid)
                                            <select name="method" class="rounded-md border border-slate-300 px-1.5 py-1 text-xs focus:border-indigo-500 focus:outline-none">
                                                <option value="cash">Kas</option>
                                                <option value="bank">Bank</option>
                                            </select>
                                        @endunless
                                        <button class="rounded-md px-2 py-1 text-xs font-medium {{ $term->is_paid ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}">{{ $term->is_paid ? 'Batal' : 'Tandai Lunas' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('v2.projects.terms.destroy', [$project->id, $term->id]) }}" onsubmit="return confirm('Hapus termin?')">@csrf @method('DELETE')<button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">✕</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-xs text-slate-400">Belum ada termin. Tambahkan di bawah (mis. DP, Progress 50%, Pelunasan).</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('v2.projects.terms.store', $project->id) }}" class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-200 pt-4 sm:grid-cols-5 items-end">
            @csrf
            <div class="col-span-2 sm:col-span-2"><label class="{{ $lbl }}">Nama Termin</label><input type="text" name="name" class="{{ $input }}" placeholder="DP / Progress 50% / Pelunasan" required></div>
            <div><label class="{{ $lbl }}">Jumlah</label><input type="number" step="0.01" min="0" name="amount" class="{{ $input }} text-right" required></div>
            <div><label class="{{ $lbl }}">Jatuh Tempo</label><input type="date" name="due_date" class="{{ $input }}"></div>
            <div><button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Tambah</button></div>
        </form>
    </div>
@endsection
