<form method="GET" class="mb-4 flex items-center gap-2">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor…"
           class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
</form>
