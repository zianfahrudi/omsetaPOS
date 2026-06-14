    <div id="customer-manual-modal" class="modal-backdrop hidden">
        <div class="modal">
            <header class="modal-header">
                <h3>Pelanggan Baru</h3>
                <button type="button" id="close-customer-manual-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <input type="hidden" id="customer-id">
                <label class="field">
                    <span>Nama Lengkap</span>
                    <input class="input" id="customer-name" type="text" placeholder="Pelanggan Umum">
                </label>
                <label class="field">
                    <span>No. WhatsApp / HP</span>
                    <input class="input" id="customer-phone" type="tel" placeholder="Opsional">
                </label>
                <div class="vehicle-grid" style="margin-bottom: 0;">
                    <label class="field">
                        <span>Nomor Plat</span>
                        <input class="input" id="manual-vehicle-plate-number" type="text" placeholder="Opsional">
                    </label>
                    <label class="field">
                        <span>Kilometer</span>
                        <input class="input" id="manual-vehicle-mileage" type="number" min="0" inputmode="numeric"
                            placeholder="Opsional">
                    </label>
                </div>
                <button type="button" id="btn-apply-customer" class="primary-btn" style="margin-top: 8px;">Simpan &
                    Terapkan Pelanggan</button>
            </div>
        </div>
    </div>
