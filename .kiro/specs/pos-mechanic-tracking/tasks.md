# Implementation Plan: POS Mechanic Tracking

## Overview

Implementasi penautan petugas (mekanik/salesman) per item `SaleItem` di POS, beserta laporan rekap performa per periode. Pendekatan inkremental: migrasi kolom → model → domain logic `CheckoutService` → validasi request (web & API) → resource/response → endpoint & UI kasir → laporan performa V2 → pengujian (feature + property-based) → verifikasi.

Bahasa & stack: PHP 8.3 / Laravel 13, PHPUnit 12 dengan `RefreshDatabase` di SQLite. Tidak ada paket PBT khusus di proyek, jadi property test memakai model factory + perulangan acak `fakerphp/faker` (minimum 100 iterasi per properti), bukan implementasi PBT dari nol.

Tiap property test ditandai komentar: `// Feature: pos-mechanic-tracking, Property {n}: {teks properti}`.

## Tasks

- [x] 1. Migrasi kolom & model dasar penautan petugas
  - [x] 1.1 Buat migrasi `add_employee_id_to_sale_items_table`
    - Tambah kolom `employee_id` nullable `after('product_type')`, `foreignId(...)->constrained('employees')->nullOnDelete()`
    - Hanya menambah satu kolom; tidak mengubah kolom lain
    - _Requirements: 2.1, 6.1_
    - _Design: Components 1, Data Models (tabel sale_items)_
  - [x] 1.2 Ubah model `App\Models\SaleItem`
    - Tambah `'employee_id'` ke fillable; tambah relasi `employee(): BelongsTo` ke `Employee` (nullable)
    - _Requirements: 2.1, 6.3_
    - _Design: Components 2_
  - [x] 1.3 Ubah model `App\Models\Employee`
    - Tambah relasi `handledSaleItems(): HasMany` ke `SaleItem`
    - _Requirements: 5.1_
    - _Design: Components 3_
  - [x]* 1.4 Smoke test migrasi & relasi
    - Migrasi jalan; SaleItem dapat dibuat dengan `employee_id = null` dan dengan employee; relasi `employee` & `handledSaleItems` ter-resolve
    - Verifikasi item tanpa petugas tidak menimbulkan error (backward compat)
    - _Requirements: 6.1, 6.3, 2.6_

- [x] 2. Factory pendukung pengujian
  - [x] 2.1 Buat model factory yang dibutuhkan test
    - Buat/lengkapi factory: `CompanyFactory`, `StoreFactory`, `EmployeeFactory` (state `inactive`, `forCompany`), `ProductFactory` (state `service`/`goods`), `SaleFactory` (state `completed`, atribut `paid_at`/`status`/`store_id`), `SaleItemFactory` (atribut `product_type`, `line_total`, `employee_id` nullable)
    - Pastikan relasi factory konsisten dengan rantai `SaleItem → Sale → Store → company_id` dan `Employee.company_id`
    - _Requirements: 1.1, 2.1, 5.1, 5.5_
    - _Design: Testing Strategy (generator via model factories)_

- [x] 3. CheckoutService: grouping, validasi, persistensi petugas
  - [x] 3.1 Ubah grouping cart & resolusi produk di `CheckoutService::checkout()`
    - Ganti `groupBy('product_id')` menjadi grouping kombinasi `product_id.'-'.($employee_id ?? 'null')`
    - Bawa `employee_id` per grup; agregasi qty hanya antar baris berkombinasi identik
    - Kumpulkan `product_id` unik untuk `Product::whereIn(...)`; validasi "produk ditemukan" terhadap jumlah `product_id` unik (bukan jumlah grup)
    - Validasi stok: jumlahkan qty per `product_id` lintas semua grup sebelum dibandingkan stok
    - Pastikan grand total transaksi tidak berubah oleh pemecahan baris
    - _Requirements: 2.2, 2.4_
    - _Design: Components 4 (poin 1–3)_
  - [x] 3.2 Tambah validasi petugas domain di `CheckoutService::checkout()`
    - Untuk tiap grup dengan `employee_id` non-null, validasi terhadap `Employee` `is_active = true` dan `company_id = store->company_id`
    - Bila tidak valid, lempar `InvalidArgumentException('Petugas tidak valid untuk toko ini.')` (dalam `DB::transaction` → rollback penuh)
    - `employee_id` null dilewati tanpa error (opsional)
    - _Requirements: 3.3, 4.3_
    - _Design: Components 4 (poin 4), Error Handling_
  - [x] 3.3 Persist `employee_id` per item & eager load
    - Sertakan `'employee_id' => $cartItem['employee_id']` pada `$sale->items()->create([...])`
    - Kembalikan `$sale->load(['items.employee', 'cashier', 'store', 'payments'])`
    - _Requirements: 2.1, 3.1, 3.2, 4.1, 4.2_
    - _Design: Components 4 (poin 5–6)_
  - [x]* 3.4 Property test — Property 1 (grouping cart)
    - **Property 1: Grouping cart per (product_id, employee_id)**
    - **Validates: Requirements 2.2, 2.4**
    - Generate ≥100 daftar item acak (product_id dari pool, employee_id null/valid acak, qty acak); assert jumlah SaleItem = jumlah kombinasi unik, qty teragregasi benar, grand total sama dengan checkout tanpa pemecahan
  - [x]* 3.5 Property test — Property 2 (persistensi per item, jalur service)
    - **Property 2: Persistensi & pemetaan petugas per item**
    - **Validates: Requirements 2.1, 3.1, 3.2, 4.1, 4.2**
    - Generate ≥100 cart campuran (valid + tanpa petugas); checkout via service; baca DB; cocokkan mapping `(product_id, employee_id)` → item (id valid apa adanya, tanpa petugas → null)
  - [x]* 3.6 Property test — Property 3 (penolakan petugas tidak valid, jalur service)
    - **Property 3: Penolakan petugas tidak valid**
    - **Validates: Requirements 3.3, 4.3**
    - Generate ≥100 `employee_id` invalid (nonaktif, company lain, id acak tak-ada di domain valid); assert `InvalidArgumentException` dan tidak ada `Sale`/`SaleItem` tersimpan
  - [x]* 3.7 Unit test contoh — produk sama, mekanik berbeda
    - Produk sama dengan dua employee berbeda → menghasilkan 2 baris `SaleItem`; produk sama + employee sama → qty bertambah pada satu baris
    - _Requirements: 2.2_

- [x] 4. Validasi request checkout (web & API)
  - [x] 4.1 Tambah aturan validasi di `CashierController@checkout`
    - Tambah `'items.*.employee_id' => ['nullable', 'integer', 'exists:employees,id']`; teruskan `employee_id` apa adanya ke service
    - _Requirements: 2.1, 3.3_
    - _Design: Components 5 (checkout), Error Handling (validasi request layer)_
  - [x] 4.2 Tambah aturan validasi di `App\Http\Requests\Api\V1\CheckoutRequest`
    - Tambah `'items.*.employee_id' => ['nullable', 'integer', 'exists:employees,id']`
    - _Requirements: 4.1, 4.3_
    - _Design: Components 7_
  - [x]* 4.3 Feature test validasi 422 (web & API)
    - `employee_id` tidak ada di tabel → 422 (request layer); employee nonaktif/company lain → ditolak (domain, 422) tanpa Sale tersimpan
    - _Requirements: 3.3, 4.3_

- [x] 5. Resource & payload response menyertakan petugas
  - [x] 5.1 Ubah `App\Http\Resources\SaleItemResource`
    - Tambah `'employee_id'` dan `'employee_name' => $this->whenLoaded('employee', fn () => $this->employee?->name)`
    - _Requirements: 4.4, 6.3_
    - _Design: Components 8_
  - [x] 5.2 Tambah field petugas pada response `CashierController@checkout` & `salePayload()`
    - Map `items` menambah `'employee_id'` dan `'employee_name' => $item->employee?->name`
    - _Requirements: 4.4, 6.3_
    - _Design: Components 5 (response, salePayload)_
  - [x] 5.3 Eager load `items.employee` di jalur API
    - Ubah `load(['items', ...])` pada `SaleResource`/controller API menjadi `items.employee` agar `employee_name` terisi
    - _Requirements: 4.4_
    - _Design: Components 8 (catatan load)_
  - [x]* 5.4 Property test — Property 5 (representasi item menyertakan petugas)
    - **Property 5: Representasi item menyertakan petugas**
    - **Validates: Requirements 4.4, 6.3**
    - Generate ≥100 SaleItem (tertaut & tidak), render `SaleItemResource`; assert `employee_id`/`employee_name` sesuai petugas; item tanpa petugas → kedua field null tanpa error
  - [x]* 5.5 Feature test response API memuat petugas
    - Property 2 jalur API: checkout API dengan item bertaut petugas → response item memuat `employee_id` & `employee_name`
    - _Requirements: 4.1, 4.2, 4.4_

- [x] 6. Endpoint daftar petugas & route kasir
  - [x] 6.1 Tambah `CashierController@employees`
    - Query: `store_id` wajib (cek `canAccessStore`), `q` opsional; sumber `Employee where company_id = store->company_id, is_active = true`; filter `q` pada `name`/`code` LIKE; return `[{id, name, code}]` dengan limit wajar (≈50)
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 7.1_
    - _Design: Components 5 (employees)_
  - [x] 6.2 Tambah route `kasir/employees`
    - `Route::get('/employees', [CashierController::class, 'employees'])->middleware('auth')->name('employees')` di grup `kasir`
    - _Requirements: 7.1_
    - _Design: Components 6_
  - [x]* 6.3 Feature test endpoint employees
    - Hanya Employee aktif & company sama; filter `q` bekerja; daftar kosong saat tak ada employee aktif tanpa menghentikan checkout; kasir terotentikasi dapat akses
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 7.1_

- [x] 7. UI kasir: picker petugas & state cart
  - [x] 7.1 Tambah pemilih Petugas per baris cart di view kasir
    - `resources/views/cashier/*`: dropdown/search petugas per baris, sumber `GET kasir/employees?store_id=...&q=...`; bila kosong tampil kosong tanpa memblok checkout
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.3_
    - _Design: Components 9_
  - [x] 7.2 Update state cart & payload di `public/cashier/cashier.js`
    - Key cart = `productId + '-' + (employeeId ?? '')`; tiap item simpan `employee_id` + `employee_name`; produk sama mekanik beda → baris terpisah; kirim `employee_id` per item saat checkout; aksi hapus penautan mengosongkan `employee_id`
    - _Requirements: 2.1, 2.2, 2.5, 2.6_
    - _Design: Components 9, Alur Data Checkout_

- [x] 8. Laporan performa mekanik (V2)
  - [x] 8.1 Tambah `ReportController@mechanicPerformance` (V2)
    - Ikuti pola `period($request)`; `abort_unless(in_array(role, ['admin','superuser'], true), 403)`; query agregasi `SaleItem` JOIN `sales`/`stores`: `whereNotNull(employee_id)`, `company_id`, `status='completed'`, `whereBetween(sales.paid_at, [from,to])`, `product_type='service'`, `groupBy(employee_id)` → `service_count`, `service_total`, `sale_count`; sertakan nama petugas
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.2, 7.2, 7.3_
    - _Design: Components 10 (controller, query agregasi), Alur Data Laporan_
  - [x] 8.2 Tambah route laporan performa mekanik
    - `Route::get('laporan/performa-mekanik', [V2\ReportController::class, 'mechanicPerformance'])->name('reports.mechanic-performance')` di grup `v2`
    - _Requirements: 7.2_
    - _Design: Components 10 (route)_
  - [x] 8.3 Tambah entri navigasi laporan
    - `resources/views/v2/layouts/nav.blade.php` grup `Laporan`: `['v2.reports.mechanic-performance', 'Performa Mekanik']`
    - _Requirements: 7.2_
    - _Design: Components 10 (nav)_
  - [x] 8.4 Buat view `v2/reports/mechanic-performance.blade.php`
    - Form rentang tanggal + tabel rekap (petugas, service_count, service_total, sale_count); bila kosong tampil keterangan "tidak ada data"
    - _Requirements: 5.1, 5.6, 5.7_
    - _Design: Components 10 (view)_
  - [x]* 8.5 Property test — Property 4 (agregasi laporan)
    - **Property 4: Agregasi laporan performa benar**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.2, 5.7**
    - Seed ≥100 himpunan Sale/SaleItem acak (status, paid_at, employee_id null/non-null, product_type); bandingkan output query controller dengan oracle PHP murni; periode acak; item null & sale di luar periode/non-completed selalu dikecualikan; tanpa data → rekap kosong
  - [x]* 8.6 Feature test laporan & akses
    - Agregasi dataset contoh benar; item lama `employee_id` null diabaikan (6.2); ubah periode mengubah hasil (5.6); kosong tanpa data (5.7); admin/superuser dapat akses, non-admin → 403 (7.2, 7.3)
    - _Requirements: 5.1, 5.6, 5.7, 6.2, 7.2, 7.3_

- [x] 9. Checkpoint
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Verifikasi akhir
  - Jalankan `vendor/bin/pint` pada file yang diubah
  - Jalankan `php artisan test --filter=` untuk test feature & property fitur ini
  - Jalankan `php artisan migrate` (atau `migrate:fresh` di env test) untuk memastikan migrasi konsisten
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks dengan `*` bersifat opsional (test) dan dapat dilewati untuk MVP cepat; tugas implementasi inti tidak ditandai opsional.
- Property test memakai factory + perulangan acak faker (≥100 iterasi), bukan PBT dari nol; tidak ada paket PBT di `composer.json`.
- Migrasi, route, nav, dan render view murni diuji via feature/smoke test, bukan PBT (sesuai Testing Strategy design).
- Multiplisitas "maksimal satu petugas per item" (Req 2.6) dijamin skema kolom tunggal — cukup smoke test.
- Tiap task merujuk requirements & komponen design untuk traceability; checkpoint memvalidasi secara inkremental.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2", "1.3", "2.1"] },
    { "id": 1, "tasks": ["1.4", "3.1", "4.2", "5.1", "5.3", "6.2", "7.1", "7.2", "8.3"] },
    { "id": 2, "tasks": ["3.2", "4.1", "8.1"] },
    { "id": 3, "tasks": ["3.3", "5.2", "8.2", "8.4"] },
    { "id": 4, "tasks": ["6.1"] },
    { "id": 5, "tasks": ["3.4", "3.5", "3.6", "3.7", "4.3", "5.4", "5.5", "6.3", "8.5", "8.6"] }
  ]
}
```
