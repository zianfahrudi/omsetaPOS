# Deploy omsetaPOS via Docker (di VPS bersama app lain)

Setup ini jalan **berdampingan** dengan container lain di VPS. App + worker + scheduler
+ MySQL semua di container. Container web hanya bind ke `127.0.0.1:18080`; nginx host
yang ekspos ke publik via domain `omseta.ziandev.site` (reverse proxy + HTTPS).

```
[internet] → nginx host (443, domain) → 127.0.0.1:18080 → container app (nginx+php-fpm)
                                                              ├── container queue
                                                              ├── container scheduler
                                                              └── container db (mysql 8.4)
```

## Prasyarat di VPS
- Docker + Docker Compose plugin (`docker compose version`).
- nginx host sudah ada (dipakai app lain). DNS `omseta.ziandev.site` → IP VPS.

## Langkah

1. **Clone projek** ke VPS, mis. `/opt/omsetapos`:
   ```bash
   git clone <repo> /opt/omsetapos && cd /opt/omsetapos
   ```

2. **Siapkan `.env`** dari template docker:
   ```bash
   cp .env.docker.example .env
   ```
   Edit `.env`:
   - `DB_PASSWORD` & `DB_ROOT_PASSWORD` → password kuat.
   - `DB_HOST=db` (jangan diubah; itu nama service).
   - `APP_URL=https://omseta.ziandev.site`.

3. **Generate APP_KEY** (sekali) lalu tempel ke `.env`:
   ```bash
   docker run --rm -v "$PWD":/app -w /app php:8.3-cli php -r \
     'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
   ```
   Isikan hasilnya ke `APP_KEY=` di `.env`. (Kalau dikosongkan, container akan
   generate otomatis saat start.)

4. **Build & jalankan**:
   ```bash
   docker compose up -d --build
   ```
   Container `app` otomatis: tunggu DB → `migrate --force` → `storage:link` →
   cache config/route/view.

5. **Seed awal** (hanya pertama kali, kalau perlu data dasar):
   ```bash
   docker compose exec app php artisan db:seed --force
   ```
   Data wilayah (provinsi/kabupaten/kecamatan) butuh internet saat seeding:
   ```bash
   docker compose exec app php artisan db:seed --class=WilayahSeeder --force
   ```

6. **Reverse proxy nginx host**:
   ```bash
   sudo cp docker/nginx-host-omseta.conf.example /etc/nginx/conf.d/omseta.ziandev.site.conf
   sudo nginx -t && sudo systemctl reload nginx
   sudo certbot --nginx -d omseta.ziandev.site      # pasang HTTPS
   ```

Buka https://omseta.ziandev.site → selesai.

## Operasional

| Tindakan | Perintah |
|---|---|
| Lihat status | `docker compose ps` |
| Log app | `docker compose logs -f app` |
| Update versi | `git pull && docker compose up -d --build` |
| Migrasi manual | `docker compose exec app php artisan migrate --force` |
| Tinker | `docker compose exec app php artisan tinker` |
| Backup DB | `docker compose exec db mysqldump -uroot -p"$DB_ROOT_PASSWORD" omsetapos > backup.sql` |
| Stop | `docker compose down` (data DB & storage tetap di volume) |

## Catatan
- Port `18080` (host) dipilih karena `8080` sudah dipakai app lain (Dart Frog).
  Bind ke localhost saja. Kalau tetap bentrok, ganti di `docker-compose.yml`
  (mis. `127.0.0.1:18090:80`) + sesuaikan `proxy_pass` nginx host.
- Migrasi **tidak pernah** `migrate:fresh` (aman, tak hapus data).
- Data persist di volume `dbdata` (MySQL) & `storage` (upload/log). Hapus volume = hapus data.
- Worker queue & scheduler jalan sebagai container terpisah, restart otomatis.
