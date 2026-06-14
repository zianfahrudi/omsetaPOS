    <div id="refund-product-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 760px;">
            <header class="modal-header">
                <h3>Pilih Barang Pengganti</h3>
                <button type="button" id="close-refund-product-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body refund-product-body">
                <input type="search" id="refund-product-search" class="input modal-search-input refund-product-search"
                    placeholder="Cari nama produk atau SKU...">
                <div id="refund-product-list" class="refund-product-grid"></div>
                <div class="refund-product-actions">
                    <button type="button" id="apply-refund-products" class="primary-btn receipt-print-btn">
                        Terapkan Pilihan
                    </button>
                    <button type="button" id="cancel-refund-products" class="small-btn" style="height:44px;">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
