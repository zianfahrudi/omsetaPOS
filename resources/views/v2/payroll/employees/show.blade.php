@extends('v2.layouts.app')
@section('title', 'Karyawan · '.$employee->name)
@section('heading', 'Detail Karyawan')

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-xs font-medium text-slate-500';
    $arisan = $employee->arisan->first();
    $saving = $employee->savings->first();
    $initial = mb_strtoupper(mb_substr(trim($employee->name), 0, 1));
    $totalBonus = $employee->bonuses->sum('amount');
    $kasbonPending = $employee->loans->where('status', 'pending')->sum('amount');
    $potonganTetap = (($arisan?->active ? $arisan->amount : 0) + ($saving?->active ? $saving->amount : 0));
    $statusBadge = [
        'present' => 'bg-emerald-50 text-emerald-700', 'late' => 'bg-amber-50 text-amber-700',
        'absent' => 'bg-rose-50 text-rose-600', 'leave' => 'bg-sky-50 text-sky-700',
        'sick' => 'bg-orange-50 text-orange-700', 'holiday' => 'bg-indigo-50 text-indigo-700',
    ];
    $statusLabel = ['present'=>'Hadir','late'=>'Telat','absent'=>'Tidak Hadir','leave'=>'Izin','sick'=>'Sakit','holiday'=>'Libur'];
    $loanLabel = ['pending'=>'Belum Lunas','paid'=>'Lunas','deducted'=>'Dipotong'];
@endphp

@section('content')
<div x-data="{ tab: 'bonus', addBonus: false, addLoan: false }">
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
                </div>
                <p class="mt-0.5 text-sm text-slate-500">{{ $employee->position ?: 'Tanpa jabatan' }} @if($employee->code)· {{ $employee->code }}@endif @if($employee->phone)· {{ $employee->phone }}@endif</p>
            </div>
        </div>

        {{-- Ringkasan angka --}}
        <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-4">
            <div><p class="text-xs text-slate-400">Tarif / Jam</p><p class="mt-0.5 font-bold text-indigo-600">{{ $rp($employee->hourly_rate) }}</p></div>
            <div><p class="text-xs text-slate-400">Total Bonus</p><p class="mt-0.5 font-bold text-emerald-600">{{ $rp($totalBonus) }}</p></div>
            <div><p class="text-xs text-slate-400">Kasbon Belum Lunas</p><p class="mt-0.5 font-bold text-rose-600">{{ $rp($kasbonPending) }}</p></div>
            <div><p class="text-xs text-slate-400">Potongan Tetap</p><p class="mt-0.5 font-bold text-slate-700">{{ $rp($potonganTetap) }}</p></div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="mt-6 flex gap-1 rounded-xl border border-slate-200 bg-white p-1 text-sm">
        @foreach (['bonus' => 'Bonus', 'kasbon' => 'Kasbon', 'potongan' => 'Potongan Tetap', 'absensi' => 'Absensi'] as $key => $label)
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
        <div class="divide-y divide-slate-100">
            @forelse ($employee->loans as $l)
                <div class="flex items-center justify-between py-2.5 text-sm">
                    <div><p class="font-medium text-slate-700">{{ $l->description ?: 'Kasbon' }}</p><p class="text-xs text-slate-400">{{ $l->date->format('d M Y') }}</p></div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full {{ $l->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2 py-0.5 text-[11px] font-medium">{{ $loanLabel[$l->status] ?? ucfirst($l->status) }}</span>
                        <span class="font-bold text-rose-600">{{ $rp($l->amount) }}</span>
                        <form method="POST" action="{{ route('v2.employees.loan.destroy', [$employee, $l->id]) }}" onsubmit="return confirm('Hapus kasbon?')">@csrf @method('DELETE')<button class="text-slate-300 hover:text-rose-600">✕</button></form>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-400">Belum ada kasbon.</p>
            @endforelse
        </div>
        <p class="mt-3 text-xs text-slate-400">Kasbon berstatus "Belum Lunas" akan otomatis dipotong saat generate payroll.</p>
    </div>

    {{-- Panel: Potongan Tetap --}}
    <div x-show="tab === 'potongan'" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2" style="display:none">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-1 text-sm font-semibold text-slate-900">Arisan</h3>
            <p class="mb-3 text-xs text-slate-400">Potongan tetap setiap periode payroll.</p>
            <form method="POST" action="{{ route('v2.employees.arisan.save', $employee) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1"><label class="{{ $lbl }}">Nominal</label><input type="number" step="0.01" min="0" name="amount" value="{{ $arisan?->amount ?? 0 }}" class="{{ $input }} text-right"></div>
                <label class="mb-1.5 inline-flex items-center gap-2 text-sm"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($arisan?->active ?? true) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Aktif</label>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <h3 class="mb-1 text-sm font-semibold text-slate-900">Tabungan</h3>
            <p class="mb-3 text-xs text-slate-400">Potongan sukarela yang disimpan perusahaan.</p>
            <form method="POST" action="{{ route('v2.employees.saving.save', $employee) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1"><label class="{{ $lbl }}">Nominal</label><input type="number" step="0.01" min="0" name="amount" value="{{ $saving?->amount ?? 0 }}" class="{{ $input }} text-right"></div>
                <label class="mb-1.5 inline-flex items-center gap-2 text-sm"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked($saving?->active ?? true) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Aktif</label>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            </form>
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
