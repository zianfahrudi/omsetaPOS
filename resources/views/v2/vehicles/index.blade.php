@extends('v2.layouts.app')
@section('title', 'Kendaraan')
@section('heading', 'Kendaraan Pelanggan')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari plat / nama / pemilik…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <a href="{{ route('v2.vehicles.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Kendaraan</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Plat Nomor</th>
                        <th class="px-4 py-3 font-medium">Kendaraan</th>
                        <th class="px-4 py-3 font-medium">Pemilik</th>
                        <th class="px-4 py-3 font-medium">Toko</th>
                        <th class="px-4 py-3 text-right font-medium">KM</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $vehicle->plate_number }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $vehicle->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $vehicle->customer?->name ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $vehicle->store?->name }}</td>
                            <td class="px-4 py-3 text-right text-slate-500">{{ $vehicle->mileage ? number_format($vehicle->mileage, 0, ',', '.') : '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.vehicles.edit', $vehicle) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route('v2.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Hapus kendaraan ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada kendaraan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $vehicles->links() }}</div>
@endsection
