@extends('v2.layouts.app')
@section('title', 'Karyawan · '.$employee->name)
@section('heading', 'Detail Karyawan')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-xs font-medium text-slate-500';
    $saving = $employee->savings->first();
    $initial = mb_strtoupper(mb_substr(trim($employee->name), 0, 1));
    $totalBonus = $employee->bonuses->sum('amount');
    $kasbonPending = $employee->outstandingLoanTotal();
    $potonganTetap = ($saving?->active ? $saving->amount : 0);
    $statusBadge = [
        'present' => 'bg-emerald-50 text-emerald-700', 'late' => 'bg-amber-50 text-amber-700',
        'absent' => 'bg-rose-50 text-rose-600', 'leave' => 'bg-sky-50 text-sky-700',
        'sick' => 'bg-orange-50 text-orange-700', 'holiday' => 'bg-indigo-50 text-indigo-700',
    ];
    $statusLabel = ['present'=>'Hadir','late'=>'Telat','absent'=>'Tidak Hadir','leave'=>'Izin','sick'=>'Sakit','holiday'=>'Libur'];
    $loanLabel = ['pending'=>'Belum Lunas','paid'=>'Lunas','deducted'=>'Dipotong'];
@endphp

@section('content')
<div x-data="{ tab: 'bonus', addBonus: false, addLoan: false, addDeduction: false, addWork: false, addSaving: false }">
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('v2.employees.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">← Kembali ke daftar karyawan</a>
        <a href="{{ route('v2.employees.edit', $employee) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit Karyawan</a>
    </div>

    {{-- Profil --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="grid h-14 w-14 place-items-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700">{{ $initial }}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-lg font-bold text-slate-900">{{ $employee->name }}</p>
                    @if ($employee->is_active)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Aktif</span>
                    @else<span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600">Nonaktif</span>@endif
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">{{ $employee->isPiecework() ? 'Borongan/Proyek' : 'Per Jam' }}</span>
                </div>
                <p class="mt-0.5 text-sm text-slate-500">{{ $employee->position ?: 'Tanpa jabatan' }} @if($employee->code)· {{ $employee->code }}@endif @if($employee->phone)· {{ $employee->phone }}@endif</p>
            </div>
        </div>

        {{-- Ringkasan angka --}}
        <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-4">
            <div><p class="text-xs text-slate-400">{{ $employee->isPiecework() ? 'Tipe Gaji' : 'Tarif / Jam' }}</p><p class="mt-0.5 font-bold text-indigo-600">{{ $employee->isPiecework() ? 'Borongan' : $rp($employee->hourly_rate) }}</p></div>
            <div><p class="text-xs text-slate-400">Total Bonus</p><p class="mt-0.5 font-bold text-emerald-600">{{ $rp($totalBonus) }}</p></div>
            <div><p class="text-xs text-slate-400">Sisa Kasbon</p><p class="mt-0.5 font-bold text-rose-600">{{ $rp($kasbonPending) }}</p></div>
            <div><p class="text-xs text-slate-400">Potongan Tetap</p><p class="mt-0.5 font-bold text-slate-700">{{ $rp($potonganTetap) }}</p></div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="mt-6 flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm">
        @php
            $tabs = ['bonus' => 'Bonus'];
            if ($employee->isPiecework()) { $tabs['borongan'] = 'Borongan'; }
            $tabs += ['kasbon' => 'Kasbon', 'potongan' => 'Potongan', 'tabungan' => 'Tabungan'];
            if (! $employee->isPiecework()) { $tabs['absensi'] = 'Absensi'; }
        @endphp
        @foreach ($tabs as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    class="flex-1 rounded-lg px-3 py-2 font-medium transition">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Panel: Bonus --}}
    <div x-show="tab === 'bonus'" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Riwayat Bonus</h3>
            <button type="button" @click="addBonus = !addBonus" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">+ Tambah Bonus</button>
        </div>
        <div x-show="addBonus" x-collapse style="display:none">
            <form method="POST" action="{{ route('v2.employees.bonus.store', $employee) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
                @csrf
                <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                <div><label class="{{ $lbl }}">Jumlah</label><input type="number" name="amount" step="0.01" min="0" class="{{ $input }} text-right" required></div>
                <div><label class="{{ $lbl }}">Keterangan</label><input type="text" name="description" placeholder="Mis: bonus target" class="{{ $input }}"></div>
                <div class="flex items-end"><button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button></div>
            </form>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($employee->bonuses as $b)
                <div class="flex items-center justify-between py-2.5 text-sm">
                    <div><p class="font-medium text-slate-700">{{ $b->description ?: ($b->type ?: 'Bonus') }}</p><p class="text-xs text-slate-400">{{ $b->date->format('d M Y') }}</p></div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-emerald-600">{{ $rp($b->amount) }}</span>
                        <form method="POST" action="{{ route('v2.employees.bonus.destroy', [$employee, $b->id]) }}" onsubmit="return confirm('Hapus bonus?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-400">Belum ada bonus.</p>
            @endforelse
        </div>
    </div>

    {{-- Panel: Borongan / Proyek --}}
    @if ($employee->isPiecework())
        <div x-show="tab === 'borongan'" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5" style="display:none">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Item Pekerjaan (Borongan / Proyek)</h3>
                <button type="button" @click="addWork = !addWork" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">+ Tambah Item</button>
            </div>
            <div x-show="addWork" x-collapse style="display:none">
                <form method="POST" action="{{ route('v2.employees.workitem.store', $employee) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-5">
                    @csrf
                    <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                    <div class="sm:col-span-2"><label class="{{ $lbl }}">Deskripsi</label><input type="text" name="description" placeholder="Mis: Lemari" class="{{ $input }}" required></div>
                    <div><label class="{{ $lbl }}">Qty</label><input type="number" step="0.01" min="0" name="qty" value="1" class="{{ $input }} text-right" required></div>
                    <div><label class="{{ $lbl }}">Upah Satuan</label><input type="number" step="0.01" min="0" name="unit_price" class="{{ $input }} text-right" required></div>
                    <div class="sm:col-span-5"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button></div>
                </form>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($employee->workItems as $w)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <p class="font-medium text-slate-700">{{ $w->description }}</p>
                            <p class="text-xs text-slate-400">{{ $w->date->format('d M Y') }} · {{ rtrim(rtrim(number_format($w->qty, 2), '0'), '.') }} × {{ $rp($w->unit_price) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-slate-800">{{ $rp($w->amount) }}</span>
                            <form method="POST" action="{{ route('v2.employees.workitem.destroy', [$employee, $w->id]) }}" onsubmit="return confirm('Hapus item?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Belum ada item pekerjaan.</p>
                @endforelse
            </div>
            <p class="mt-3 text-xs text-slate-400">Total item dengan tanggal di dalam periode payroll menjadi gaji kotor karyawan borongan/proyek ini.</p>
        </div>
    @endif

    {{-- Panel: Kasbon --}}
    <div x-show="tab === 'kasbon'" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5" style="display:none">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Riwayat Kasbon</h3>
            <button type="button" @click="addLoan = !addLoan" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700">+ Tambah Kasbon</button>
        </div>
        <div x-show="addLoan" x-collapse style="display:none">
            <form method="POST" action="{{ route('v2.employees.loan.store', $employee) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
                @csrf
                <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                <div><label class="{{ $lbl }}">Jumlah</label><input type="number" name="amount" step="0.01" min="0" class="{{ $input }} text-right" required></div>
                <div><label class="{{ $lbl }}">Keterangan</label><input type="text" name="description" placeholder="Mis: pinjaman" class="{{ $input }}"></div>
                <input type="hidden" name="status" value="pending">
                <div class="flex items-end"><button class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Simpan</button></div>
            </form>
        </div>
        <div class="space-y-3">
            @forelse ($employee->loans as $l)
                <div class="rounded-xl border border-slate-200 p-4" x-data="{ pay: false }">
                    <div class="flex items-start justify-between gap-3 text-sm">
                        <div>
                            <p class="font-medium text-slate-700">{{ $l->description ?: 'Kasbon' }}</p>
                            <p class="text-xs text-slate-400">{{ $l->date->format('d M Y') }} · Pinjam {{ $rp($l->amount) }} · Dibayar {{ $rp($l->repaidTotal()) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-[11px] text-slate-400">Sisa</p>
                                <p class="font-bold {{ (float) $l->outstanding > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $rp($l->outstanding) }}</p>
                            </div>
                            <span class="rounded-full {{ (float) $l->outstanding > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2 py-0.5 text-[11px] font-medium">{{ (float) $l->outstanding > 0 ? 'Belum Lunas' : 'Lunas' }}</span>
                            @if ((float) $l->outstanding > 0)
                                <button type="button" @click="pay = !pay" class="rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Cicil</button>
                            @endif
                            <form method="POST" action="{{ route('v2.employees.loan.destroy', [$employee, $l->id]) }}" onsubmit="return confirm('Hapus kasbon ini beserta cicilannya?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                        </div>
                    </div>

                    {{-- Form cicilan --}}
                    <div x-show="pay" x-collapse style="display:none">
                        <form method="POST" action="{{ route('v2.employees.loan.repayment.store', [$employee, $l->id]) }}" class="mt-3 grid grid-cols-1 gap-3 rounded-lg bg-slate-50 p-3 sm:grid-cols-4">
                            @csrf
                            <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                            <div><label class="{{ $lbl }}">Jumlah Cicil</label><input type="number" step="0.01" min="0.01" max="{{ (float) $l->outstanding }}" name="amount" value="{{ (float) $l->outstanding }}" class="{{ $input }} text-right" required></div>
                            <div><label class="{{ $lbl }}">Catatan</label><input type="text" name="note" class="{{ $input }}"></div>
                            <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">Catat Cicilan</button></div>
                        </form>
                    </div>

                    {{-- Riwayat cicilan --}}
                    @if ($l->repayments->isNotEmpty())
                        <div class="mt-3 border-t border-slate-100 pt-2">
                            @foreach ($l->repayments as $i => $r)
                                <div class="flex items-center justify-between py-1 text-xs">
                                    <span class="text-slate-500">Cicilan ke-{{ $l->repayments->count() - $i }} · {{ $r->date->format('d M Y') }}@if($r->note) · {{ $r->note }}@endif</span>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-slate-700">{{ $rp($r->amount) }}</span>
                                        <form method="POST" action="{{ route('v2.employees.loan.repayment.destroy', [$employee, $l->id, $r->id]) }}" onsubmit="return confirm('Hapus cicilan?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-400">Belum ada kasbon.</p>
            @endforelse
        </div>
        <p class="mt-3 text-xs text-slate-400">Catat <strong>cicilan</strong> tiap periode. Cicilan dengan tanggal di dalam periode payroll otomatis muncul sebagai potongan Bon saat generate.</p>
    </div>

    {{-- Panel: Potongan (ad-hoc) --}}
    <div x-show="tab === 'potongan'" class="mt-4 rounded-2xl border border-slate-200 bg-white p-5" style="display:none">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Riwayat Potongan</h3>
            <button type="button" @click="addDeduction = !addDeduction" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700">+ Tambah Potongan</button>
        </div>
        <div x-show="addDeduction" x-collapse style="display:none">
            <form method="POST" action="{{ route('v2.employees.deduction.store', $employee) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-4">
                @csrf
                <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                <div><label class="{{ $lbl }}">Jumlah</label><input type="number" name="amount" step="0.01" min="0" class="{{ $input }} text-right" required></div>
                <div><label class="{{ $lbl }}">Keterangan</label><input type="text" name="description" placeholder="Mis: potongan denda" class="{{ $input }}"></div>
                <div class="flex items-end"><button class="w-full rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">Simpan</button></div>
            </form>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($employee->deductions as $d)
                <div class="flex items-center justify-between py-2.5 text-sm">
                    <div><p class="font-medium text-slate-700">{{ $d->description ?: 'Potongan' }}</p><p class="text-xs text-slate-400">{{ $d->date->format('d M Y') }}</p></div>
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-rose-600">{{ $rp($d->amount) }}</span>
                        <form method="POST" action="{{ route('v2.employees.deduction.destroy', [$employee, $d->id]) }}" onsubmit="return confirm('Hapus potongan?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-400">Belum ada potongan.</p>
            @endforelse
        </div>
        <p class="mt-3 text-xs text-slate-400">Potongan dengan tanggal di dalam periode payroll akan otomatis dipotong saat generate.</p>
    </div>

    {{-- Panel: Tabungan --}}
    <div x-show="tab === 'tabungan'" class="mt-4 space-y-4" style="display:none">
        @php $savingBalance = $employee->savingBalance(); $depositCount = $employee->savingEntries->where('type', 'deposit')->count(); @endphp

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {{-- Saldo --}}
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <p class="text-xs font-medium text-indigo-500">Saldo Tabungan</p>
                <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $rp($savingBalance) }}</p>
                <p class="mt-0.5 text-xs text-indigo-400">{{ $depositCount }}x setor</p>
            </div>
            {{-- Potongan tetap per periode --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
                <h3 class="mb-1 text-sm font-semibold text-slate-900">Setoran Otomatis per Periode</h3>
                <p class="mb-3 text-xs text-slate-400">Nominal ini dipotong tiap payroll dan otomatis masuk ke saldo tabungan saat payroll dibayar.</p>
                <form method="POST" action="{{ route('v2.employees.saving.save', $employee) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1"><label class="{{ $lbl }}">Nominal</label><input type="number" step="0.01" min="0" name="amount" value="{{ $saving?->amount ?? 0 }}" class="{{ $input }} text-right"></div>
                    <label class="mb-1.5 inline-flex items-center gap-2 text-sm"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($saving?->active ?? true) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Aktif</label>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
                </form>
            </div>
        </div>

        {{-- Buku tabungan --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Buku Tabungan</h3>
                <button type="button" @click="addSaving = !addSaving" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">+ Setor / Tarik Manual</button>
            </div>
            <div x-show="addSaving" x-collapse style="display:none">
                <form method="POST" action="{{ route('v2.employees.saving.entry.store', $employee) }}" class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-5">
                    @csrf
                    <div><label class="{{ $lbl }}">Tanggal</label><input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $input }}"></div>
                    <div><label class="{{ $lbl }}">Jenis</label><select name="type" class="{{ $input }}"><option value="deposit">Setor</option><option value="withdraw">Tarik</option></select></div>
                    <div><label class="{{ $lbl }}">Jumlah</label><input type="number" step="0.01" min="0" name="amount" class="{{ $input }} text-right" required></div>
                    <div class="sm:col-span-1"><label class="{{ $lbl }}">Catatan</label><input type="text" name="note" class="{{ $input }}"></div>
                    <div class="flex items-end"><button class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button></div>
                </form>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($employee->savingEntries as $en)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <p class="font-medium text-slate-700">
                                @if ($en->type === 'withdraw')<span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600">Tarik</span>
                                @else<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Setor</span>@endif
                                {{ $en->note ?: '—' }}
                            </p>
                            <p class="text-xs text-slate-400">{{ $en->date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold {{ $en->type === 'withdraw' ? 'text-rose-600' : 'text-emerald-600' }}">{{ $en->type === 'withdraw' ? '−' : '+' }}{{ $rp($en->amount) }}</span>
                            <form method="POST" action="{{ route('v2.employees.saving.entry.destroy', [$employee, $en->id]) }}" onsubmit="return confirm('Hapus transaksi tabungan?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-slate-400">Belum ada transaksi tabungan.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-1 text-sm font-semibold text-slate-900">Arisan</h3>
            <p class="mb-3 text-xs text-slate-400">Iuran arisan dari kelompok aktif otomatis dipotong di payroll (sesuai jadwal periode arisan).</p>
            @php $activeMemberships = $employee->arisanMemberships->where('status', 'active'); @endphp
            @forelse ($activeMemberships as $m)
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                    <span class="text-slate-700">{{ $m->group?->name ?? 'Kelompok' }}</span>
                    <span class="font-medium text-slate-800">{{ $rp($m->group?->contribution_amount) }}/periode</span>
                </div>
            @empty
                <p class="py-2 text-xs text-slate-400">Belum tergabung di kelompok arisan aktif.</p>
            @endforelse
            <a href="{{ route('v2.arisan.index') }}" class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Buka Modul Arisan →
            </a>
        </div>
    </div>

    {{-- Panel: Absensi --}}
    <div x-show="tab === 'absensi'" class="mt-4 rounded-2xl border border-slate-200 bg-white" style="display:none">
        <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-sm font-semibold text-slate-900">Riwayat Absensi Terbaru</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                    <th class="px-5 py-3 font-medium">Tanggal</th>
                    <th class="px-5 py-3 font-medium">Shift</th>
                    <th class="px-5 py-3 font-medium">Check In/Out</th>
                    <th class="px-5 py-3 text-right font-medium">Jam Dibayar</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                </tr></thead>
                <tbody>
                    @forelse ($employee->attendances as $a)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-700">{{ $a->work_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $a->shift?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $a->check_in?->format('H:i') ?? '—' }} → {{ $a->check_out?->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ number_format($a->payableHours(), 2) }}</td>
                            <td class="px-5 py-3"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusBadge[$a->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$a->status] ?? ucfirst($a->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">Belum ada riwayat absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
