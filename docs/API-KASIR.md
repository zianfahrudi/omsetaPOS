# API Kasir omsetaPOS (untuk Mobile App)

REST API untuk aplikasi kasir mobile. Auth pakai **Laravel Sanctum** (bearer token).
Semua response JSON. Versi: **v1**.

## Base URL
```
Produksi : https://omseta.ziandev.site/api/v1
Lokal    : http://localhost:18080/api/v1
```

## Header wajib
```
Accept: application/json
Authorization: Bearer <TOKEN>      # untuk semua endpoint kecuali login
```
Untuk upload file (checkout QRIS / refund) pakai `Content-Type: multipart/form-data`.
Endpoint JSON biasa pakai `Content-Type: application/json`.

## Format Error
- **401** belum login / token invalid → `{ "message": "Unauthenticated." }`
- **403** tidak punya akses outlet → `{ "message": "..." }`
- **422** validasi / aturan bisnis gagal:
  ```json
  { "message": "Stok tidak cukup.", "errors": { "field": ["..."] } }
  ```
- **404** data tidak ditemukan.

Alur singkat mobile: **login → pilih outlet → (buka sesi kasir) → cari produk →
hitung pricing → checkout → cetak/struk → (tutup sesi kasir)**.

---

## 1. Auth

### POST `/auth/login`  (publik)
Login & ambil token.

Request:
```json
{ "email": "kasir@toko.test", "password": "rahasia", "device_name": "iPhone 15" }
```
Response `200`:
```json
{
  "token": "1|abcdef...token",
  "user": {
    "id": 5, "name": "Budi", "email": "kasir@toko.test", "role": "cashier",
    "stores": [ { "id": 1, "name": "Toko Pusat", "code": "T-1", "phone": null, "address": null, "is_active": true } ]
  }
}
```
Error `422`: email/password salah atau akun nonaktif.

> Simpan `token` di secure storage. Kirim di header `Authorization: Bearer <token>`.

### GET `/auth/me`
Profil user + daftar outlet yang bisa diakses. Response sama seperti objek `user` di atas.

### POST `/auth/logout`
Hapus token saat ini. Response `200`: `{ "message": "Logged out." }`.

---

## 2. Outlet

### GET `/stores`
Daftar outlet yang boleh diakses user.
```json
{ "data": [ { "id": 1, "name": "Toko Pusat", "code": "T-1", "phone": null, "address": null, "is_active": true } ] }
```
> `store_id` dari sini dipakai sebagai parameter di hampir semua endpoint lain.

---

## 3. Produk

### GET `/products?store_id=1&q=oli&product_id=`
Cari produk pada outlet (maks 80, urut nama). `q` opsional (nama/sku/barcode),
`product_id` opsional (ambil 1 produk).
```json
{
  "data": [
    {
      "id": 12, "name": "Oli Mesin 1L", "code": "8991002", "barcode": "8991002", "sku": "OLI-1L",
      "image_url": null, "product_type": "goods",
      "price": 55000, "unit_price": 55000, "base_price": 55000,
      "fee_amount": 0,
      "product_service_fee": 0, "product_service_fee_type": null, "product_service_fee_value": 0,
      "product_tax_type": null, "product_tax_value": 0, "product_tax_amount": 0,
      "stock": 40, "unit": "pcs"
    }
  ]
}
```
Field harga: `unit_price` = harga jual final per unit (sudah termasuk fee/pajak per produk
sesuai konfigurasi). `product_type` bisa `goods` atau `service`.

---

## 4. Pelanggan

### GET `/customers?store_id=1&q=budi`
Cari pelanggan (maks 20). Termasuk kendaraan bila ada.
```json
{
  "data": [
    { "id": 3, "name": "Budi", "phone": "0812...", "email": null, "outstanding_debt": 0,
      "vehicles": [ { "id": 7, "name": null, "plate_number": "B 1234  XYZ", "mileage": 12000 } ] }
  ]
}
```

### GET `/customers/check?store_id=1&name=Budi&phone=0812`
Cek duplikat sebelum menyimpan.
```json
{ "exists": true, "message": "Pelanggan sudah terdaftar. Pilih dari database pelanggan." }
```

### POST `/customers`
Buat pelanggan baru (+ kendaraan opsional).
```json
{ "store_id": 1, "name": "Budi", "phone": "0812...", "vehicle_plate_number": "B 1234 XYZ", "vehicle_mileage": 12000 }
```
Response `201`: objek `CustomerResource`. Error `422` bila duplikat.

---

## 5. Kendaraan (opsional, untuk bisnis bengkel)

### GET `/vehicles?store_id=1&q=B1234`
Cari kendaraan (maks 20). Termasuk data pemilik.
```json
{ "data": [ { "id": 7, "name": null, "plate_number": "B 1234 XYZ", "mileage": 12000,
  "customer": { "id": 3, "name": "Budi", "phone": "0812..." } } ] }
```

### POST `/vehicles`
Buat/update kendaraan (otomatis buat/cari pemilik).
```json
{ "store_id": 1, "customer_id": 3, "owner_name": "Budi", "owner_phone": "0812...",
  "vehicle_name": "Avanza", "plate_number": "B 1234 XYZ", "mileage": 12500 }
```
Response `201` (baru) / `200` (update): objek `VehicleResource`.

---

## 6. Pricing (hitung total sebelum checkout)

### POST `/pricing`
Hitung diskon, pajak, service fee, grand total dari subtotal.
```json
{ "store_id": 1, "subtotal": 110000, "discount_code": "PROMO10" }
```
Response `200`:
```json
{
  "pricing": {
    "discount_code": "PROMO10", "discount_name": "Promo 10%", "discount_type": "percentage",
    "discount_value": 10, "discount_total": 11000,
    "tax_percentage": 0, "tax_total": 0,
    "service_fee_percentage": 0, "service_fee_total": 0,
    "grand_total": 99000
  }
}
```
Error `422` bila kode diskon tidak valid.

---

## 7. Checkout (buat transaksi)

### POST `/checkout`
`Content-Type: multipart/form-data` bila `payment_method=qris` (wajib `payment_proof`).
Untuk `cash` boleh JSON biasa.

Field:
| Field | Wajib | Keterangan |
|---|---|---|
| `store_id` | ya | outlet |
| `payment_method` | ya | `cash` / `qris` |
| `payment_proof` | jika qris | file gambar (≤5MB) |
| `paid_amount` | ya | uang dibayar (untuk hitung kembalian/utang) |
| `items` | ya | array min 1 |
| `items[].product_id` | ya | |
| `items[].quantity` | ya | min 1 |
| `items[].service_fee_amount` | tidak | override fee per item |
| `items[].tax_amount` | tidak | override pajak per item |
| `customer_id` | tidak | pelanggan terdaftar |
| `customer_name`,`customer_phone` | tidak | pelanggan walk-in |
| `vehicle_plate_number`,`vehicle_mileage` | tidak | bengkel |
| `discount_code` | tidak | kode promo |
| `is_debt` | tidak | `true` = transaksi utang (bon) |

Contoh (cash, JSON):
```json
{
  "store_id": 1, "payment_method": "cash", "paid_amount": 100000,
  "discount_code": null, "is_debt": false,
  "customer_id": 3,
  "items": [ { "product_id": 12, "quantity": 1 }, { "product_id": 20, "quantity": 2 } ]
}
```
Response `201`: objek `SaleResource` (lihat bentuk di §8) dalam `{ "data": { ... } }`.
Error `422`: stok kurang / kode diskon invalid / aturan lain (baca `message`).

> Kembalian = `change_amount`. Bila `is_debt=true` & kurang bayar, `debt_amount` > 0
> dan `payment_status` = `belum_lunas`.

---

## 8. Transaksi (riwayat)

### GET `/transactions?store_id=1&q=INV`
Transaksi milik kasir login pada outlet (maks 30, terbaru dulu).
```json
{
  "data": [
    {
      "id": 88, "number": "INV/20260618/0001",
      "store_name": "Toko Pusat", "cashier_name": "Budi",
      "customer_name": "Budi", "customer_phone": "0812...",
      "vehicle_plate_number": null, "vehicle_mileage": null,
      "status": "completed", "payment_method": "cash", "payment_proof": null,
      "payment_status": "lunas", "payment_status_label": "Lunas",
      "subtotal": 110000, "discount_code": null, "discount_total": 0,
      "tax_total": 0, "service_fee_total": 0, "grand_total": 110000,
      "paid_amount": 110000, "change_amount": 0,
      "is_debt": false, "debt_amount": 0,
      "paid_at": "18 Jun 2026 10:21",
      "items": [
        { "id": 1, "product_id": 12, "name": "Oli Mesin 1L", "product_type": "goods",
          "quantity": 1, "refunded_quantity": 0, "refundable_quantity": 1,
          "unit_price": 55000, "fee_amount": 0, "service_fee_amount": 0, "tax_amount": 0, "line_total": 55000 }
      ]
    }
  ]
}
```

### POST `/transactions/{sale}/mark-paid`
Tandai transaksi utang jadi lunas. Response `200`: `SaleResource`.
Error `422` bila sudah lunas; `403` bila bukan transaksi kasir tsb.

---

## 9. Refund / Tukar Barang

### POST `/refunds`  (`multipart/form-data`, butuh foto bukti)
| Field | Wajib | Keterangan |
|---|---|---|
| `store_id` | ya | |
| `sale_id` | ya | transaksi yg direfund (harus lunas) |
| `type` | ya | `full` (refund penuh) / `exchange` (tukar) |
| `evidence_photos[]` | ya | 1–6 gambar (≤5MB) |
| `reason` | tidak | alasan |
| `returned_items[].sale_item_id` + `.quantity` | utk exchange | item yg dikembalikan |
| `replacement_items[].product_id` + `.quantity` | utk exchange | item pengganti |
| `additional_payment_amount` | tidak | tambahan bayar bila pengganti lebih mahal |

`type=full` otomatis mengembalikan semua item yang masih bisa direfund.

Response `200`:
```json
{
  "refund": {
    "receipt_type": "refund", "id": 4, "number": "RF/...",
    "sale_number": "INV/...", "store_name": "Toko Pusat", "handled_by_name": "Budi",
    "customer_name": "Budi", "type": "exchange", "status": "completed", "reason": null,
    "returned_total": 55000, "replacement_total": 70000, "refund_amount": 0,
    "additional_payment_amount": 15000, "additional_paid_amount": 20000, "change_amount": 5000,
    "evidence_photos": ["refund-proofs/xxx.jpg"],
    "created_at": "18 Jun 2026 11:00",
    "items": [ { "direction": "in", "name": "Oli 1L", "quantity": 1, "unit_price": 55000, "line_total": 55000 } ]
  },
  "sale_status": "refunded"
}
```
Error `422`: transaksi belum lunas / tidak ada item bisa direfund.

---

## 10. Sesi Kasir (buka/tutup shift)

### GET `/cashier-sessions/current?store_id=1`
Sesi terbuka milik kasir login pada outlet (atau `null`).
```json
{ "session": { "id": 9, "number": "SES/20260618/0001", "store_id": 1, "store_name": "Toko Pusat",
  "cashier_name": "Budi", "status": "open", "opened_at": "2026-06-18T08:00:00+07:00", "closed_at": null,
  "opening_cash": 200000, "cash_sales_total": 0, "expected_cash": 0, "closing_cash": 0, "cash_difference": 0, "notes": null } }
```

### POST `/cashier-sessions/open`
```json
{ "store_id": 1, "opening_cash": 200000 }
```
Response `201`: `CashierSessionResource`. Error `422` bila masih ada sesi terbuka.

### POST `/cashier-sessions/{session}/close`
```json
{ "counted_cash": 750000, "notes": "shift pagi" }
```
Sistem hitung `expected_cash` = modal awal + penjualan tunai selama sesi, dan
`cash_difference` = uang dihitung − ekspektasi. Response `200`: `CashierSessionResource`.
Error `403` bila bukan sesi milik kasir tsb; `422` bila sesi sudah ditutup.

---

## Ringkasan Endpoint

| Method | Path | Fungsi |
|---|---|---|
| POST | `/auth/login` | Login, ambil token |
| GET | `/auth/me` | Profil + outlet |
| POST | `/auth/logout` | Logout |
| GET | `/stores` | Daftar outlet |
| GET | `/products` | Cari produk |
| GET | `/customers` | Cari pelanggan |
| GET | `/customers/check` | Cek duplikat pelanggan |
| POST | `/customers` | Buat pelanggan |
| GET | `/vehicles` | Cari kendaraan |
| POST | `/vehicles` | Buat/update kendaraan |
| POST | `/pricing` | Hitung total |
| POST | `/checkout` | Buat transaksi |
| GET | `/transactions` | Riwayat transaksi |
| POST | `/transactions/{id}/mark-paid` | Lunasi utang |
| POST | `/refunds` | Refund / tukar |
| GET | `/cashier-sessions/current` | Sesi kasir aktif |
| POST | `/cashier-sessions/open` | Buka sesi |
| POST | `/cashier-sessions/{id}/close` | Tutup sesi |

## Contoh cURL

Login:
```bash
curl -X POST https://omseta.ziandev.site/api/v1/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"kasir@toko.test","password":"rahasia","device_name":"android"}'
```
Checkout:
```bash
curl -X POST https://omseta.ziandev.site/api/v1/checkout \
  -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>' \
  -H 'Content-Type: application/json' \
  -d '{"store_id":1,"payment_method":"cash","paid_amount":100000,"items":[{"product_id":12,"quantity":1}]}'
```

## Catatan implementasi mobile
- CORS sudah aktif default Laravel untuk `api/*`. Untuk app native (bukan browser) tidak perlu CSRF.
- Token tidak kedaluwarsa otomatis kecuali di-`logout`. Simpan aman.
- Semua nominal dalam Rupiah (number, tanpa pemisah ribuan).
- `store_id` wajib dikirim di endpoint per-outlet; user hanya bisa akses outlet miliknya
  (`403` bila bukan haknya). Superuser bisa semua outlet aktif.
```
