@extends('v2.layouts.app')
@section('title', $group->name)
@section('heading', 'Arisan: '.$group->name)

@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $statusBadge = ['draft' => 'bg-slate-100 text-slate-600', 'active' => 'bg-emerald-50 text-emerald-700', 'completed' => 'bg-indigo-50 text-indigo-700', 'cancelled' => 'bg-rose-50 text-rose-600'];
    $statusLabel = ['draft' => 'Draft', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Batal'];
    $memberStatus = ['active' => 'bg-emerald-50 text-emerald-700', 'completed' => 'bg-indigo-50 text-indigo-700', 'withdrawn' => 'bg-rose-50 text-rose-600'];
    $memberStatusLabel = ['active' => 'Aktif', 'completed' => 'Selesai', 'withdrawn' => 'Keluar'];
    $methodLabel = ['random' => 'Acak', 'manual' => 'Manual', 'queue' => 'Urutan Antrian'];
    $activeMembers = $group->members->where('status', 'active');
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('v2.arisan.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Daftar Kelompok</a>
        <div class="flex gap-2">
            <a href="{{ route('v2.arisan.edit', $group->id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit Kelompok</a>
        </div>
    </div>

    {{-- Ringkasan kelompok --}}
    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Iuran / Periode</p>
            <p class="mt-1 text-lg font-bold text-slate-800">{{ $rp($group->contribution_amount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Peserta Aktif</p>
            <p class="mt-1 text-lg font-bold text-slate-800">{{ $activeMembers->count() }} orang</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Dana Terkumpul</p>
            <p class="mt-1 text-lg font-bold text-slate-800">{{ $rp($group->totalCollected()) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-xs font-medium text-slate-500">Status / Metode</p>
            <p class="mt-1"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusBadge[$group->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $statusLabel[$group->status] ?? ucfirst($group->status) }}</span></p>
            <p class="mt-1 text-xs text-slate-500">Undian: {{ $methodLabel[$group->draw_method] ?? $group->draw_method }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Anggota --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Anggota ({{ $group->members->count() }})</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <tbody>
                            @forelse ($group->members->sortBy('sequence_number') as $m)
                                <tr class="border-b border-slate-100">
                                    <td class="px-4 py-2.5 text-slate-400">{{ $m->sequence_number }}</td>
                                    <td class="px-2 py-2.5">
                                        <span class="font-medium text-slate-700">{{ $m->employee?->name ?? '—' }}</span>
                                        @if ($m->has_won)<span class="ml-1 text-[10px] font-bold text-amber-600">★ menang</span>@endif
                                    </td>
                                    <td class="px-2 py-2.5 text-center"><span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ $memberStatus[$m->status] ?? 'bg-slate-100' }}">{{ $memberStatusLabel[$m->status] ?? $m->status }}</span></td>
                                    <td class="px-2 py-2.5 text-right">
                                        @if ($m->status === 'active')
                                            <form method="POST" action="{{ route('v2.arisan.members.remove', [$group->id, $m->id]) }}" onsubmit="return confirm('Keluarkan anggota ini dari arisan?')">
                                                @csrf @method('DELETE')
                                                <button class="text-xs text-rose-600 hover:underline">Keluar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-8 text-center text-slate-400">Belum ada anggota.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Tambah anggota --}}
                @if ($availableEmployees->isNotEmpty() && $group->status !== 'completed' && $group->status !== 'cancelled')
                    <div class="border-t border-slate-200 p-4">
                        <form method="POST" action="{{ route('v2.arisan.members.add', $group->id) }}" class="flex gap-2">
                            @csrf
                            <select name="employee_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="">Pilih karyawan…</option>
                                @foreach ($availableEmployees as $e)
                                    <option value="{{ $e->id }}">{{ $e->name }}@if($e->code) ({{ $e->code }})@endif</option>
                                @endforeach
                            </select>
                            <button class="shrink-0 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Periode --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Periode Arisan</h3>
                    @if (! $currentPeriod && $activeMembers->count() > 0 && $group->status !== 'completed' && $group->status !== 'cancelled')
                        <form method="POST" action="{{ route('v2.arisan.periods.open', $group->id) }}">
                            @csrf
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-700">+ Buka Periode Baru</button>
                        </form>
                    @endif
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse ($group->periods as $p)
                        @php
                            $paidCount = $p->contributions->where('status', 'paid')->count();
                            $totalCount = $p->contributions->count();
                            $allPaid = $totalCount > 0 && $paidCount === $totalCount;
                        @endphp
                        <div class="p-5" x-data="{ open: {{ $p->status === 'pending' ? 'true' : 'false' }} }">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <button type="button" @click="open = !open" class="flex items-center gap-2 text-left">
                                    <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                                    <span class="font-semibold text-slate-800">Periode #{{ $p->period_no }}</span>
                                    <span class="text-xs text-slate-400">{{ optional($p->period_date)->format('d/m/Y') }}</span>
                                </button>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">Terkumpul {{ $paidCount }}/{{ $totalCount }}</span>
                                    @if ($p->status === 'completed')
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-700">Selesai</span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Berjalan</span>
                                    @endif
                                </div>
                            </div>

                            <div x-show="open" x-collapse class="mt-3">
                                @if ($p->status === 'completed' && $p->winner)
                                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
                                        ★ Pemenang: <strong>{{ $p->winner->name }}</strong> — dana dicairkan {{ $rp($p->payout?->amount ?? $p->total_collected) }}
                                    </div>
                                @endif

                                <table class="w-full text-sm">
                                    <thead><tr class="border-b border-slate-200 text-left text-xs text-slate-500">
                                        <th class="py-2 font-medium">Karyawan</th>
                                        <th class="py-2 text-right font-medium">Iuran</th>
                                        <th class="py-2 text-center font-medium">Status</th>
                                    </tr></thead>
                                    <tbody>
                                        @foreach ($p->contributions as $c)
                                            <tr class="border-b border-slate-50">
                                                <td class="py-2 text-slate-700">{{ $c->employee?->name ?? '—' }}</td>
                                                <td class="py-2 text-right text-slate-600">{{ $rp($c->amount) }}</td>
                                                <td class="py-2 text-center">
                                                    @if ($c->status === 'paid')
                                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Lunas</span>
                                                    @elseif ($c->status === 'cancelled')
                                                        <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-600">Batal</span>
                                                    @else
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Pending</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot><tr class="font-semibold text-slate-800">
                                        <td class="py-2">Total Terkumpul</td>
                                        <td class="py-2 text-right">{{ $rp($p->total_collected) }}</td>
                                        <td></td>
                                    </tr></tfoot>
                                </table>

                                @if ($p->status === 'pending')
                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        @unless ($allPaid)
                                            <form method="POST" action="{{ route('v2.arisan.periods.collect', [$group->id, $p->id]) }}">
                                                @csrf
                                                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700">Tandai Iuran Terkumpul</button>
                                            </form>
                                        @endunless

                                        @if ($allPaid)
                                            <form method="POST" action="{{ route('v2.arisan.periods.draw', [$group->id, $p->id]) }}" class="flex flex-wrap items-center gap-2"
                                                  x-data="{ method: '{{ $group->draw_method }}' }">
                                                @csrf
                                                <select name="method" x-model="method" class="rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                    <option value="random">Acak</option>
                                                    <option value="queue">Urutan Antrian</option>
                                                    <option value="manual">Manual</option>
                                                </select>
                                                <select name="employee_id" x-show="method === 'manual'" x-cloak class="rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                                    <option value="">Pilih pemenang…</option>
                                                    @foreach ($activeMembers->where('has_won', false) as $am)
                                                        <option value="{{ $am->employee_id }}">{{ $am->employee?->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-700">Undi Pemenang & Cairkan</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Pemenang bisa diundi setelah seluruh iuran terkumpul.</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-10 text-center text-slate-400">Belum ada periode. Buka periode pertama untuk memulai.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
