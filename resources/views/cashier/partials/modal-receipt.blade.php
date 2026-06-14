    <div id="receipt-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 420px;">
            <header class="modal-header">
                <h3>Detail Transaksi</h3>
                <button type="button" id="close-receipt-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <div id="receipt-content" class="receipt"></div>
                <div class="receipt-actions">
                    <button type="button" id="mark-sale-paid" class="small-btn" style="height:44px; display:none;">
                        Set Lunas
                    </button>
                    <button type="button" id="print-receipt" class="primary-btn receipt-print-btn">
                        Print Nota
                    </button>
                    <button type="button" id="finish-receipt" class="small-btn" style="height:44px;">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
