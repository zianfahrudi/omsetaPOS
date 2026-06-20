@extends('v2.layouts.app')
@section('title', ($record->exists ? 'Edit ' : 'Tambah ').$label)
@section('heading', ($record->exists ? 'Edit ' : 'Tambah ').$label)

@php
    $input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500';
    $lbl = 'mb-1 block text-sm font-medium text-slate-700';
    $action = $record->exists ? route($routeBase.'.update', $record->id) : route($routeBase.'.store');
    $lat = old('latitude', $record->latitude ?? -6.2);
    $lng = old('longitude', $record->longitude ?? 106.816666);
    $radius = old('radius_meters', $record->radius_meters ?? 100);
@endphp

@section('content')
    <form method="POST" action="{{ $action }}" class="max-w-3xl">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        <div class="rounded-2xl border border-slate-200 bg-white p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Nama Lokasi</label>
                    <input type="text" name="name" value="{{ old('name', $record->name) }}" class="{{ $input }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $lbl }}">Alamat</label>
                    <textarea name="address" rows="2" class="{{ $input }}">{{ old('address', $record->address) }}</textarea>
                </div>

                <div>
                    <label class="{{ $lbl }}">Latitude</label>
                    <input type="text" id="latitude" name="latitude" value="{{ $lat }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Longitude</label>
                    <input type="text" id="longitude" name="longitude" value="{{ $lng }}" class="{{ $input }}" required>
                </div>
                <div>
                    <label class="{{ $lbl }}">Radius (meter)</label>
                    <input type="number" id="radius_meters" name="radius_meters" value="{{ $radius }}" class="{{ $input }}" min="10" max="5000" required>
                    <p class="mt-1 text-xs text-slate-400">Jarak maksimum karyawan dari titik agar presensi diterima.</p>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $record->is_active ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Aktif
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <button type="button" id="open-map" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        Pilih Lokasi di Peta
                    </button>
                    <p class="mt-1 text-xs text-slate-400">Buka peta untuk menentukan titik dengan klik/geser pin atau pencarian alamat.</p>
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan</button>
            <a href="{{ route($routeBase.'.index') }}" class="rounded-lg px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Batal</a>
        </div>
    </form>

    {{-- Modal peta --}}
    <div id="map-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200">
        <div id="map-panel" class="flex max-h-[90vh] w-full max-w-3xl scale-95 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 transition-transform duration-200">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                <h3 class="text-sm font-semibold text-slate-800">Pilih Titik Lokasi Presensi</h3>
                <button type="button" id="close-map" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="border-b border-slate-200 p-3">
                <div class="flex gap-2">
                    <input type="text" id="map-search" class="{{ $input }}" placeholder="Cari alamat / tempat… (mis. Monas Jakarta)" autocomplete="off">
                    <button type="button" id="map-search-btn" class="shrink-0 rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
                    <button type="button" id="map-locate-btn" class="shrink-0 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100">Lokasi Saya</button>
                </div>
            </div>
            <div class="relative">
                <div id="map" class="h-[60vh] w-full"></div>
                <div id="map-loading" class="absolute inset-0 z-[1000] flex items-center justify-center bg-white/70 backdrop-blur-sm">
                    <div class="flex flex-col items-center gap-2 text-slate-500">
                        <span class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600"></span>
                        <span class="text-xs font-medium">Memuat peta…</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between gap-3 border-t border-slate-200 px-5 py-3">
                <p class="text-xs text-slate-500">Terpilih: <span id="map-coord" class="font-medium text-slate-700">{{ number_format((float) $lat, 6) }}, {{ number_format((float) $lng, 6) }}</span></p>
                <button type="button" id="map-done" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">Selesai</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const radiusInput = document.getElementById('radius_meters');
            const coordLabel = document.getElementById('map-coord');
            const modal = document.getElementById('map-modal');

            let lat = parseFloat(latInput.value) || -6.2;
            let lng = parseFloat(lngInput.value) || 106.816666;
            let radius = parseInt(radiusInput.value) || 100;
            let map = null, marker = null, circle = null;
            const loading = document.getElementById('map-loading');
            const panel = document.getElementById('map-panel');

            function setPoint(la, ln, zoom) {
                lat = la;
                lng = ln;
                latInput.value = la.toFixed(7);
                lngInput.value = ln.toFixed(7);
                if (coordLabel) coordLabel.textContent = la.toFixed(6) + ', ' + ln.toFixed(6);
                if (marker) marker.setLatLng([la, ln]);
                if (circle) circle.setLatLng([la, ln]);
                if (zoom && map) map.setView([la, ln], zoom);
            }

            function initMap() {
                if (map) return;
                map = L.map('map').setView([lat, lng], 16);
                const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19, attribution: '© OpenStreetMap'
                }).addTo(map);
                tiles.on('load', () => loading.classList.add('hidden'));

                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                circle = L.circle([lat, lng], { radius: radius, color: '#4f46e5', fillColor: '#6366f1', fillOpacity: 0.15 }).addTo(map);

                map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng));
                marker.on('dragend', () => {
                    const p = marker.getLatLng();
                    setPoint(p.lat, p.lng);
                });
            }

            function openModal() {
                loading.classList.remove('hidden');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                requestAnimationFrame(() => {
                    modal.classList.remove('opacity-0');
                    panel.classList.remove('scale-95');
                });
                initMap();
                setTimeout(() => { map.invalidateSize(); map.setView([lat, lng]); }, 150);
                // Jaring pengaman bila event 'load' tidak terpicu.
                setTimeout(() => loading.classList.add('hidden'), 4000);
            }

            function closeModal() {
                modal.classList.add('opacity-0');
                panel.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 200);
            }

            document.getElementById('open-map').addEventListener('click', openModal);
            document.getElementById('close-map').addEventListener('click', closeModal);
            document.getElementById('map-done').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

            // Sinkron input manual → peta.
            latInput.addEventListener('change', () => {
                const v = parseFloat(latInput.value);
                if (!isNaN(v)) setPoint(v, lng);
            });
            lngInput.addEventListener('change', () => {
                const v = parseFloat(lngInput.value);
                if (!isNaN(v)) setPoint(lat, v);
            });
            radiusInput.addEventListener('input', () => {
                const r = parseInt(radiusInput.value);
                if (!isNaN(r) && r > 0 && circle) circle.setRadius(r);
            });

            // Lokasi saya.
            document.getElementById('map-locate-btn').addEventListener('click', () => {
                if (!navigator.geolocation) { alert('Browser tidak mendukung geolokasi.'); return; }
                navigator.geolocation.getCurrentPosition(
                    (pos) => setPoint(pos.coords.latitude, pos.coords.longitude, 17),
                    () => alert('Gagal mengambil lokasi. Pastikan izin lokasi aktif.')
                );
            });

            // Pencarian alamat via Nominatim (OpenStreetMap).
            async function search() {
                const q = document.getElementById('map-search').value.trim();
                if (!q) return;
                try {
                    const res = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data && data.length) {
                        setPoint(parseFloat(data[0].lat), parseFloat(data[0].lon), 17);
                    } else {
                        alert('Lokasi tidak ditemukan.');
                    }
                } catch (e) {
                    alert('Gagal mencari lokasi.');
                }
            }
            document.getElementById('map-search-btn').addEventListener('click', search);
            document.getElementById('map-search').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); search(); }
            });
        });
    </script>
@endpush
