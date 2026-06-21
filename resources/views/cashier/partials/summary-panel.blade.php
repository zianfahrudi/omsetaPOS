        <aside class="checkout-panel">
            <header class="order-head" style="border-bottom: 1px solid var(--line); padding-bottom: 16px;">
                <h2>Detail Transaksi</h2>
            </header>
            <div class="order-total" id="order-total-footer">
                <div id="order-details-container" style="flex: 1;">
                    <!-- Data Pelanggan -->
                    <div class="panel-section">
                        <span class="section-title">Data Pelanggan</span>
                        <div style="display: flex; gap: 8px; position: relative;">
                            <input type="search" id="customer-search" class="input"
                                placeholder="Cari nama atau no. hp dari database..." autocomplete="off">
                            <button type="button" class="small-btn" id="btn-open-manual-customer"
                                style="width: 48px; flex-shrink: 0; padding: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                            </button>
                            <div id="customer-list" class="customer-list"
                                style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 50; background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 200px; overflow-y: auto; display: none; flex-direction: column; gap: 8px; padding: 8px;">
                                <!-- Customer list rendered here -->
                            </div>
                        </div>
                    </div>

                    <div class="vehicle-block panel-section">
                        <span class="section-title">Kendaraan</span>
                        <label class="field" style="position: relative;">
                            <span>Nomor Plat Kendaraan</span>
                            <input class="input" id="vehicle-plate-number" type="text" placeholder="Ketik plat untuk lihat riwayat servis…"
                                autocomplete="off">
                            <div id="vehicle-list" class="customer-list"
                                style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 45; background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 240px; overflow-y: auto; display: none; flex-direction: column; gap: 8px; padding: 8px;">
                                <!-- Vehicle list rendered here -->
                            </div>
                        </label>

                        <div id="vehicle-service-info" class="vehicle-service" style="display: none;"></div>

                        <label class="field">
                            <span>KM Sekarang <small style="font-weight:500;text-transform:none;letter-spacing:0;">(diperbarui saat servis)</small></span>
                            <div style="display: flex; gap: 8px;">
                                <input class="input" id="vehicle-mileage" type="number" min="0" inputmode="numeric"
                                    placeholder="Masukkan KM terkini">
                                <button type="button" class="small-btn" id="btn-open-vehicle-modal"
                                    style="width: 48px; flex-shrink: 0; padding: 0;" title="Tambah kendaraan baru">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 5v14M5 12h14" />
                                    </svg>
                                </button>
                            </div>
                        </label>
                    </div>

                    <!-- Kode Diskon -->
                    <div class="discount-row"
                        style="margin-bottom: 24px; display: none; gap: 8px; align-items: flex-end;">
                        <label class="field" style="flex: 1;">
                            <span>Kode Diskon</span>
                            <input class="input" id="discount-code" type="text" placeholder="Contoh: HEMAT10">
                        </label>
                        <button class="small-btn" type="button" id="apply-discount">Apply</button>
                        <button class="small-btn" type="button" id="clear-discount">Hapus</button>
                    </div>

                    <div class="summary">
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <strong id="subtotal">Rp 0</strong>
                        </div>
                        <div class="summary-line" id="discount-row-display" style="display: none;">
                            <span>Diskon</span>
                            <strong id="discount-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Service fee <small id="service-fee-rate">(0%)</small></span>
                            <strong id="service-fee-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Tax <small id="tax-rate">(0%)</small></span>
                            <strong id="tax-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Kembalian</span>
                            <strong id="change" style="color: var(--success);">Rp 0</strong>
                        </div>
                        <div class="summary-line" id="debt-row-display" style="display: none;">
                            <span>Hutang</span>
                            <strong id="debt-total" style="color: var(--danger);">Rp 0</strong>
                        </div>
                        <div class="summary-line grand">
                            <span>Total Pembayaran</span>
                            <strong id="grand-total">Rp 0</strong>
                        </div>
                    </div>

                    <div class="pay-grid">
                        <label class="field" style="grid-column: 1 / -1; margin-bottom: -4px;">
                            <span>Metode Pembayaran</span>
                        </label>
                        <input type="hidden" id="payment-method" value="cash">
                        <div class="payment-methods">
                            <div class="payment-card active" data-method="cash">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="12" cy="12" r="2" />
                                    <path d="M6 12h.01M18 12h.01" />
                                </svg>
                                Tunai
                            </div>
                            <div class="payment-card" data-method="qris">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                    <rect x="7" y="7" width="3" height="3" />
                                    <rect x="14" y="7" width="3" height="3" />
                                    <rect x="7" y="14" width="3" height="3" />
                                    <path d="M14 14h3v3h-3z" />
                                </svg>
                                QRIS
                            </div>
                            <div class="payment-card" data-method="transfer">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 7h13l-3-3" />
                                    <path d="M21 17H8l3 3" />
                                </svg>
                                Transfer
                            </div>
                        </div>
                        <label class="debt-toggle" id="split-toggle-wrap" style="grid-column: 1 / -1;">
                            <input id="split-toggle" type="checkbox">
                            <span>Bayar gabungan (cash + transfer/QRIS)</span>
                        </label>
                        <div id="split-panel" style="display: none; grid-column: 1 / -1; flex-direction: column; gap: 8px;">
                            <label class="field">
                                <span>Tunai</span>
                                <input class="input split-input" id="split-cash" type="text" inputmode="numeric" autocomplete="off" placeholder="0">
                            </label>
                            <label class="field">
                                <span>Transfer</span>
                                <input class="input split-input" id="split-transfer" type="text" inputmode="numeric" autocomplete="off" placeholder="0">
                            </label>
                            <label class="field">
                                <span>QRIS</span>
                                <input class="input split-input" id="split-qris" type="text" inputmode="numeric" autocomplete="off" placeholder="0">
                            </label>
                            <div class="summary-line" style="margin-top: 2px;">
                                <span>Total Dibayar</span>
                                <strong id="split-total">Rp 0</strong>
                            </div>
                        </div>
                        <label class="field" id="paid-field" style="grid-column: 1 / -1;">
                            <span id="paid-label">Nominal Diterima</span>
                            <input class="input" id="paid-amount" type="text" inputmode="numeric" autocomplete="off"
                                placeholder="0">
                        </label>
                        <label class="field" id="proof-field" style="display: none; grid-column: 1 / -1;">
                            <span>Upload Bukti Transfer</span>
                            <input class="input" id="payment-proof" type="file" accept="image/*" style="padding: 10px;">
                        </label>
                        <label class="debt-toggle">
                            <input id="is-debt" type="checkbox">
                            <span>Transaksi hutang piutang</span>
                        </label>
                    </div>
                </div>

                <div class="checkout-foot">
                    <button class="primary-btn" type="button" id="checkout" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        Proses Pembayaran
                    </button>
                </div>
            </div>
        </aside>
