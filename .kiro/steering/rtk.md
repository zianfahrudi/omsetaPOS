---
inclusion: always
---

# RTK (Rust Token Killer) — kompres output command

`rtk` terpasang di mesin (`/opt/homebrew/bin/rtk`). Dia mem-filter & memampatkan
output command sebelum masuk konteks (hemat 60-90% pada output noisy).

## Aturan

Saat menjalankan command shell yang outputnya bisa panjang/noisy, **prefix dengan `rtk`**
bila subcommand-nya didukung. Untuk output pendek (mis. `pwd`, `echo`) tidak perlu.

## Pemetaan command untuk projek ini (Laravel/PHP + git)

| Tujuan | Pakai |
|---|---|
| Status/diff/log git | `rtk git status`, `rtk git diff`, `rtk git log --oneline -20` |
| Baca file (terfilter) | `rtk read <file>` |
| List / tree direktori | `rtk ls`, `rtk tree` |
| Cari file | `rtk find . -name "*.php"` |
| Grep | `rtk grep <pola>` |
| Jalankan test, tampilkan gagal saja | `rtk test php artisan test` |
| Command apa pun, error/warning saja | `rtk err composer install` |
| JSON dari API/curl | `rtk curl <url>` |
| Pasang/jalankan npm | `rtk npm run build` |
| Passthrough penuh (output mentah, tetap tercatat) | `rtk proxy <cmd>` |
| Command mentah tanpa filter/track | `rtk run <cmd>` |

## Catatan

- Tidak ada filter khusus `composer`/`artisan`; bungkus dengan `rtk err <cmd>`
  atau `rtk test <cmd>` agar tetap terkompres.
- Kalau hasil filter terasa kurang (butuh output penuh), ulangi dengan `rtk proxy <cmd>`.
- Cek penghematan kapan saja: `rtk gain`.
- Tetap utamakan tool internal Kiro (read_file, grep_search, file_search) untuk
  baca/cari di dalam projek — itu sudah hemat. `rtk` untuk hal yang memang lewat shell.
