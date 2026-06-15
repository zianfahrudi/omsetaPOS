# Status Implementasi Fitur omsetaPOS

Ringkasan apa yang sudah jadi dan apa yang belum, dipetakan ke folder `FEATURE/`.
Per update terakhir: 112 test, 601 assertion — semua hijau.

## ✅ Sudah diimplementasi (+ tertest)

### Buku Besar (Akuntansi inti)
- Daftar Akun (Chart of Accounts) — hierarki, akun sistem.
- Jurnal Umum manual (posting seimbang otomatis).
- Mesin double-entry (`PostingService`) + Buku Besar/Neraca Saldo (`LedgerService`).

### Point of Sale
- Kasir POS (blade `/kasir`) — sudah dibersihkan jadi multi-file.
- Riwayat transaksi, refund, hutang.
- **Sesi Kasir** (buka/tutup, kas awal/akhir, selisih).

### Penjualan
- Penawaran Harga → Pesanan Penjualan → Faktur Penjualan (AR) → Pembayaran Piutang → Retur Penjualan.

### Pembelian
- Permintaan Pembelian → Pesanan Pembelian → Faktur Pembelian (AP) → Pembayaran Hutang → Retur Pembelian.
- Cost rata-rata tertimbang otomatis.

### Kas & Bank
- Kas Masuk, Kas Keluar, Transfer Kas, Giro Masuk (siklus terima/setor/cair/tolak), Rekonsiliasi Bank.

### Persediaan
- Penyesuaian Stok / Stock Opname, Kartu Stok, Pemindahan antar-gudang (multi-gudang), Perakitan (assembly).

### Harta Tetap
- Register aset + penyusutan garis lurus.

### Data Master
- Kontak (pelanggan/supplier/lainnya), Kategori, Satuan, Gudang, Pajak, Departemen, Proyek, Mata Uang.

### Laporan (11)
- Neraca, Laba Rugi, Arus Kas, Neraca Saldo, Piutang (aging), Hutang (aging), Pajak (PPN), Persediaan (nilai), Stok per Gudang, Analisa Penjualan, Analisa Pembelian.

### Dashboard
- Kartu metrik finansial, posisi keuangan, tren omzet, grafik arus kas, distribusi penjualan (pie).

### Infrastruktur
- API REST `/api/v1` + auth Sanctum (untuk mobile).
- Dimensi proyek/departemen pada jurnal.

## ⛔ Belum diimplementasi (sisa)

| Fitur | Sumber | Catatan |
|---|---|---|
| **Penjualan Konsinyasi** | PERSEDIAAN.md | Titip-jual ke pihak ketiga. Alur khusus, belum dibuat. |
| **Transaksi Multi-currency penuh** | DATA MASTER.md | Master Mata Uang + kurs sudah ada; pencatatan transaksi valas & selisih kurs belum. |
| **Pengiriman Barang (Delivery)** | PENJUALAN.md | Saat ini stok berkurang langsung di Faktur Penjualan, bukan dokumen pengiriman terpisah. |
| **Penerimaan Barang (Goods Receipt)** | PEMBELIAN.md | Saat ini stok bertambah langsung di Faktur Pembelian, bukan dokumen penerimaan terpisah. |
| **Data Lain** | DATA MASTER.md | Label/Tag, Syarat Pembayaran, Metode Pengiriman, Template Dokumen, Data Lokasi. Konfigurasi minor. |
| **Laporan Pajak PPh** | LAPORAN.md | Saat ini fokus PPN (masukan/keluaran). PPh belum. |

## Catatan teknis
- Stok disimpan total di `products.stock`; rincian per gudang di `warehouse_stocks` (sum = total).
- Akun sistem dikenali via kolom `accounts.subtype` (cash, bank, accounts_receivable, dst).
- Dokumen transaksi bersifat immutable setelah posting (tak ada halaman edit) — koreksi lewat retur/jurnal penyesuaian.

## Pembersihan (cleanup)
- Resource admin lama **Customers** (pelanggan per-toko) dihapus — digantikan **Kontak** (Data Master). Tabel `customers` & alur POS tetap berjalan.
- Seluruh menu/grup admin diterjemahkan ke Bahasa Indonesia dan dikonsolidasi: Kasir, Penjualan, Pembelian, Persediaan, Kas & Bank, Akuntansi, Laporan, Data Master, Manajemen, Sistem.
