    <div id="vehicle-manual-modal" class="modal-backdrop hidden">
        <div class="modal">
            <header class="modal-header">
                <h3>Kendaraan Baru</h3>
                <button type="button" id="close-vehicle-manual-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <input type="hidden" id="vehicle-owner-id">
                <label class="field">
                    <span>Nama / Merek</span>
                    <input class="input" id="new-vehicle-name" type="text" placeholder="Contoh: Avanza / Honda Brio">
                </label>
                <label class="field">
                    <span>Nomor Plat</span>
                    <input class="input" id="new-vehicle-plate-number" type="text" placeholder="Contoh: DD 1234 XY">
                </label>
                <label class="field" style="position: relative;">
                    <span>Nama Pemilik</span>
                    <div style="display: flex; gap: 8px;">
                        <input class="input" id="vehicle-owner-name" type="text" placeholder="Nama customer"
                            autocomplete="off">
                        <button type="button" class="small-btn" id="btn-search-vehicle-owner"
                            style="width: 48px; flex-shrink: 0; padding: 0;" title="Cari customer">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                        </button>
                    </div>
                    <div id="vehicle-owner-list" class="customer-list"
                        style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 70; background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 200px; overflow-y: auto; display: none; flex-direction: column; gap: 8px; padding: 8px;">
                    </div>
                </label>
                <label class="field">
                    <span>Nomor HP Pemilik</span>
                    <input class="input" id="vehicle-owner-phone" type="tel" placeholder="Opsional">
                </label>
                <label class="field">
                    <span>Kilometer</span>
                    <input class="input" id="new-vehicle-mileage" type="number" min="0" inputmode="numeric"
                        placeholder="0">
                </label>
                <button type="button" id="btn-save-vehicle" class="primary-btn" style="margin-top: 8px;">Simpan &
                    Terapkan Kendaraan</button>
            </div>
        </div>
    </div>
