# Panduan Deployment PakeLagi API ke Render (Docker)

Dokumen ini menjelaskan langkah-langkah deployment **PakeLagi API (Laravel 12)** ke **Render** menggunakan **Docker**, dengan database **PostgreSQL** terpisah, penyimpanan gambar **Cloudinary**, serta frontend **Next.js/SPA di Vercel**.

---

## 1. Arsitektur Production

- **Backend API**: Render Web Service (Docker, PHP 8.3-FPM + Nginx + Supervisor).
- **Database**: Render Managed PostgreSQL (atau MySQL eksternal).
- **Media / Image Storage**: Cloudinary (`PRODUCT_IMAGE_DISK=cloudinary`).
- **Frontend**: Vercel (`FRONTEND_URL=https://...`).

### Catatan Kompatibilitas Database (PostgreSQL vs MySQL)

Seluruh migration di `database/migrations/` menggunakan Blueprint standar Laravel sehingga kompatibel dengan **PostgreSQL** maupun **MySQL**.

- **Rekomendasi Render**: Gunakan **Render PostgreSQL** karena terintegrasi langsung di Render.
- **Perbedaan perilaku yang perlu diketahui**: Operator `LIKE` di PostgreSQL bersifat _case-sensitive_ (berbeda dengan MySQL/SQLite yang _case-insensitive_). Jika pencarian produk/event perlu _case-insensitive_ di PostgreSQL, pertimbangkan `ilike` atau `LOWER(...)` di update mendatang.

---

## 2. Persiapan Sebelum Deploy

1. Pastikan `.env` lokal **tidak** di-commit ke GitHub (sudah ada di `.gitignore`).
2. Pastikan Anda sudah memiliki:
    - Akun **Render** dan repository GitHub yang terhubung.
    - Akun **Cloudinary** (`Cloud Name`, `API Key`, `API Secret`).
    - `APP_KEY` baru khusus production (generate di lokal dengan `php artisan key:generate --show` — **jangan** gunakan `APP_KEY` development dan **jangan** simpan di source code).

---

## 3. Setup Database di Render

1. Di Dashboard Render, klik **New + → PostgreSQL**.
2. Pilih region yang sama dengan Web Service API agar latency rendah.
3. Setelah aktif, salin **Internal Database URL** (format `postgresql://user:pass@host:5432/dbname`).

---

## 4. Setup Web Service (Docker) di Render

1. Klik **New + → Web Service**, pilih repository GitHub Anda.
2. Atur konfigurasi berikut:
    - **Root Directory**: `pakelagi-api` (jika repo Anda monorepo yang berisi `pakelagi-api` dan `pakelagi-frontend`). Jika repo hanya berisi Laravel API, kosongkan.
    - **Language / Runtime**: `Docker`.
    - **Dockerfile Path**: `./Dockerfile` (relatif terhadap Root Directory).
    - **Health Check Path**: `/up`.
3. Container akan otomatis membaca environment variable `PORT` dari Render (default `10000`).

---

## 5. Daftar Environment Variables Production (Render)

Tambahkan variabel berikut di tab **Environment** pada Render Web Service (**jangan** hardcode di source code):

### Aplikasi & Keamanan

```env
APP_NAME=PakeLagi_API
APP_ENV=production
APP_KEY=base64:ISI_DENGAN_KEY_BARU_PRODUCTION
APP_DEBUG=false
APP_URL=https://pakelagi-api.onrender.com
LOG_CHANNEL=stderr
LOG_LEVEL=warning
TRUSTED_PROXIES=*
```

> **Catatan Keamanan `TRUSTED_PROXIES=*`**: Render menerminasi HTTPS di load balancer dan meneruskan header `X-Forwarded-Proto` / `X-Forwarded-For`. Pengaturan `*` aman **hanya** jika aplikasi berjalan di belakang reverse proxy Render yang menimpa header `X-Forwarded-*` dari klien.

### Database (PostgreSQL Render atau MySQL TLS Eksternal / Enzonic Databases)

**Opsi A — Render PostgreSQL:**

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://USER:PASSWORD@HOST:5432/DATABASE
```

_(Alternatif jika menggunakan MySQL eksternal: set `DB_CONNECTION=mysql` dan isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.)_

**Opsi B — MySQL Eksternal dengan TLS/SSL Wajib (misalnya Enzonic Databases):**

```env
DB_CONNECTION=mysql
DB_HOST=your-mysql-host.example.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_strong_password
MYSQL_ATTR_SSL_CA=true
# Opsional jika menggunakan path CA custom (default saat true: /etc/ssl/certs/ca-certificates.crt):
# MYSQL_ATTR_SSL_CA_PATH=/etc/ssl/certs/ca-certificates.crt
```

> `config/database.php` menormalkan `MYSQL_ATTR_SSL_CA=true` menjadi path CA bundle sistem (`/etc/ssl/certs/ca-certificates.crt` yang sudah tersedia di Docker image) agar `PDO::MYSQL_ATTR_SSL_CA` melakukan handshake TLS dengan valid. Jika `MYSQL_ATTR_SSL_CA` tidak diisi di lokal, koneksi MySQL development berjalan tanpa TLS seperti biasa.

### Session, Cache & Queue

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### CORS & Sanctum (Frontend Vercel)

```env
FRONTEND_URL=https://nama-project.vercel.app
SANCTUM_STATEFUL_DOMAINS=nama-project.vercel.app
```

Jika frontend memiliki custom domain + domain preview Vercel, pisahkan `SANCTUM_STATEFUL_DOMAINS` dengan koma (tanpa `https://`).

### Cloudinary (Upload Gambar)

```env
PRODUCT_IMAGE_DISK=cloudinary
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_FOLDER=pakelagi
```

### Frontend Revalidation & Admin Seeder

```env
FRONTEND_REVALIDATE_URL=https://nama-project.vercel.app/api/revalidate
REVALIDATE_SECRET=isi_secret_acak_yang_sama_dengan_vercel
ADMIN_NAME=Admin PakeLagi
ADMIN_EMAIL=admin@domainanda.com
ADMIN_PASSWORD=PasswordKuatMinimal16Karakter
```

---

## 6. Menjalankan Migration & Seeder (Terkontrol)

Entrypoint Docker **tidak** menjalankan migration atau seeder secara default (`RUN_MIGRATIONS_ON_START=false`, `RUN_SEED_ON_START=false`).

### Opsi A — Melalui Environment Flag Saat Startup (Cocok untuk EthioDeploy / Database Kosong Pertama Kali)
1. Set environment variable berikut sebelum deploy pertama:
   ```env
   RUN_MIGRATIONS_ON_START=true
   RUN_SEED_ON_START=true
   ```
   - `RUN_MIGRATIONS_ON_START=true` menjalankan `php artisan migrate --force` sebelum Nginx/PHP-FPM dimulai. Jika database gagal diakses atau migration error, container akan langsung berhenti (tidak berjalan dalam kondisi rusak).
   - `RUN_SEED_ON_START=true` menjalankan `php artisan db:seed --force` (di `production`, hanya menjalankan `CategorySeeder` dan `AdminUserSeeder` menggunakan `ADMIN_EMAIL` & `ADMIN_PASSWORD` dari env).
2. Setelah deploy pertama berhasil dan tabel/admin terverifikasi, **kembalikan kedua flag ke `false`** (atau hapus) agar restart container berikutnya tidak menjalankan ulang migration/seed.

### Opsi B — Melalui Render Shell / Pre-Deploy Command (Manual)

```bash
# 1. Jalankan migration
php artisan migrate --force

# 2. (Opsional / Pertama kali) Seed kategori & akun admin dari env ADMIN_*
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=AdminUserSeeder --force

# 3. (Opsional) Setelah deployment & database terverifikasi, aktifkan cache optimasi Laravel:
php artisan optimize
```

---

## 7. Catatan Penting: Cross-Site Cookies (Vercel ↔ Render)

Karena API (`*.onrender.com`) dan Frontend (`*.vercel.app`) berada di **domain yang berbeda** (cross-site), autentikasi session Sanctum bergantung pada cookie pihak ketiga (_third-party cookies_):

- `SESSION_SECURE_COOKIE=true` dan `SESSION_SAME_SITE=none` **wajib** aktif agar browser mau mengirim cookie session ke domain berbeda melalui HTTPS.
- **Kendala Browser Modern**: Safari (ITP) dan beberapa browser dengan mode _strict tracking prevention_ memblokir _third-party cookies_ meskipun `SameSite=None; Secure`.
- **Solusi Jangka Panjang yang Direkomendasikan**: Gunakan custom domain di root domain yang sama, misalnya:
    - Frontend: `https://pakelagi.id` (Vercel)
    - API: `https://api.pakelagi.id` (Render)
    - Set `SESSION_DOMAIN=.pakelagi.id` dan `SESSION_SAME_SITE=lax`.

### Cara Menguji CSRF, Login, Session, dan Logout

Pastikan frontend mengirim `credentials: 'include'` (Fetch) atau `withCredentials: true` (Axios) dan header `Accept: application/json`:

1. **Ambil CSRF Cookie**:
   `GET https://pakelagi-api.onrender.com/sanctum/csrf-cookie`
    - Cek response header `Set-Cookie`: harus ada `XSRF-TOKEN` dan session cookie dengan atribut `Secure; SameSite=None`.
2. **Login**:
   `POST https://pakelagi-api.onrender.com/api/v1/auth/login`
    - Sertakan header `X-XSRF-TOKEN` (nilai decode dari cookie `XSRF-TOKEN`) dan `Origin: https://nama-project.vercel.app`.
    - Status harus `200 OK`. Jika `400` ("Permintaan login harus berasal dari domain frontend yang terdaftar"), periksa `SANCTUM_STATEFUL_DOMAINS` dan `Referer`/`Origin`.
3. **Cek Session (`/me`)**:
   `GET https://pakelagi-api.onrender.com/api/v1/auth/me`
    - Harus mengembalikan profil admin `200 OK`.
4. **Logout**:
   `POST https://pakelagi-api.onrender.com/api/v1/auth/logout`
    - Setelah logout, `GET /api/v1/auth/me` harus mengembalikan `401 Unauthorized`.

---

## 8. Troubleshooting

| Gejala                                       | Penyebab Umum                                              | Solusi                                                                                                                |
| -------------------------------------------- | ---------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| `419 CSRF token mismatch` / `400` saat login | `SANCTUM_STATEFUL_DOMAINS` atau `FRONTEND_URL` tidak cocok | Pastikan domain Vercel di `SANCTUM_STATEFUL_DOMAINS` tanpa `https://` dan `FRONTEND_URL` persis dengan origin Vercel. |
| Cookie tidak tersimpan di browser            | `TRUSTED_PROXIES` kosong atau `SESSION_SAME_SITE=lax`      | Pastikan `TRUSTED_PROXIES=*`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=none`.                                 |
| Upload gambar gagal (`500`)                  | Credential Cloudinary belum diisi atau salah               | Cek `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`, dan `PRODUCT_IMAGE_DISK=cloudinary`.      |
| Health check `/up` gagal di Render           | Nginx tidak listen di `$PORT`                              | Pastikan `PORT` di Render konsisten dan cek log container Supervisor/Nginx.                                           |
| `500` saat boot pertama                      | `APP_KEY` belum di-set                                     | Generate dengan `php artisan key:generate --show` dan masukkan ke env Render.                                         |

---

## 9. Rollback

1. **Rollback Aplikasi (Render)**:
    - Buka **Events / Deploys** di Render Web Service, pilih deploy sebelumnya yang stabil, lalu klik **Rollback to this deploy**.
2. **Rollback Database (Jika migration baru bermasalah)**:
    - Selalu buat backup/snapshot PostgreSQL sebelum menjalankan migration baru.
    - Jika perlu revert 1 batch terakhir via Render Shell:
        ```bash
        php artisan migrate:rollback --step=1 --force
        ```
