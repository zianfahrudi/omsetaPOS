# Status Implementasi Fitur omsetaPOS

Ringkasan apa yang sudah jadi, dipetakan ke folder `FEATURE/`.
Per update terakhir: **140 test, 767 assertion — semua hijau**.

Aplikasi punya 2 antarmuka admin:
- **Admin v2** (utama, tanpa Filament) — Blade + Tailwind + Alpine, prefix `/app`.
- **Panel Filament lama** — masih tersedia di `/admin`.
- **Kasir POS** — Blade di `/kasir` (gaya supermarket, list produk + pintasan keyboard).

Entry point `/` dan `/login` mengarah ke Admin v2 (`/app`).

## ✅ Sudah diimplementasi (+ tertest) di Admin v2

### Dashboard
- Kartu metrik (Penjualan, Pembelian, Saldo Kas & Bank, Piutang, Hutang).
- Grafik **Penjualan vs Pembelian** 6 bulan (Chart.js) + **Distribusi Penjualan** (doughnut).
- Posisi keuangan (Aset/Liabilitas/Ekuitas) + transaksi terakhir.

### Buku Besar (Akuntansi inti)
- Daftar Akun (CoA berhierarki, akun sistem via `subtype`).
- Buku Besar per akun (mutasi + saldo berjalan).
- Jurnal Umum manual (multi-baris, cek seimbang real-time, posting otomatis).
- Mesin double-entry `PostingService` + `LedgerService`.

### Point of Sale
- Kasir POS (`/kasir`) — list produk gaya supermarket, pintasan keyboard (F2/F4/F9/F6/F7/Esc).
- Riwayat Transaksi POS + detail, Sesi Kasir (kas awal/akhir, selisih).

### Penjualan
- Penawaran → Pesanan → Faktur (AR) → Pembayaran Piutang → Retur Penjualan.
- Daftar Piutang (aging). Konversi antar-dokumen.

### Pembelian
- Permintaan → Pesanan → Faktur (AP) → Pembayaran Hutang → Retur Pembelian.
- Daftar Hutang (aging). Cost rata-rata tertimbang otomatis.

### Kas & Bank
- Kas Masuk / Keluar / Transfer (1 form), Giro Masuk (terima→setor→cair→tolak), Rekonsiliasi Bank.

### Persediaan
- Penyesuaian Stok (≈ Stock Opname), Pemindahan antar-gudang, Perakitan (assembly),
  **Konsinyasi** (kirim → settle → retur), Kartu Stok.

### Harta Tetap
- Register aset + penyusutan garis lurus (posting jurnal).

### Data Master
- Kontak, Produk, **Pelanggan (POS)**, **Kendaraan**, Satuan, Gudang, Departemen, Proyek,
  Mata Uang, Pajak, Harta Tetap, **Provinsi & Kabupaten/Kota** (data wilayah Indonesia).

### Laporan
- Neraca, Laba Rugi, Arus Kas, Penjualan, Pembelian, Persediaan, Pajak (PPN), Piutang, Hutang.

### Infrastruktur
- API REST `/api/v1` + auth Sanctum (untuk mobile).
- Dimensi proyek/departemen pada jurnal.

## ⛔ Belum diimplementasi (sisa minor)

| Fitur | Sumber | Catatan |
|---|---|---|
| **Pengiriman Barang (Delivery)** | PENJUALAN.md | Stok berkurang langsung di Faktur Penjualan, bukan dokumen pengiriman terpisah. |
| **Penerimaan Barang (Goods Receipt)** | PEMBELIAN.md | Stok bertambah langsung di Faktur Pembelian, bukan dokumen penerimaan terpisah. |
| **Void transaksi POS dari v2** | POINT OF SALE.md | Pembatalan transaksi belum tersedia di admin v2. |
| **Data Lain** | DATA MASTER.md | Label/Tag, Syarat Pembayaran, Metode Pengiriman, Template Dokumen. Konfigurasi minor. |
| **Laporan Pajak PPh** | LAPORAN.md | Fokus saat ini PPN (masukan/keluaran). PPh belum. |
| **Transaksi valas penuh** | DATA MASTER.md | Master Mata Uang + kurs ada; pencatatan transaksi valas & selisih kurs belum. |

## Catatan teknis
- Stok total di `products.stock`; rincian per gudang di `warehouse_stocks` (sum = total).
- Akun sistem dikenali via `accounts.subtype` (cash, bank, accounts_receivable, inventory, consignment_inventory, giro_receivable, dst).
- Dokumen transaksi immutable setelah posting — koreksi via retur / jurnal penyesuaian.
- Data wilayah (34 provinsi, 514 kab/kota) di-seed terpisah: `php artisan db:seed --class=WilayahSeeder` (sumber: emsifa/api-wilayah-indonesia, MIT).
