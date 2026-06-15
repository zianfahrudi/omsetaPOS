@extends('v2.layouts.app')
@section('title', 'Kontak')
@section('heading', 'Kontak')

@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama / kode / telepon…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <option value="">Semua tipe</option>
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Filter</button>
        </form>
        <a href="{{ route('v2.contacts.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah Kontak</a>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Tipe</th>
                        <th class="px-4 py-3 font-medium">Telepon</th>
                        <th class="px-4 py-3 text-right font-medium">Piutang</th>
                        <th class="px-4 py-3 text-right font-medium">Hutang</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $contact->name }}</p>
                                @if ($contact->code)<p class="text-xs text-slate-400">{{ $contact->code }}</p>@endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $typeLabels[$contact->type] ?? $contact->type }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $contact->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($contact->receivable_balance) }}</td>
                            <td class="px-4 py-3 text-right">{{ $rp($contact->payable_balance) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('v2.contacts.edit', $contact) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route('v2.contacts.destroy', $contact) }}" onsubmit="return confirm('Hapus kontak ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada kontak.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $contacts->links() }}</div>
@endsection
