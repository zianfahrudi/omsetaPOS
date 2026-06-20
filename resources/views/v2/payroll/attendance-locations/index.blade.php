@extends('v2.layouts.app')
@section('title', $label)
@section('heading', $label)

@push('head')
    <style>
        .leaflet-popup-content-wrapper { border-radius: 0.75rem; box-shadow: 0 10px 25px -5px rgba(15,23,42,.25); }
        .leaflet-popup-content { margin: 0.75rem 0.9rem; }
        .leaflet-popup-tip { box-shadow: 0 3px 8px rgba(15,23,42,.18); }
    </style>
@endpush

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari…"
                   class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cari</button>
        </form>
        <div class="flex items-center gap-2">
            @if ($mapPoints->isNotEmpty())
                <button type="button" id="open-map" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/></svg>
                    Lihat Peta
                </button>
            @endif
            <a href="{{ route($routeBase.'.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">+ Tambah {{ $label }}</a>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left text-slate-500">
                        @foreach ($columns as $header => $fn)
                            <th class="px-4 py-3 font-medium">{{ $header }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            @foreach ($columns as $header => $fn)
                                <td class="px-4 py-3 text-slate-700">{!! $fn($record) !!}</td>
                            @endforeach
                            <td class="px-4 py-3 text-center">
                                @if (($record->is_active ?? true))
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route($routeBase.'.edit', $record->id) }}" class="rounded-md px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50">Edit</a>
                                    <form method="POST" action="{{ route($routeBase.'.destroy', $record->id) }}" onsubmit="return confirm('Hapus {{ strtolower($label) }} ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + 2 }}" class="px-4 py-10 text-center text-slate-400">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>

    {{-- Modal peta semua titik --}}
    @if ($mapPoints->isNotEmpty())
        <div id="map-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200">
            <div id="map-panel" class="flex max-h-[90vh] w-full max-w-4xl scale-95 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 transition-transform duration-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <h3 class="text-sm font-semibold text-slate-800">Peta Titik Lokasi Presensi</h3>
                    <button type="button" id="close-map" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="relative">
                    <div id="map" class="h-[70vh] w-full"></div>
                    <div id="map-loading" class="absolute inset-0 z-[1000] flex items-center justify-center bg-white/70 backdrop-blur-sm">
                        <div class="flex flex-col items-center gap-2 text-slate-500">
                            <span class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600"></span>
                            <span class="text-xs font-medium">Memuat peta…</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($mapPoints->isNotEmpty())
        <script>
            window.addEventListener('load', function () {
                const points = @json($mapPoints);
                const modal = document.getElementById('map-modal');
                const panel = document.getElementById('map-panel');
                const loading = document.getElementById('map-loading');
                let map = null;

                function initMap() {
                    if (map) return;
                    map = L.map('map');
                    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19, attribution: '© OpenStreetMap'
                    }).addTo(map);
                    tiles.on('load', () => loading.classList.add('hidden'));

                    const bounds = [];
                    points.forEach((p) => {
                        const color = p.active ? '#4f46e5' : '#94a3b8';
                        L.circle([p.lat, p.lng], { radius: p.radius, color: color, fillColor: color, fillOpacity: 0.15 }).addTo(map);
                        const badge = p.active
                            ? '<span style="background:#ecfdf5;color:#047857;padding:1px 6px;border-radius:9999px;font-size:10px;font-weight:600;">Aktif</span>'
                            : '<span style="background:#fef2f2;color:#dc2626;padding:1px 6px;border-radius:9999px;font-size:10px;font-weight:600;">Nonaktif</span>';
                        const html =
                            '<div style="min-width:170px;">' +
                                '<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">' +
                                    '<span style="font-size:13px;font-weight:600;color:#1e293b;">' + p.name + '</span>' + badge +
                                '</div>' +
                                (p.address ? '<p style="font-size:11px;color:#64748b;margin:0 0 4px;">' + p.address + '</p>' : '') +
                                '<p style="font-size:11px;color:#64748b;margin:0 0 8px;">Radius presensi: <b>' + p.radius + ' m</b></p>' +
                                '<a href="' + p.edit_url + '" style="display:inline-block;background:#4f46e5;color:#fff;font-size:11px;font-weight:500;padding:4px 10px;border-radius:8px;text-decoration:none;">Edit titik</a>' +
                            '</div>';
                        L.marker([p.lat, p.lng]).addTo(map).bindPopup(html);
                        bounds.push([p.lat, p.lng]);
                    });

                    if (bounds.length === 1) {
                        map.setView(bounds[0], 16);
                    } else if (bounds.length > 1) {
                        map.fitBounds(bounds, { padding: [40, 40] });
                    }
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
                    setTimeout(() => map.invalidateSize(), 150);
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
                modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
            });
        </script>
    @endif
@endpush
