<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Dari</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    </div>
    <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Sampai</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    </div>
    <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Tampilkan</button>
</form>
