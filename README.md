# omsetaPOS

omsetaPOS adalah aplikasi POS berbasis Laravel 13, PHP 8.4, dan Filament 5.

## Fitur MVP

- Multi-toko untuk 1 user.
- Role awal: `cashier`, `admin`, `superuser`.
- CMS Filament untuk toko, user, produk, stok, penjualan, refund, stock movement, dan audit log.
- Halaman kasir dengan scan/cari produk via barcode, SKU, atau nama.
- Checkout cash dan QRIS.
- Refund partial, full refund, dan tukar barang dengan perhitungan selisih.
- Report penjualan realtime dengan filter tanggal, toko, dan metode pembayaran.
- Audit log untuk order dan refund.

## Jalankan Lokal

```bash
composer install
php artisan migrate --seed
php artisan serve
```

Panel:

```text
http://127.0.0.1:8000/admin
```

Kasir standalone:

```text
http://127.0.0.1:8000/kasir
```

Akun demo:

```text
superuser@omsetapos.test / password
admin@omsetapos.test / password
cashier@omsetapos.test / password
```

## Test

```bash
php artisan test
```

## Catatan

Saran fitur lanjutan ada di `docs/fitur-lanjutan.md`.
