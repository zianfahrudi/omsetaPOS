# API Absensi Karyawan omsetaPOS (untuk Mobile App)

REST API presensi mandiri karyawan dengan validasi **geofence** (titik lokasi)
dan **anti-fake-GPS**. Auth pakai **Laravel Sanctum** (bearer token).
Semua response JSON. Versi: **v1**.

> Catatan: karyawan (model `Employee`) adalah entitas terpisah dari user admin/kasir.
> Token karyawan **tidak** dapat dipakai untuk endpoint API Kasir, dan sebaliknya.

## Base URL
```
Produksi : https://omseta.ziandev.site/api/v1
Lokal    : http://localhost:18080/api/v1
```

## Header wajib
```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <TOKEN>      # untuk semua endpoint kecuali login
```

## Format Error
- **401** belum login / token invalid → `{ "message": "Unauthenticated." }`
- **422** validasi / aturan bisnis gagal (termasuk gagal geofence & anti-fake-GPS):
  ```json
  { "message": "Pesan utama.", "errors": { "field": ["..."] } }
  ```
- **429** terlalu banyak percobaan login.

Alur singkat mobile: **login → cek status hari ini → check-in (kirim GPS) →
… kerja … → check-out (kirim GPS)**. Riwayat & jadwal bisa diambil kapan saja.

---

## 1. Auth

### POST `/employee/auth/login`  (publik)
Login pakai **nomor telepon** + password yang diset admin.

Request:
```json
{
  "phone": "081234567890",
  "password": "rahasia123",
  "device_name": "Samsung A52",
  "device_id": "ANDROID-ABC123"
}
```
| Field | Wajib | Keterangan |
|---|---|---|
| `phone` | ya | Nomor telepon karyawan (sesuai data master). |
| `password` | ya | Password mobile yang diset admin. |
| `device_name` | tidak | Label token (default `mobile`). |
| `device_id` | tidak | ID perangkat. Saat login pertama akan didaftarkan ke karyawan (untuk binding perangkat). |

Response `200`:
```json
{
  "token": "12|abcdef...token",
  "employee": {
    "id": 3,
    "code": "E-001",
    "name": "Abdurrahman",
    "phone": "081234567890",
    "position": "Kasir",
    "location": {
      "id": 1,
      "name": "Kantor Pusat",
      "address": "Jl. Sudirman No. 1",
      "latitude": -6.2,
      "longitude": 106.81666,
      "radius_meters": 100
    }
  }
}
```
- `location` = titik presensi yang ditugaskan. `null` bila belum ditugaskan
  (sistem akan pakai titik aktif perusahaan saat check-in).

Error `422`: `{ "message": "...", "errors": { "phone": ["Nomor HP atau password salah."] } }`
(juga untuk akun nonaktif / tanpa password mobile).
Error `429`: terlalu banyak percobaan (maks 5 / menit per nomor+IP).

> Simpan `token` di secure storage. Kirim di header `Authorization: Bearer <token>`.

### GET `/employee/auth/me`
Profil karyawan + titik lokasi. Struktur sama seperti objek `employee` di atas.

### POST `/employee/auth/logout`
Hapus token saat ini. Response `200`: `{ "message": "Logged out." }`.

---

## 2. Status Presensi Hari Ini

### GET `/employee/attendance/today`
Cek apakah karyawan sudah/belum check-in atau check-out hari ini.

Response `200`:
```json
{
  "date": "2026-06-20",
  "can_check_in": true,
  "can_check_out": false,
  "attendance": null
}
```
Setelah check-in:
```json
{
  "date": "2026-06-20",
  "can_check_in": false,
  "can_check_out": true,
  "attendance": {
    "id": 45,
    "work_date": "2026-06-20",
    "status": "present",
    "source": "mobile",
    "check_in": "2026-06-20T09:02:11+07:00",
    "check_out": null,
    "total_hours": 0,
    "check_in_distance": 12.4,
    "check_out_distance": null
  }
}
```
- `can_check_in` / `can_check_out`: dipakai untuk mengaktifkan tombol di UI.

---

## 3. Check-in & Check-out

Keduanya butuh data GPS dari perangkat. Server memvalidasi (urut, gagal → `422`
dengan `errors.location`):

1. **Anti-fake-GPS** — `is_mock` harus `false`.
2. **Akurasi GPS** — `accuracy` ≤ ambang (default **100 m**).
3. **Binding perangkat** — bila diaktifkan admin, `device_id` harus cocok dengan yang terdaftar.
4. **Geofence** — jarak ke titik presensi ≤ `radius_meters` (+ buffer bila ada).

### Body request (sama untuk check-in & check-out)
```json
{
  "latitude": -6.2000500,
  "longitude": 106.8166600,
  "accuracy": 12.0,
  "is_mock": false,
  "device_id": "ANDROID-ABC123"
}
```
| Field | Wajib | Keterangan |
|---|---|---|
| `latitude` | ya | -90..90 |
| `longitude` | ya | -180..180 |
| `accuracy` | tidak | Akurasi GPS (meter) dari perangkat. Sangat disarankan dikirim. |
| `is_mock` | tidak | `true` bila perangkat mendeteksi mock/fake location. Default `false`. |
| `device_id` | tidak | ID perangkat untuk binding. |

> **Penting (sisi mobile)**: server tidak bisa mendeteksi spoofing sendiri.
> Aplikasi WAJIB mengisi `is_mock` dengan benar:
> - Android: `Location.isFromMockProvider()` / `isMock` (API 31+). Pertimbangkan **Play Integrity API**.
> - iOS: deteksi jailbreak bila perlu.
> Kirim `accuracy` apa adanya dari GPS.

### POST `/employee/attendance/check-in`
Sukses `201`:
```json
{
  "message": "Check-in berhasil.",
  "attendance": {
    "id": 45,
    "work_date": "2026-06-20",
    "status": "present",
    "source": "mobile",
    "check_in": "2026-06-20T09:02:11+07:00",
    "check_out": null,
    "total_hours": 0,
    "check_in_distance": 12.4,
    "check_out_distance": null
  }
}
```
- `status` otomatis `late` bila check-in melewati jam mulai shift terjadwal hari itu, selain itu `present`.
- `check_in_distance` = jarak (meter) dari titik presensi saat check-in.

Error `422` (contoh):
```json
{ "message": "Anda berada 1532 m dari titik presensi \"Kantor Pusat\" (maks 100 m). Mendekatlah ke lokasi.",
  "errors": { "location": ["Anda berada 1532 m dari titik presensi ..."] } }
```
Pesan `location` lain yang mungkin:
- `"Lokasi palsu (fake GPS) terdeteksi. Matikan aplikasi pemalsu lokasi lalu coba lagi."`
- `"Akurasi GPS terlalu rendah (500 m). Pastikan GPS aktif di luar ruangan lalu coba lagi."`
- `"Perangkat tidak dikenali. Hubungi admin untuk mendaftarkan ulang perangkat."`
- `"Belum ada titik lokasi presensi yang ditentukan. Hubungi admin."`

Error `422` lain: `errors.check_in = ["Anda sudah check-in hari ini."]`.

### POST `/employee/attendance/check-out`
Sukses `200`:
```json
{
  "message": "Check-out berhasil.",
  "attendance": {
    "id": 45,
    "work_date": "2026-06-20",
    "status": "present",
    "source": "mobile",
    "check_in": "2026-06-20T09:02:11+07:00",
    "check_out": "2026-06-20T17:05:40+07:00",
    "total_hours": 8.06,
    "check_in_distance": 12.4,
    "check_out_distance": 9.8
  }
}
```
Error `422`:
- `errors.check_out = ["Anda belum check-in hari ini."]`
- `errors.check_out = ["Anda sudah check-out hari ini."]`
- gagal geofence/anti-fake-GPS → `errors.location` (lihat di atas).

---

## 4. Riwayat Presensi

### GET `/employee/attendance/history?per_page=30`
Riwayat presensi karyawan (terbaru dulu). `per_page` opsional (default 30, maks 100).

Response `200`:
```json
{
  "data": [
    {
      "id": 45, "work_date": "2026-06-20", "status": "present", "source": "mobile",
      "check_in": "2026-06-20T09:02:11+07:00", "check_out": "2026-06-20T17:05:40+07:00",
      "total_hours": 8.06, "check_in_distance": 12.4, "check_out_distance": 9.8
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 30, "total": 74 }
}
```

---

## 5. Jadwal Shift

### GET `/employee/schedule`
Jadwal shift mendatang (mulai hari ini, maks 30 baris, urut tanggal).

Response `200`:
```json
{
  "data": [
    {
      "work_date": "2026-06-21",
      "shift": { "id": 1, "name": "Pagi", "start_time": "09:00:00", "end_time": "12:00:00" }
    }
  ]
}
```
- `shift` bisa `null` bila jadwal tanpa shift terkait.

---

## Lampiran A — Status Absensi
`present` · `late` · `absent` · `leave` · `sick` · `holiday`
(Presensi via mobile hanya menghasilkan `present`/`late`. Sisanya diatur admin.)

## Lampiran B — Konfigurasi anti-fake-GPS (sisi server)
Diatur via `.env` (lihat `config/attendance.php`):

| ENV | Default | Fungsi |
|---|---|---|
| `ATTENDANCE_REJECT_MOCK` | `true` | Tolak presensi bila `is_mock = true`. |
| `ATTENDANCE_MAX_ACCURACY` | `100` | Akurasi GPS terburuk (meter) yang diterima. |
| `ATTENDANCE_RADIUS_BUFFER` | `0` | Toleransi tambahan di luar radius (meter) untuk drift GPS. |
| `ATTENDANCE_BIND_DEVICE` | `false` | Wajibkan `device_id` cocok dengan yang terdaftar (1 akun = 1 HP). |

## Lampiran C — Ringkasan Endpoint
| Method | Path | Auth | Fungsi |
|---|---|---|---|
| POST | `/employee/auth/login` | — | Login, dapat token |
| GET | `/employee/auth/me` | ✓ | Profil karyawan |
| POST | `/employee/auth/logout` | ✓ | Logout |
| GET | `/employee/attendance/today` | ✓ | Status presensi hari ini |
| POST | `/employee/attendance/check-in` | ✓ | Check-in + GPS |
| POST | `/employee/attendance/check-out` | ✓ | Check-out + GPS |
| GET | `/employee/attendance/history` | ✓ | Riwayat presensi |
| GET | `/employee/schedule` | ✓ | Jadwal shift mendatang |
