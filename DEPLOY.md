# Deploy omsetaPOS — dari Mac lokal sampai VPS

Satu panduan utuh: tes di Mac → push ke GitHub → jalan di VPS Ubuntu pada
domain **`app.omseta.web.id`**.

Stack: Docker (app + queue + scheduler + MySQL) + nginx host (reverse proxy) + HTTPS Let's Encrypt.

```
[internet] → nginx host :443 (app.omseta.web.id) → 127.0.0.1:18080 → container app (nginx+php-fpm)
                                                                        ├── container queue
                                                                        ├── container scheduler
                                                                        └── container db (mysql 8.4, volume)
```

Repo: `github.com/zianfahrudi/omsetaPOS` (branch `master`).
Alur update: push ke GitHub → di VPS `git pull` → `docker compose up -d --build`.

---

# BAGIAN A — DI MAC (lokal, opsional tapi disarankan)

Tujuan: pastikan container build & jalan sebelum naik ke VPS. Pakai OrbStack/Docker Desktop.

### A1. Cek Docker
```bash
docker version && docker compose version
```

### A2. `.env` untuk tes lokal (HTTP, bukan HTTPS)
```bash
cp .env.production.example .env
```
Edit `.env` agar cocok lokal:
```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:18080
SESSION_SECURE_COOKIE=false
DB_PASSWORD=rahasia123
DB_ROOT_PASSWORD=rootrahasia123
SEED_PASSWORD=rahasia123
```
> Di lokal `SESSION_SECURE_COOKIE=false`. Kalau `true`, cookie minta HTTPS → login gagal di `http://localhost`.

Generate `APP_KEY`, tempel ke baris `APP_KEY=`:
```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

### A3. Build, jalankan, seed
```bash
docker compose up -d --build
docker compose ps
docker compose logs -f app          # tunggu migrate + cache, Ctrl+C keluar
docker compose exec app php artisan db:seed --force
```
Buka **http://localhost:18080** → harus tampil login.

Stop tes (data aman di volume):
```bash
docker compose down
```
> Jangan commit `.env` (sudah di-`.gitignore`).

### A4. Push ke GitHub
```bash
git add -A
git commit -m "Deploy config"
git push origin master
```

---

# BAGIAN B — DI VPS (Ubuntu, produksi)

## B0. DNS (lakukan dulu, biar sempat propagasi)
Di pengelola DNS `omseta.web.id`, buat **A record**:

| Type | Name  | Value           |
|------|-------|-----------------|
| A    | `app` | `IP_PUBLIK_VPS` |

Cek dari laptop:
```bash
dig +short app.omseta.web.id        # harus keluar IP VPS
```

## B1. Login & update server
```bash
ssh root@IP_VPS                     # atau user sudoer
sudo apt update && sudo apt upgrade -y
```

### (Disarankan) user non-root
```bash
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```
Lanjut login sebagai `deploy`.

### Swap 2 GB (bila RAM ≤ 2 GB — bantu saat build)
```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

## B2. Firewall (UFW)
```bash
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'         # buka 80 + 443
sudo ufw --force enable
```
> Port 18080 tidak dibuka ke publik — container bind hanya ke `127.0.0.1`.

## B3. Install Docker + nginx + certbot
```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER && newgrp docker
docker version && docker compose version

sudo apt install -y nginx certbot python3-certbot-nginx
sudo systemctl enable --now nginx
```

## B4. Ambil kode
```bash
sudo mkdir -p /opt/omsetapos && sudo chown $USER:$USER /opt/omsetapos
git clone https://github.com/zianfahrudi/omsetaPOS.git /opt/omsetapos
cd /opt/omsetapos
```
> Repo private? Pakai deploy key SSH atau Personal Access Token:
> ```bash
> # SSH deploy key (read-only):
> ssh-keygen -t ed25519 -C "vps-omseta" -f ~/.ssh/omseta_deploy -N ""
> cat ~/.ssh/omseta_deploy.pub   # tempel ke GitHub → repo → Settings → Deploy keys
> printf 'Host github-omseta\n  HostName github.com\n  User git\n  IdentityFile ~/.ssh/omseta_deploy\n' >> ~/.ssh/config
> git clone github-omseta:zianfahrudi/omsetaPOS.git /opt/omsetapos
> # atau HTTPS token:
> git clone https://<TOKEN>@github.com/zianfahrudi/omsetaPOS.git /opt/omsetapos
> ```

## B5. `.env` produksi
```bash
cp .env.production.example .env
nano .env
```
Wajib diisi (jangan default):
- `DB_PASSWORD`, `DB_ROOT_PASSWORD` → password kuat, berbeda.
- `SEED_PASSWORD` → password login awal superuser (kuat, bukan `password`).
- `SEED_SUPERUSER_EMAIL` → email login superuser.
- Sudah benar: `APP_URL=https://app.omseta.web.id`, `SESSION_SECURE_COOKIE=true`.

Generate `APP_KEY`, tempel ke `APP_KEY=`:
```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

## B6. Build & jalankan
```bash
docker compose up -d --build
docker compose ps
docker compose logs -f app          # tunggu migrate --force + cache selesai
```
Entrypoint otomatis: tunggu DB → `migrate --force` → `storage:link` → cache config/route/view.

### Seed awal (sekali)
```bash
docker compose exec app php artisan db:seed --force
# data wilayah (provinsi/kab/kecamatan, butuh internet, opsional):
docker compose exec app php artisan db:seed --class=WilayahSeeder --force
```
> Di produksi hanya akun **superuser** dibuat (pakai `SEED_SUPERUSER_EMAIL` + `SEED_PASSWORD`).
> User lain dibuat lewat menu **Pengguna**.

### Cek dari dalam VPS
```bash
curl -I http://127.0.0.1:18080      # harus 200 / 302
```

## B7. Reverse proxy + HTTPS
```bash
sudo cp docker/nginx-host-app-omseta.conf.example /etc/nginx/sites-available/app.omseta.web.id
sudo ln -s /etc/nginx/sites-available/app.omseta.web.id /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d app.omseta.web.id      # DNS harus sudah mengarah ke VPS
```
Certbot menambah blok 443 + redirect 80→443 + auto-renew.

Buka **https://app.omseta.web.id** → login pakai `SEED_SUPERUSER_EMAIL` + `SEED_PASSWORD`. 🎉

---

# UPDATE versi (rutin)

Di Mac: commit + `git push origin master`.
Di VPS:
```bash
cd /opt/omsetapos
git pull
docker compose up -d --build        # migrate --force jalan otomatis, data aman
```

---

# Operasional (VPS)

| Tindakan | Perintah |
|---|---|
| Status | `docker compose ps` |
| Log app | `docker compose logs -f app` |
| Restart | `docker compose restart` |
| Shell app | `docker compose exec app sh` |
| Migrasi manual | `docker compose exec app php artisan migrate --force` |
| Bersih cache | `docker compose exec app php artisan optimize:clear` |
| Backup DB | `docker compose exec db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" omsetapos' > backup_$(date +%F).sql` |
| Stop (data aman) | `docker compose down` |

### Backup DB otomatis harian (cron)
```bash
mkdir -p /opt/omsetapos/backups
crontab -e
# tambah:
0 2 * * * cd /opt/omsetapos && docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" omsetapos' > /opt/omsetapos/backups/omsetapos_$(date +\%F).sql 2>/dev/null
```

---

# Troubleshooting

- **502 Bad Gateway** → container belum siap / port salah. `docker compose ps` lalu `curl -I http://127.0.0.1:18080`.
- **Login gagal / redirect loop** → cek `APP_URL=https://app.omseta.web.id` & `SESSION_SECURE_COOKIE=true`, lalu `docker compose restart`.
- **CSS/JS rusak** → `docker compose exec app php artisan optimize:clear` lalu `docker compose restart`.
- **certbot gagal** → pastikan `dig +short app.omseta.web.id` = IP VPS & port 80 terbuka (UFW `Nginx Full`).
- **Build kehabisan memori** → aktifkan swap (B1).
- **Data** persist di volume `dbdata` (MySQL) & `storage` (upload/log). `docker compose down` aman; **jangan** `down -v` (hapus volume = hapus data).
- Migrasi **tidak pernah** `migrate:fresh` — aman, tak menghapus data.

---

# Catatan keamanan
- Container app bind hanya ke `127.0.0.1:18080`; MySQL hanya di jaringan internal Docker (tak terekspos host).
- Ganti semua password default di `.env`. Jangan commit `.env` (sudah di-`.gitignore`).
- Reverse proxy sudah dipercaya app (`trustProxies`) → HTTPS terdeteksi benar.
- Pertimbangkan matikan login SSH root & pakai key-only auth.
