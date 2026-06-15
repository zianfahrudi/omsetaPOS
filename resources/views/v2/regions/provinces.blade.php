@extends('v2.layouts.app')
@section('title', 'Provinsi')
@section('heading', 'Provinsi')

@section('content')
    <form method="GET" class="mb-4 flex items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari provinsi…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        <a href="{{ route('v2.regions.regencies') }}" class="ml-auto text-sm font-medium text-indigo-600 hover:underline">Lihat Kabupaten/Kota →</a>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Kode</th>
                        <th class="px-4 py-3 font-medium">Provinsi</th>
                        <th class="px-4 py-3 text-right font-medium">Jml Kab/Kota</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($provinces as $p)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-slate-500">{{ $p->code }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $p->name }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $p->regencies_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-slate-400">Belum ada data wilayah. Jalankan <code>php artisan db:seed --class=WilayahSeeder</code>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $provinces->links() }}</div>
@endsection
