# Requirements Document

## Introduction

Fitur ini menambahkan pencatatan petugas pengerjaan (mekanik/salesman) pada transaksi Point of Sale (POS). Tujuan utamanya adalah mencatat siapa yang mengerjakan produk jasa (`product_type = 'service'`) di setiap transaksi, sehingga performa tiap mekanik dapat dimonitor melalui laporan rekap per periode. Data ini menjadi dasar pemberian bonus/reward di masa depan.

Fitur memanfaatkan master Karyawan (`Employee`) yang sudah ada sebagai sumber data mekanik/salesman, dan menautkan referensi petugas pada baris item penjualan (`SaleItem`). Penautan bersifat opsional agar transaksi tanpa petugas tetap valid (backward compatibility). Penautan tersedia baik melalui kasir web maupun API mobile.

### Keputusan Desain yang Diangkat (perlu konfirmasi user)

Poin-poin berikut diangkat sebagai keputusan eksplisit dalam requirements. Default sudah dipilih; mohon dikonfirmasi atau dikoreksi:

- **D1 — Granularitas penautan:** Penautan petugas dilakukan **per-ITEM jasa** (bukan per-nota), agar satu nota bengkel dapat mencatat mekanik berbeda untuk tiap jasa. (Requirement 2)
- **D2 — Konsep petugas:** Salesman (penjual barang) dan mekanik (pengerjaan jasa) **disatukan** menjadi satu konsep "Petugas" yang merujuk ke `Employee`. Tidak ada pembedaan tipe peran pada tahap ini. (Glossary, Requirement 1)
- **D3 — Kewajiban penautan:** Penautan bersifat **opsional** untuk semua item, baik `service` maupun `goods`. Tidak ada validasi wajib pada tahap ini agar backward compatibility terjaga. (Requirement 2, Requirement 6)
- **D4 — Sumber master:** Sumber data petugas adalah `Employee` yang `is_active = true` dalam `company_id` yang sama. (Requirement 1)
- **D5 — Lingkup monitoring:** Laporan hanya melakukan **pencatatan dan rekap** (jumlah jasa, total nilai jasa, jumlah transaksi). Perhitungan komisi/persentase **tidak** termasuk lingkup tahap ini. (Requirement 5)
- **D6 — Cakupan transaksi:** Hanya transaksi POS dengan status terbayar/selesai yang masuk rekap performa. (Requirement 5)

## Glossary

- **POS_System**: Sistem Point of Sale aplikasi omsetaPOS yang menangani checkout, mencakup kasir web (`CashierController`) dan API (`Api\V1\CheckoutController`).
- **Petugas**: Karyawan (`Employee`) yang ditautkan ke item penjualan sebagai pengerjaan/penjual. Mencakup mekanik (untuk jasa) maupun salesman (untuk barang). Mengacu ke `Employee` dengan `is_active = true`.
- **Employee**: Master karyawan (`App\Models\Employee`) dengan field `company_id`, `code`, `name`, `position`, `is_active`, dll.
- **Sale**: Header transaksi penjualan (`App\Models\Sale`).
- **SaleItem**: Baris item dalam transaksi (`App\Models\SaleItem`) dengan field `product_type` bernilai `goods` atau `service`.
- **Service_Item**: `SaleItem` dengan `product_type = 'service'`.
- **Checkout_Service**: `App\Services\CheckoutService` yang memproses checkout untuk web dan API.
- **Cashier**: Pengguna yang mengoperasikan kasir dan melakukan checkout.
- **Admin**: Pengguna dengan akses laporan performa petugas.
- **Mechanic_Performance_Report**: Laporan rekap performa Petugas per periode.
- **Performance_Period**: Rentang tanggal yang dipilih untuk laporan, ditentukan oleh tanggal mulai dan tanggal akhir inklusif.

## Requirements

### Requirement 1: Pemilihan Petugas dari Master Karyawan

**User Story:** Sebagai kasir, saya ingin memilih Petugas dari daftar karyawan aktif, sehingga saya dapat mencatat siapa yang mengerjakan jasa.

#### Acceptance Criteria

1. WHEN Cashier membuka pemilihan Petugas pada layar checkout, THE POS_System SHALL menampilkan daftar Employee yang memiliki `is_active = true` dan `company_id` sama dengan company transaksi.
2. THE POS_System SHALL menampilkan nama (`name`) dan kode (`code`) setiap Employee pada daftar pilihan Petugas.
3. WHEN Cashier mengetik kata kunci pada pencarian Petugas, THE POS_System SHALL menyaring daftar Petugas berdasarkan kecocokan kata kunci terhadap `name` atau `code`.
4. IF tidak terdapat Employee aktif pada company transaksi, THEN THE POS_System SHALL menampilkan daftar Petugas kosong tanpa menghentikan proses checkout.

### Requirement 2: Penautan Petugas per Item Jasa

**User Story:** Sebagai kasir di bengkel, saya ingin menautkan mekanik pada tiap item jasa, sehingga satu nota dapat mencatat mekanik berbeda untuk jasa berbeda.

#### Acceptance Criteria

1. WHEN Cashier menautkan seorang Petugas pada sebuah SaleItem, THE POS_System SHALL menyimpan referensi Petugas pada SaleItem tersebut.
2. THE POS_System SHALL mengizinkan setiap SaleItem dalam satu Sale memiliki Petugas yang berbeda.
3. WHERE sebuah SaleItem adalah Service_Item, THE POS_System SHALL menyediakan opsi penautan Petugas pada SaleItem tersebut.
4. WHERE sebuah SaleItem memiliki `product_type = 'goods'`, THE POS_System SHALL mengizinkan penautan Petugas secara opsional.
5. WHEN Cashier menghapus penautan Petugas dari sebuah SaleItem, THE POS_System SHALL mengosongkan referensi Petugas pada SaleItem tersebut.
6. THE POS_System SHALL membatasi setiap SaleItem hanya memiliki paling banyak satu Petugas yang tertaut.

### Requirement 3: Penautan Petugas Bersifat Opsional

**User Story:** Sebagai pemilik usaha, saya ingin penautan petugas bersifat opsional, sehingga transaksi tanpa petugas tetap dapat diselesaikan.

#### Acceptance Criteria

1. WHEN Cashier menyelesaikan checkout tanpa menautkan Petugas pada satu pun SaleItem, THE Checkout_Service SHALL menyelesaikan transaksi dengan referensi Petugas kosong pada seluruh SaleItem.
2. WHEN Cashier menyelesaikan checkout dengan sebagian SaleItem tertaut Petugas dan sebagian tidak, THE Checkout_Service SHALL menyimpan referensi Petugas hanya pada SaleItem yang tertaut.
3. IF referensi Petugas yang dikirim tidak merujuk pada Employee aktif dalam company transaksi, THEN THE Checkout_Service SHALL menolak checkout dan mengembalikan pesan kesalahan yang menyebutkan referensi Petugas tidak valid.

### Requirement 4: Penautan Petugas melalui API Checkout

**User Story:** Sebagai pengguna aplikasi mobile, saya ingin menyertakan petugas saat checkout via API, sehingga pencatatan mekanik konsisten antara web dan mobile.

#### Acceptance Criteria

1. WHEN permintaan checkout API menyertakan referensi Petugas pada sebuah item, THE Checkout_Service SHALL menyimpan referensi Petugas pada SaleItem terkait.
2. WHEN permintaan checkout API tidak menyertakan referensi Petugas pada sebuah item, THE Checkout_Service SHALL menyimpan SaleItem terkait dengan referensi Petugas kosong.
3. IF permintaan checkout API menyertakan referensi Petugas yang tidak merujuk pada Employee aktif dalam company transaksi, THEN THE Checkout_Service SHALL menolak permintaan dan mengembalikan respons kesalahan validasi.
4. WHEN respons checkout API dikembalikan untuk transaksi yang memiliki SaleItem tertaut Petugas, THE POS_System SHALL menyertakan referensi Petugas pada representasi setiap SaleItem tersebut.

### Requirement 5: Laporan Performa Petugas

**User Story:** Sebagai admin, saya ingin melihat rekap performa per petugas dalam satu periode, sehingga saya dapat menilai kinerja untuk pemberian bonus.

#### Acceptance Criteria

1. WHEN Admin membuka Mechanic_Performance_Report dengan Performance_Period yang dipilih, THE POS_System SHALL menampilkan satu baris rekap untuk setiap Petugas yang memiliki SaleItem tertaut dalam Performance_Period.
2. THE Mechanic_Performance_Report SHALL menampilkan jumlah Service_Item yang dikerjakan oleh setiap Petugas dalam Performance_Period.
3. THE Mechanic_Performance_Report SHALL menampilkan total nilai (`line_total`) Service_Item yang dikerjakan oleh setiap Petugas dalam Performance_Period.
4. THE Mechanic_Performance_Report SHALL menampilkan jumlah Sale berbeda yang melibatkan setiap Petugas dalam Performance_Period.
5. THE Mechanic_Performance_Report SHALL hanya menyertakan SaleItem dari Sale berstatus terbayar atau selesai.
6. WHEN Admin mengubah Performance_Period, THE POS_System SHALL menghitung ulang rekap berdasarkan Performance_Period yang baru.
7. IF tidak terdapat SaleItem tertaut Petugas dalam Performance_Period, THEN THE POS_System SHALL menampilkan laporan kosong dengan keterangan tidak ada data.

### Requirement 6: Kompatibilitas Data Transaksi Lama

**User Story:** Sebagai pemilik usaha, saya ingin transaksi lama tetap valid setelah fitur ditambahkan, sehingga riwayat penjualan tidak rusak.

#### Acceptance Criteria

1. THE POS_System SHALL memperlakukan SaleItem yang dibuat sebelum fitur ini sebagai SaleItem dengan referensi Petugas kosong.
2. WHEN Mechanic_Performance_Report dihitung, THE POS_System SHALL mengabaikan SaleItem dengan referensi Petugas kosong dari rekap per Petugas.
3. WHEN sebuah Sale lama ditampilkan, THE POS_System SHALL menampilkan SaleItem tanpa referensi Petugas tanpa menimbulkan kesalahan.

### Requirement 7: Hak Akses

**User Story:** Sebagai pemilik usaha, saya ingin akses yang sesuai peran, sehingga kasir hanya menautkan petugas dan admin melihat laporan.

#### Acceptance Criteria

1. WHERE pengguna berperan Cashier, THE POS_System SHALL menyediakan kemampuan menautkan Petugas pada SaleItem saat checkout.
2. WHERE pengguna berperan Admin, THE POS_System SHALL menyediakan akses ke Mechanic_Performance_Report.
3. IF pengguna tanpa peran Admin mencoba mengakses Mechanic_Performance_Report, THEN THE POS_System SHALL menolak akses.
