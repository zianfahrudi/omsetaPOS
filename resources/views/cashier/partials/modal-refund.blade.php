    <div id="refund-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 980px;">
            <header class="modal-header">
                <h3>Refund Transaksi</h3>
                <button type="button" id="close-refund-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <div class="refund-grid">
                    <section class="refund-panel">
                        <h4>Cari transaksi</h4>
                        <input type="search" id="refund-search" class="input modal-search-input"
                            placeholder="Nomor transaksi, customer, atau barang...">
                        <div id="refund-sale-list" class="transaction-list"></div>
                    </section>
                    <section class="refund-panel">
                        <h4>Detail refund</h4>
                        <label class="field">
                            <span>Tipe refund</span>
                            <select class="select" id="refund-type">
                                <option value="full">Full refund</option>
                                <option value="exchange">Ganti barang</option>
                            </select>
                        </label>
                        <div id="refund-selected-sale"
                            style="text-align:center; padding:20px; color:var(--muted); border:1px dashed var(--line); border-radius:var(--radius-md);">
                            Pilih transaksi dulu.
                        </div>
                        <div id="refund-return-list" style="display:grid; gap:10px;"></div>
                        <div id="refund-replacement-section" style="display:none; gap:10px; flex-direction:column;">
                            <button class="small-btn" type="button" id="open-refund-products"
                                style="height:44px; text-align:center;">
                                Pilih Barang Pengganti
                            </button>
                            <div id="refund-replacement-cart" style="display:grid; gap:10px;"></div>
                            <label class="field">
                                <span>Tambahan pembayaran</span>
                                <input class="input" id="refund-additional-payment" type="text" inputmode="numeric"
                                    autocomplete="off" placeholder="0">
                            </label>
                        </div>
                        <label class="field">
                            <span>Foto bukti barang refund</span>
                            <input class="input" id="refund-evidence" type="file" accept="image/*" multiple
                                style="padding: 10px;">
                        </label>
                        <label class="field">
                            <span>Catatan refund</span>
                            <textarea class="input" id="refund-reason" rows="3"
                                style="height:auto; min-height:84px; padding:12px 16px;"
                                placeholder="Alasan refund..."></textarea>
                        </label>
                        <div class="refund-summary" id="refund-summary"></div>
                        <button class="primary-btn" type="button" id="process-refund" disabled>
                            Proses Refund
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </div>
