# Tutorial Deploy omsetaPOS — Langkah per Terminal

Dua tempat kerja:
- **LOCAL** = Mac kamu (Docker pakai OrbStack). Untuk tes + push kode.
- **VPS** = server produksi. Untuk jalanin beneran di `omseta.ziandev.site`.

Repo: `github.com/zianfahrudi/omsetaPOS` (branch `master`).
Alur: kode di-push ke GitHub → di VPS `git pull` → `docker compose up -d --build`.

---

## BAGIAN A — DI LOCAL (Mac + OrbStack)

### A1. Pastikan OrbStack jalan
Buka app **OrbStack** (sebelumnya daemon mati). Cek:
```bash
docker version
docker compose version
```
Keduanya keluar versi = siap.

### A2. (Opsional tapi disarankan) Tes container di local dulu
Bikin `.env` khusus tes lokal (HTTP, bukan HTTPS):
```bash
cp .env.docker.example .env
```
Edit `.env` untuk lokal:
```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:18080
SESSION_SECURE_COOKIE=false
DB_PASSWORD=rahasia123
DB_ROOT_PASSWORD=rootrahasia123
```
> Penting: di lokal `SESSION_SECURE_COOKIE=false`. Kalau `true`, cookie minta HTTPS
> dan login gagal di `http://localhost`.

Generate APP_KEY, tempel ke `.env`:
```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```
Isikan ke baris `APP_KEY=` di `.env`.

Build & jalankan:
```bash
docker compose up -d --build
```
Lihat progres:
```bash
docker compose ps
docker compose logs -f app      # Ctrl+C untuk berhenti lihat log
```
Seed data dasar (sekali):
```bash
docker compose exec app php artisan db:seed --force
```
Buka browser: **http://localhost:18080** → harus tampil login.

Stop tes lokal (data tetap aman di volume):
```bash
docker compose down
```
> Jangan commit file `.env`. Sudah di-`.gitignore`.

### A3. Push kode ke GitHub
```bash
git add -A
git commit -m "Setup Docker deploy"
git push origin master
```

---

## BAGIAN B — DI VPS

Login SSH:
```bash
ssh user@IP_VPS
```

### B1. Pastikan Docker ada
```bash
docker version || curl -fsSL https://get.docker.com | sudo sh
docker compose version
```
Kalau `docker` perlu sudo terus, tambah user ke grup (opsional):
```bash
sudo usermod -aG docker $USER   # logout-login ulang agar berlaku
```

### B2. Cek port 18080 bebas
```bash
sudo ss -ltnp | grep ':18080' || echo "BEBAS"
```
Kalau muncul `BEBAS` = aman. (8080 dipakai Dart Frog, makanya kita pakai 18080.)

### B3. Kredensial GitHub (cek dulu, sekali saja)

omsetaPOS = **repo terpisah** dari app lama → harus di-clone ke folder sendiri.
Tapi kredensial GitHub kemungkinan sudah ada (dipakai app lama). Cek di folder app lama:
```bash
cd /path/app-lama && git remote -v && cd -
```
- Muncul `git@github.com:...` → pakai **SSH** (auth sudah siap, lanjut B4 pakai URL SSH).
- Muncul `https://github.com/...` → pakai **HTTPS** (auth lewat credential helper / token).

Kalau VPS **belum** punya akses ke repo `omsetaPOS` (repo private), siapkan salah satu:

**Opsi SSH deploy key (disarankan):**
```bash
ssh-keygen -t ed25519 -C "vps-omseta" -f ~/.ssh/omseta_deploy -N ""
cat ~/.ssh/omseta_deploy.pub
# tempel isinya ke GitHub: repo omsetaPOS → Settings → Deploy keys → Add (read-only)
# daftarkan key ke ssh:
echo 'Host github-omseta
  HostName github.com
  User git
  IdentityFile ~/.ssh/omseta_deploy' >> ~/.ssh/config
```

**Opsi HTTPS token:** buat Personal Access Token (fine-grained, akses read repo omsetaPOS),
nanti dipakai di URL clone: `https://<TOKEN>@github.com/zianfahrudi/omsetaPOS.git`.

### B4. Clone kode (PERTAMA KALI — bukan git pull)
> `git pull` hanya jalan **setelah** repo ada. Pertama kali wajib `git clone`,
> karena itu yang menyimpan alamat repo (`origin`) di VPS.

```bash
sudo mkdir -p /opt/omsetapos && sudo chown $USER:$USER /opt/omsetapos
```
Pilih sesuai metode auth-mu:
```bash
# SSH (deploy key dengan Host alias di atas):
git clone github-omseta:zianfahrudi/omsetaPOS.git /opt/omsetapos

# SSH (key akun sudah terpasang global):
git clone git@github.com:zianfahrudi/omsetaPOS.git /opt/omsetapos

# HTTPS (token):
git clone https://<TOKEN>@github.com/zianfahrudi/omsetaPOS.git /opt/omsetapos

# HTTPS (repo public / helper sudah simpan kredensial):
git clone https://github.com/zianfahrudi/omsetaPOS.git /opt/omsetapos
```
```bash
cd /opt/omsetapos
git remote -v      # verifikasi origin tersimpan → pull berikutnya otomatis tahu
```

### B5. Siapkan `.env` produksi
```bash
cp .env.docker.example .env
nano .env
```
Set:
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://omseta.ziandev.site
SESSION_SECURE_COOKIE=true
DB_PASSWORD=PASSWORD_KUAT
DB_ROOT_PASSWORD=ROOT_PASSWORD_KUAT
```
Generate APP_KEY:
```bash
docker run --rm -v "$PWD":/app -w /app php:8.3-cli \
  php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```
Tempel ke `APP_KEY=` di `.env`.

### B6. Build & jalankan
```bash
docker compose up -d --build
docker compose ps
docker compose logs -f app     # tunggu sampai migrasi & cache selesai
```
Seed data dasar (sekali):
```bash
docker compose exec app php artisan db:seed --force
# wilayah (butuh internet, opsional):
docker compose exec app php artisan db:seed --class=WilayahSeeder --force
```
> Produksi: pastikan `SEED_PASSWORD` di `.env` sudah diisi kuat (bukan `password`).
> Seeder akan menolak seed di produksi bila masih default. Di produksi hanya akun
> **superuser** yang dibuat (admin/kasir demo hanya non-produksi) — buat user lain
> via menu **Pengguna**.
Tes dari dalam VPS:
```bash
curl -I http://127.0.0.1:18080      # harus 200/302
```

### B7. Reverse proxy nginx host → domain
```bash
sudo cp docker/nginx-host-omseta.conf.example /etc/nginx/conf.d/omseta.ziandev.site.conf
sudo nginx -t
sudo systemctl reload nginx
```
Pasang HTTPS (gratis, Let's Encrypt):
```bash
sudo certbot --nginx -d omseta.ziandev.site
```
> Pastikan DNS `omseta.ziandev.site` → IP VPS sudah jadi sebelum certbot.

Buka: **https://omseta.ziandev.site** → selesai 🎉

---

## UPDATE versi berikutnya (rutin)

Di LOCAL: commit + `git push origin master`.
Di VPS:
```bash
cd /opt/omsetapos
git pull
docker compose up -d --build      # migrasi --force jalan otomatis, data aman
```

## Perintah harian (VPS)

| Tindakan | Perintah |
|---|---|
| Status | `docker compose ps` |
| Log app | `docker compose logs -f app` |
| Restart | `docker compose restart` |
| Masuk shell app | `docker compose exec app sh` |
| Backup DB | `docker compose exec db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" omsetapos' > backup_$(date +%F).sql` |
| Stop (data aman) | `docker compose down` |

## Kalau ada masalah
- **502 di domain** → container belum jalan / port salah. Cek `docker compose ps` & `curl -I http://127.0.0.1:18080`.
- **Login gagal / redirect loop** → cek `APP_URL=https://...` & `SESSION_SECURE_COOKIE=true` di `.env` VPS, lalu `docker compose restart`.
- **Aset/CSS rusak** → `docker compose exec app php artisan optimize:clear` lalu restart.
- **Reverse proxy bukan nginx** (Caddy/Traefik) → konfigurasinya beda, minta bantuan.
