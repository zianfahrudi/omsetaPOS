@props([
    'name' => 'month',
    'value' => null,          // format Y-m
    'label' => 'Periode Bulan',
])

@php
    $val = $value ?: now()->format('Y-m');
    [$initYear, $initMonth] = array_map('intval', explode('-', $val));
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $monthsFull = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp

<div
    x-data="{
        open: false,
        year: {{ $initYear }},
        month: {{ $initMonth }},
        months: @js($months),
        monthsFull: @js($monthsFull),
        get display() { return this.monthsFull[this.month - 1] + ' ' + this.year; },
        get value() { return this.year + '-' + String(this.month).padStart(2, '0'); },
        pick(m) { this.month = m; this.open = false; this.$nextTick(() => this.$root.closest('form')?.requestSubmit()); },
    }"
    class="relative"
    @keydown.escape="open = false"
    @click.outside="open = false"
>
    <label class="mb-1 block text-xs font-medium text-slate-500">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" :value="value">
    <button type="button" @click="open = !open"
            class="flex w-52 items-center justify-between gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <span class="flex items-center gap-2">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
            <span x-text="display" class="font-medium"></span>
        </span>
        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
    </button>

    <div x-show="open" x-cloak x-transition.origin.top
         class="absolute z-20 mt-1 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
        <div class="mb-2 flex items-center justify-between">
            <button type="button" @click="year--" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>
            <span class="text-sm font-semibold text-slate-800" x-text="year"></span>
            <button type="button" @click="year++" class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>
        <div class="grid grid-cols-3 gap-1.5">
            <template x-for="(m, i) in months" :key="i">
                <button type="button" @click="pick(i + 1)"
                        class="rounded-lg px-2 py-2 text-sm transition"
                        :class="(month === i + 1) ? 'bg-indigo-600 font-medium text-white' : 'text-slate-600 hover:bg-slate-100'"
                        x-text="m"></button>
            </template>
        </div>
    </div>
</div>
