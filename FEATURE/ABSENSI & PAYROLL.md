# PRD - Modul Absensi & Payroll Berbasis Shift

## Ringkasan

Membangun modul Absensi & Payroll yang terintegrasi untuk menghitung gaji karyawan secara otomatis berdasarkan:

* Jadwal Shift
* Jam Masuk
* Jam Pulang
* Total Jam Kerja
* Tarif Per Jam
* Bonus
* Kasbon
* Arisan
* Tabungan

Sistem akan menggantikan proses perhitungan manual menggunakan Excel dan menghasilkan payroll secara otomatis setiap periode.

---

# Tujuan

## Problem

Saat ini proses penggajian dilakukan secara manual menggunakan Excel sehingga:

* Rentan kesalahan perhitungan
* Sulit melakukan rekap bulanan
* Sulit melacak histori absensi
* Membutuhkan waktu lama saat payroll

## Goal

* Mengotomatisasi perhitungan jam kerja
* Mengotomatisasi perhitungan gaji
* Mengelola jadwal shift karyawan
* Menghasilkan slip gaji secara otomatis
* Menyediakan laporan payroll per periode

---

# User Role

## Admin

Dapat:

* Mengelola karyawan
* Mengelola shift
* Menentukan jadwal kerja
* Mengelola absensi
* Mengelola bonus
* Mengelola kasbon
* Mengelola arisan
* Mengelola tabungan
* Generate payroll
* Melihat laporan payroll

## Karyawan

Dapat:

* Melihat jadwal kerja
* Melakukan check-in
* Melakukan check-out
* Melihat riwayat absensi
* Melihat slip gaji

---

# Modul 1 - Master Karyawan

## Data Karyawan

| Field       | Tipe    |
| ----------- | ------- |
| id          | UUID    |
| name        | String  |
| phone       | String  |
| position    | String  |
| hourly_rate | Decimal |
| join_date   | Date    |
| is_active   | Boolean |

### Contoh

| Nama        | Tarif Per Jam |
| ----------- | ------------- |
| Abdurrahman | Rp12.142      |

---

# Modul 2 - Master Shift

## Shift Pagi

```text
09:00 - 12:00
```

Durasi:

```text
3 Jam
```

## Shift Siang

```text
14:00 - 18:00
```

Durasi:

```text
4 Jam
```

Total Normal:

```text
7 Jam
```

## Database

### shifts

| Field          | Tipe    |
| -------------- | ------- |
| id             | UUID    |
| name           | String  |
| start_time     | Time    |
| end_time       | Time    |
| duration_hours | Decimal |

---

# Modul 3 - Jadwal Shift

Admin menentukan shift setiap karyawan.

## Contoh

| Hari   | Shift        |
| ------ | ------------ |
| Senin  | Pagi + Siang |
| Selasa | Pagi + Siang |
| Rabu   | Pagi + Siang |

## Database

### employee_schedules

| Field       | Tipe |
| ----------- | ---- |
| id          | UUID |
| employee_id | UUID |
| shift_id    | UUID |
| work_date   | Date |

---

# Modul 4 - Absensi

## Check In

Contoh:

```text
09:02
```

## Check Out

Contoh:

```text
12:01
```

## Database

### attendances

| Field         | Tipe     |
| ------------- | -------- |
| id            | UUID     |
| employee_id   | UUID     |
| shift_id      | UUID     |
| work_date     | Date     |
| check_in      | Datetime |
| check_out     | Datetime |
| total_minutes | Integer  |
| total_hours   | Decimal  |
| status        | Enum     |

### Status

```text
Present
Late
Absent
Leave
Sick
Holiday
```

---

# Modul 5 - Perhitungan Jam Kerja

## Formula

```text
Total Jam Kerja =
(Check Out - Check In) dalam menit / 60
```

### Contoh

```text
Check In : 09:00
Check Out : 12:00
```

Hasil:

```text
3 Jam
```

### Contoh 2

```text
Check In : 14:00
Check Out : 19:20
```

Perhitungan:

```text
320 Menit / 60
```

Hasil:

```text
5.33 Jam
```

---

# Modul 6 - Perhitungan Gaji Harian

## Formula

```text
Gaji Harian =
Total Jam Kerja × Tarif Per Jam
```

### Contoh

```text
8.2 Jam
×
Rp12.142
```

Hasil:

```text
Rp99.564
```

---

# Modul 7 - Bonus

Admin dapat memberikan bonus kepada karyawan.

## Jenis Bonus

* Bonus Kehadiran
* Bonus Target
* Bonus Lembur
* Bonus Prestasi

## Database

### employee_bonus

| Field       | Tipe    |
| ----------- | ------- |
| id          | UUID    |
| employee_id | UUID    |
| date        | Date    |
| amount      | Decimal |
| description | String  |

---

# Modul 8 - Kasbon

Digunakan untuk mencatat pinjaman karyawan.

## Database

### employee_loans

| Field       | Tipe    |
| ----------- | ------- |
| id          | UUID    |
| employee_id | UUID    |
| amount      | Decimal |
| date        | Date    |
| description | String  |
| status      | Enum    |

### Status

```text
Pending
Paid
Deducted
```

Payroll akan otomatis memotong kasbon yang belum lunas.

---

# Modul 9 - Arisan

Potongan tetap setiap periode payroll.

## Database

### employee_arisan

| Field       | Tipe    |
| ----------- | ------- |
| id          | UUID    |
| employee_id | UUID    |
| amount      | Decimal |
| active      | Boolean |

---

# Modul 10 - Tabungan Karyawan

Potongan sukarela yang disimpan perusahaan.

## Database

### employee_savings

| Field       | Tipe    |
| ----------- | ------- |
| id          | UUID    |
| employee_id | UUID    |
| amount      | Decimal |
| active      | Boolean |

---

# Modul 11 - Payroll

Admin memilih periode payroll.

Contoh:

```text
1 Juni 2026 - 30 Juni 2026
```

Sistem menghitung seluruh transaksi dalam periode tersebut.

---

## Formula Payroll

```text
Take Home Pay =
Total Gaji
+ Total Bonus
- Kasbon
- Arisan
- Tabungan
```

---

## Database

### payrolls

| Field         | Tipe    |
| ------------- | ------- |
| id            | UUID    |
| employee_id   | UUID    |
| period_start  | Date    |
| period_end    | Date    |
| total_hours   | Decimal |
| gross_salary  | Decimal |
| total_bonus   | Decimal |
| total_loan    | Decimal |
| total_arisan  | Decimal |
| total_savings | Decimal |
| take_home_pay | Decimal |
| status        | Enum    |

### Status

```text
Draft
Approved
Paid
```

---

# Slip Gaji

## Informasi

```text
Nama Karyawan
Periode Payroll
```

## Pendapatan

```text
Total Jam Kerja
Tarif Per Jam
Gaji Kotor
Bonus
```

## Potongan

```text
Kasbon
Arisan
Tabungan
```

## Hasil Akhir

```text
Take Home Pay
```

---

# Dashboard Payroll

## KPI

### Total Karyawan

```text
Jumlah karyawan aktif
```

### Total Jam Kerja

```text
Akumulasi jam kerja bulan berjalan
```

### Total Payroll

```text
Total pengeluaran gaji
```

### Total Bonus

```text
Akumulasi bonus
```

### Total Kasbon

```text
Kasbon yang masih berjalan
```

---

# Approval Jam Kerja

## Problem

Dalam praktiknya sering terjadi:

* Pulang lebih cepat
* Tidak masuk shift kedua
* Lembur
* Setengah hari

Sehingga jam kerja yang dibayar tidak selalu sama dengan hasil absensi.

## Solusi

Tambahkan field:

```text
paid_hours
```

Flow:

```text
Check In
↓
Check Out
↓
Sistem Hitung Jam Kerja
↓
Admin Review
↓
Paid Hours Final
↓
Payroll
```

Formula Payroll menggunakan:

```text
Paid Hours
```

bukan

```text
Attendance Hours
```

agar lebih fleksibel mengikuti kondisi lapangan.

---

# Future Enhancement

## GPS Attendance

Validasi lokasi saat check-in dan check-out.

## Face Verification

Verifikasi wajah saat absensi.

## Overtime

Perhitungan lembur otomatis.

## Payroll Export

* PDF
* Excel

## WhatsApp Slip Gaji

Kirim slip gaji langsung ke WhatsApp karyawan.

## Employee Self Service

Karyawan dapat melihat:

* Jadwal
* Absensi
* Slip Gaji
* Histori Kasbon
* Histori Tabungan
