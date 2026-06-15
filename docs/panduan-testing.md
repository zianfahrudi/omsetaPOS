# Panduan Testing omsetaPOS

Panduan menjalankan aplikasi dan menguji alur end-to-end secara manual.

## 1. Persiapan

```bash
composer install
npm install && npm run build      # aset Filament
php artisan migrate:fresh --seed  # DB bersih + data demo
php artisan serve
```

Buka:
- Admin/CMS: http://127.0.0.1:8000/admin
- Kasir POS: http://127.0.0.1:8000/kasir

### Akun demo
| Email | Password | Peran |
|---|---|---|
| superuser@omsetapos.test | password | Superuser (akses semua) |
| admin@omsetapos.test | password | Admin |
| cashier@omsetapos.test | password | Kasir |

### Data demo
- 1 Perusahaan: **Omseta Group** (mata uang IDR).
- 2 Toko: Omseta Mart Pusat & Cabang.
- 60 produk (sparepart, oli, ban, jasa), 1 gudang default, CoA standar (33 akun), 1 supplier, PPN 11%.

## 2. Jalankan test otomatis

```bash
php artisan test
```
Harusnya semua hijau (112 test). Untuk modul tertentu:
```bash
php artisan test --filter=SalesInvoiceTest
```

## 3. Skenario uji manual (per modul)

Lokasi menu ada di sidebar `/admin`, dikelompokkan: Point of Sale, Penjualan, Pembelian, Persediaan, Kas & Bank, Akuntansi, Laporan, Data Master.

### A. POS + Sesi Kasir
1. **Point of Sale → Sesi Kasir → Buka**: pilih toko, kas awal mis. 200.000.
2. Buka `/kasir`, pilih toko, cari produk, checkout tunai.
3. Kembali ke **Sesi Kasir → Tutup Sesi**: isi kas akhir. Cek kolom Seharusnya & Selisih.

### B. Siklus Pembelian (isi stok + hutang)
1. **Pembelian → Permintaan Pembelian**: buat, lalu aksi "Konversi ke Pesanan".
2. **Pembelian → Pesanan Pembelian**: aksi "Konversi ke Faktur".
3. **Pembelian → Faktur Pembelian**: cek stok produk naik & cost rata-rata berubah; aksi "Bayar".
4. **Pembelian → Retur Pembelian**: pilih faktur, retur sebagian.
5. Cek **Akuntansi → Jurnal**: ada jurnal Persediaan/Hutang otomatis.

### C. Siklus Penjualan (piutang)
1. **Penjualan → Penawaran Harga** → konversi ke Pesanan → konversi ke Faktur.
2. **Penjualan → Faktur Penjualan**: cek stok turun, HPP terbentuk; aksi "Terima Bayar".
3. **Penjualan → Retur Penjualan**: retur sebagian.

### D. Kas & Bank
1. **Kas & Bank → Transaksi**: buat Kas Masuk, Kas Keluar, Transfer.
2. **Giro Masuk**: terima → setor → cairkan (pilih bank).
3. **Rekonsiliasi Bank**: pilih akun bank + saldo koran, cek status cocok/selisih.

### E. Persediaan
1. **Persediaan → Penyesuaian Stok**: input jumlah aktual, cek selisih + jurnal.
2. **Persediaan → Pemindahan Barang**: pindah antar gudang (total stok tetap).
3. **Persediaan → Perakitan**: rakit produk jadi dari komponen.
4. **Persediaan → Kartu Stok** (via Laporan): lihat mutasi.

### F. Laporan & Dashboard
- **Laporan → Neraca**: pastikan seimbang (Aset = Liabilitas + Ekuitas).
- **Laporan → Laba Rugi / Arus Kas / Neraca Saldo / Piutang / Hutang / Pajak / Persediaan / Stok per Gudang / Analisa Penjualan-Pembelian**.
- **Dashboard**: kartu metrik + grafik terisi setelah ada transaksi.

## 4. Uji API (mobile)

```bash
# Login -> token
curl -s -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"cashier@omsetapos.test","password":"password","device_name":"hp"}'

# Pakai token
curl -s http://127.0.0.1:8000/api/v1/products?store_id=1 \
  -H "Accept: application/json" -H "Authorization: Bearer <TOKEN>"
```
Endpoint: auth, stores, products, customers, vehicles, pricing, checkout, transactions, refunds.

## 5. Verifikasi konsistensi akuntansi
- Setiap transaksi (POS/penjualan/pembelian/kas/penyesuaian) menghasilkan jurnal seimbang (Debit = Kredit) — cek di **Akuntansi → Jurnal**.
- **Neraca harus selalu seimbang**. Bila tidak, ada jurnal manual yang salah.
- Nilai Persediaan di Neraca ≈ total nilai di **Laporan Persediaan**.

## 6. Reset data
Ulangi `php artisan migrate:fresh --seed` kapan saja untuk kembali ke data demo bersih.
