# Modul 12 - Arisan Karyawan

## Ringkasan

Modul Arisan Karyawan digunakan untuk mengelola kegiatan arisan internal perusahaan.

Karyawan dapat bergabung ke dalam kelompok arisan dan sistem akan otomatis melakukan potongan payroll setiap periode sesuai nominal iuran yang telah ditentukan.

Pemenang arisan akan menerima dana arisan dan tercatat dalam sistem.

---

# Tujuan

## Problem

Saat ini arisan dikelola secara manual sehingga:

* Sulit melacak anggota
* Sulit melacak pembayaran iuran
* Sulit menentukan giliran pemenang
* Rentan kesalahan pencatatan

## Goal

* Mengelola anggota arisan
* Mengelola periode arisan
* Mengelola iuran otomatis melalui payroll
* Mengelola pemenang arisan
* Menyediakan histori arisan

---

# Konsep Bisnis

Contoh:

Jumlah Peserta:

```text
10 Orang
```

Iuran:

```text
Rp100.000 / Bulan
```

Total Dana Arisan:

```text
Rp1.000.000
```

Setiap bulan:

* Payroll memotong Rp100.000
* Dana terkumpul Rp1.000.000
* Satu peserta mendapatkan dana arisan

---

# Master Kelompok Arisan

## Data

### arisan_groups

| Field               | Tipe    |
| ------------------- | ------- |
| id                  | UUID    |
| name                | String  |
| contribution_amount | Decimal |
| start_date          | Date    |
| end_date            | Date    |
| total_members       | Integer |
| draw_method         | Enum    |
| status              | Enum    |

---

## Draw Method

```text
Random
Manual
Queue
```

### Random

Pemenang dipilih secara acak.

### Manual

Pemenang ditentukan admin.

### Queue

Pemenang berdasarkan urutan anggota.

---

## Status

```text
Draft
Active
Completed
Cancelled
```

---

# Pendaftaran Peserta

## Data

### arisan_members

| Field           | Tipe    |
| --------------- | ------- |
| id              | UUID    |
| arisan_group_id | UUID    |
| employee_id     | UUID    |
| join_date       | Date    |
| sequence_number | Integer |
| status          | Enum    |

---

## Status

```text
Active
Completed
Withdrawn
```

---

# Periode Arisan

Setiap bulan sistem membuat periode arisan.

### arisan_periods

| Field              | Tipe    |
| ------------------ | ------- |
| id                 | UUID    |
| arisan_group_id    | UUID    |
| period_no          | Integer |
| period_date        | Date    |
| total_collected    | Decimal |
| winner_employee_id | UUID    |
| status             | Enum    |

---

## Status

```text
Pending
Completed
```

---

# Potongan Payroll Otomatis

Saat payroll dijalankan:

Sistem mengecek:

```text
Apakah karyawan menjadi anggota arisan aktif?
```

Jika ya:

```text
Potong otomatis sebesar nominal iuran
```

Contoh:

```text
Gaji Kotor : Rp3.500.000
Iuran Arisan : Rp100.000

Take Home Pay:
Rp3.400.000
```

---

# Pencatatan Iuran

### arisan_contributions

| Field             | Tipe    |
| ----------------- | ------- |
| id                | UUID    |
| arisan_period_id  | UUID    |
| employee_id       | UUID    |
| payroll_id        | UUID    |
| amount            | Decimal |
| contribution_date | Date    |
| status            | Enum    |

---

## Status

```text
Paid
Pending
Cancelled
```

---

# Penentuan Pemenang

Admin dapat memilih:

## Otomatis

Sistem menentukan pemenang berdasarkan:

```text
Random
```

atau

```text
Urutan Antrian
```

## Manual

Admin memilih pemenang secara langsung.

---

# Pencairan Dana Arisan

Setelah pemenang dipilih:

Sistem membuat transaksi:

### arisan_payouts

| Field            | Tipe    |
| ---------------- | ------- |
| id               | UUID    |
| arisan_period_id | UUID    |
| employee_id      | UUID    |
| amount           | Decimal |
| payout_date      | Date    |
| notes            | Text    |

---

Contoh:

```text
Total Peserta : 10
Iuran : Rp100.000

Dana Terkumpul :
Rp1.000.000

Pemenang :
Abdurrahman

Dana Dicairkan :
Rp1.000.000
```

---

# Dashboard Arisan

## KPI

### Arisan Aktif

```text
Jumlah kelompok arisan yang berjalan
```

### Total Peserta

```text
Jumlah seluruh anggota arisan
```

### Dana Terkumpul

```text
Akumulasi dana arisan
```

### Periode Berjalan

```text
Periode aktif saat ini
```

---

# Halaman Karyawan

Karyawan dapat melihat:

## Informasi Arisan

```text
Nama Kelompok
Nominal Iuran
Jumlah Peserta
Periode Saat Ini
```

## Status Kepesertaan

```text
Aktif / Selesai
```

## Riwayat Iuran

```text
Periode
Nominal
Status
```

## Riwayat Menang

```text
Tanggal Menang
Nominal Diterima
```

---

# Business Rules

## Rule 1

Peserta yang sudah menang tidak dapat menang lagi dalam kelompok yang sama.

## Rule 2

Karyawan yang keluar dari perusahaan otomatis dinonaktifkan dari arisan.

## Rule 3

Iuran arisan dipotong melalui payroll.

## Rule 4

Peserta yang memiliki status Withdrawn tidak ikut pengundian.

## Rule 5

Dana arisan hanya dapat dicairkan setelah seluruh iuran periode terkumpul.

---

# Future Enhancement

## Multiple Arisan Group

Karyawan dapat mengikuti lebih dari satu kelompok arisan.

## Lucky Draw

Pengundian otomatis dengan animasi.

## WhatsApp Notification

Notifikasi:

* Potongan arisan berhasil
* Menjadi pemenang arisan
* Jadwal pengundian

## Digital Signature

Konfirmasi penerimaan dana arisan secara digital.
