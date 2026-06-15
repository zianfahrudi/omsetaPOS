@props([
    'action',
    'backUrl',
    'number',
    'partnerName' => '—',
    'partnerLabel' => 'Kontak',
    'grandTotal' => 0,
    'paid' => 0,
    'outstanding' => 0,
])

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

<form method="POST" action="{{ $action }}" class="max-w-lg">
    @csrf
    <div class="rounded-2xl border border-slate-200 bg-white p-6">
        <dl class="mb-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm">
            <div><dt class="text-slate-400">Faktur</dt><dd class="font-medium text-slate-800">{{ $number }}</dd></div>
            <div><dt class="text-slate-400">{{ $partnerLabel }}</dt><dd class="font-medium text-slate-800">{{ $partnerName }}</dd></div>
            <div><dt class="text-slate-400">Total</dt><dd>{{ $rp($grandTotal) }}</dd></div>
            <div><dt class="text-slate-400">Sisa</dt><dd class="font-semibold text-rose-600">{{ $rp($outstanding) }}</dd></div>
        </dl>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="{{ $lbl }}">Nominal</label>
                <input type="number" step="0.01" min="1" max="{{ $outstanding }}" name="amount" value="{{ old('amount', $outstanding) }}" class="{{ $input }}" required>
            </div>
            <div>
                <label class="{{ $lbl }}">Metode</label>
                <select name="method" class="{{ $input }}" required>
                    <option value="cash" @selected(old('method') === 'cash')>Tunai (Kas)</option>
                    <option value="bank" @selected(old('method') === 'bank')>Transfer (Bank)</option>
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Tanggal</label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="{{ $input }}" required>
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $lbl }}">Catatan</label>
                <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>
    <div class="mt-4 flex items-center gap-3">
        <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Catat Pembayaran</button>
        <a href="{{ $backUrl }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
    </div>
</form>
