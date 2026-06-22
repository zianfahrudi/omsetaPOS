@extends('v2.layouts.app')
@section('title', ($record->exists ? 'Edit Akun' : 'Tambah Akun'))
@section('heading', ($record->exists ? 'Edit Akun' : 'Tambah Akun'))

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $record->exists ? route('v2.accounting.accounts.update', $record->id) : route('v2.accounting.accounts.store');
    $curParent = old('parent_id', $record->parent_id);
    $curType = old('type', $record->type);
    $isNew = ! $record->exists;
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="max-w-xl">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Induk Akun (opsional)</label>
                    <select name="parent_id" class="{{ $input }}">
                        <option value="">— Akun utama (tanpa induk) —</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}" data-type="{{ $p->type }}" @selected((int) $curParent === $p->id)>{{ $p->code }} · {{ $p->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Pilih induk untuk membuat sub-akun (mis. induk "Bank" → sub "Bank BCA"). Tipe mengikuti induk.</p>
                </div>
                <div>
                    <label class="{{ $lbl }}">Kode</label>
                    <input type="text" name="code" id="acc-code" value="{{ old('code', $record->code) }}" class="{{ $input }}" required autocomplete="off">
                    <p class="mt-1 text-xs text-slate-400">Terisi otomatis. Bisa diubah manual bila perlu.</p>
                </div>
                <div>
                    <label class="{{ $lbl }}">Tipe</label>
                    <select name="type" class="{{ $input }}" required>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected($curType === $t)>{{ $typeLabels[$t] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Akun</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Subtipe (opsional)</label>
                    <input type="text" name="subtype" value="{{ old('subtype', $record->subtype) }}" class="{{ $input }}" placeholder="mis. bank, cash">
                    <p class="mt-1 text-xs text-slate-400">Isi <code>bank</code>/<code>cash</code> agar akun terdeteksi sebagai kas/bank saat transaksi.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Keterangan (opsional)</label>
                    <input type="text" name="description" value="{{ old('description', $record->description) }}" class="{{ $input }}">
                </div>
                <div class="sm:col-span-2 flex flex-col gap-2">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_postable" value="0">
                        <input type="checkbox" name="is_postable" value="1" @checked(old('is_postable', $record->is_postable ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Bisa diposting (akun transaksi, bukan sekadar pengelompok)
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Aktif
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route('v2.accounting.accounts') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>

    <script>
        (function () {
            var parentSel = document.querySelector('select[name=parent_id]');
            var typeSel = document.querySelector('select[name=type]');
            var codeInp = document.getElementById('acc-code');
            var isNew = {{ $isNew ? 'true' : 'false' }};
            var suggestByParent = @json($suggestByParent ?? []);
            var suggestByType = @json($suggestByType ?? []);

            // Mode auto aktif untuk akun baru selama user belum mengetik kode manual.
            var auto = isNew;
            codeInp.addEventListener('input', function () { auto = false; });

            function currentSuggestion() {
                if (parentSel.value && suggestByParent[parentSel.value]) return suggestByParent[parentSel.value];
                if (typeSel.value && suggestByType[typeSel.value]) return suggestByType[typeSel.value];
                return '';
            }

            function syncType() {
                var opt = parentSel.options[parentSel.selectedIndex];
                var t = opt ? opt.getAttribute('data-type') : null;
                if (t) { typeSel.value = t; typeSel.setAttribute('disabled', 'disabled'); }
                else { typeSel.removeAttribute('disabled'); }
            }

            function refresh() {
                syncType();
                if (auto) {
                    var s = currentSuggestion();
                    if (s) codeInp.value = s;
                }
            }

            parentSel.addEventListener('change', refresh);
            typeSel.addEventListener('change', function () { if (auto) { var s = currentSuggestion(); if (s) codeInp.value = s; } });

            // Sinkron tipe saat load (tanpa menimpa kode yang sudah dari server).
            syncType();

            // Pastikan tipe ikut terkirim walau disabled.
            document.querySelector('form').addEventListener('submit', function () {
                typeSel.removeAttribute('disabled');
            });
        })();
    </script>
@endsection
