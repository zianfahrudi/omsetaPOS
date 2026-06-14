        <section class="workspace">
            <div class="topbar">
                <div class="brand">
                    <div class="brand-mark">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </div>
                    <div>
                        <h1>Kasir omsetaPOS</h1>
                        <p>{{ $user->name }} &middot; Selesaikan transaksi dengan cepat.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="dashboard-link" type="button" id="btn-open-refund">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <path d="M21 12a9 9 0 1 1-3-6.7" />
                            <path d="M21 3v6h-6" />
                        </svg>
                        Refund
                    </button>
                    <button class="dashboard-link" type="button" id="btn-open-transactions">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <path d="M3 3v18h18" />
                            <path d="M18 17V9" />
                            <path d="M13 17V5" />
                            <path d="M8 17v-3" />
                        </svg>
                        Riwayat Transaksi
                    </button>
                    <a class="dashboard-link" href="/admin">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                            <line x1="3" x2="21" y1="9" y2="9" />
                            <line x1="9" x2="9" y1="21" y2="9" />
                        </svg>
                        Dashboard Admin
                    </a>
                </div>
            </div>

            <div class="filters">
                <label class="field">
                    <span>Cabang Toko</span>
                    <select class="select" id="store">
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Cari Produk</span>
                    <div class="input-group">
                        <input class="input" id="search" type="search" placeholder="Ketik nama produk atau SKU...">
                    </div>
                </label>
                {{-- Barcode scan belum dipakai.
                <label class="field">
                    <span>Scan Barcode</span>
                    <div class="input-group">
                        <input class="input" id="scan" type="text" placeholder="Arahkan scanner kesini..." autofocus>
                    </div>
                </label>
                --}}
            </div>

            <div class="catalog-container">
                <div class="catalog" id="catalog"></div>
            </div>
        </section>
