@extends('v2.layouts.app')
@section('title', 'Kabupaten/Kota')
@section('heading', 'Kabupaten/Kota')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <select name="province_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" style="min-width:220px">
            <option value="">Semua provinsi</option>
            @foreach ($provinces as $p)
                <option value="{{ $p->id }}" @selected($provinceId === $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kab/kota…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Kabupaten/Kota</th>
                        <th class="px-4 py-3 font-medium">Provinsi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($regencies as $r)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-slate-500">{{ $r->code }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $r->name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $r->province?->name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-slate-400">Belum ada data. Jalankan <code>php artisan db:seed --class=WilayahSeeder</code>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $regencies->links() }}</div>
@endsection
