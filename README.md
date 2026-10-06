# PakeLagi API — Backend REST API

Backend REST API untuk **[pakelagi.works](https://pakelagi.works)**, toko pakaian bekas (_preloved_). Repository ini mengelola katalog produk, pengolahan foto WebP, autentikasi admin, serta pengiriman pemberitahuan revalidasi cache ke frontend Next.js (`pakelagi-web`).

---

## 🛠 Tech Stack

- **Framework:** Laravel 12 (PHP 8.3+)
- **Database:** MySQL (`utf8mb4`)
- **Authentication:** Laravel Sanctum (SPA Session Cookie)
- **Image Processing:** Intervention Image 4 (GD Driver dengan WebP)
- **Queue:** Laravel Queue (Database Driver) untuk revalidasi cache frontend
- **API Documentation:** Dedoc Scramble (OpenAPI 3.1)
- **Code Quality & Testing:** PHPUnit / Pest, Laravel Pint, Larastan (PHPStan Level 5)

---

## 📋 Prasyarat System

- **PHP 8.3** atau versi lebih baru (dengan ekstensi `gd` aktif dan mendukung WebP)
- **Composer 2.x**
- **MySQL 8.0+**
- **Laragon** (opsional, disarankan untuk pengguna Windows)

---

## 🚀 Panduan Instalasi Lokal

### 1. Clone Repository & Install Dependensi

```bash
git clone https://github.com/hilptra/pakelagi-api.git
cd pakelagi-api
composer install
```

### 2. Konfigurasi Environment

Salin file contoh konfigurasi dan buat kunci aplikasi:

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database di file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pakelagi
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrasi & Seeding Database

Jalankan migrasi database beserta seeder untuk mengisi data awal (kategori, produk contoh, dan akun admin):

```bash
php artisan migrate --seed
```

_Catatan: Akun admin awal dibuat berdasarkan konfigurasi `ADMIN_EMAIL` dan `ADMIN_PASSWORD` di `.env`._

### 4. Link Storage Foto

Buat _symlink_ agar foto produk yang diunggah ke storage public dapat diakses lewat URL web:

```bash
php artisan storage:link
```

### 5. Jalankan Server Dev & Pekerja Queue

Buka dua jendela terminal untuk menjalankan server aplikasi dan pekerja antrean revalidasi:

```bash
# Terminal 1: Server Aplikasi
php artisan serve

# Terminal 2: Pekerja Queue Revalidasi
php artisan queue:listen
```

Aplikasi akan berjalan di `http://127.0.0.1:8000`.

---

## 📚 Dokumentasi API (OpenAPI / Swagger)

Dokumentasi API dibuat secara otomatis menggunakan **Dedoc Scramble**:

- **Interactive UI:** Akses `http://127.0.0.1:8000/docs/api` saat server berjalan.
- **OpenAPI JSON Spec:** `http://127.0.0.1:8000/docs/api.json` (atau lihat file [`docs/openapi.json`](./docs/openapi.json)).

---

## 🧪 Testing & Kualitas Kode

Sebelum melakukan commit atau Pull Request, pastikan seluruh tes dan pengecekan format kode lulus:

```bash
# Menjalankan seluruh Unit & Feature Tests (menggunakan database pakelagi_test)
php artisan test

# Memeriksa dan merapikan format kode dengan Laravel Pint
./vendor/bin/pint

# Memeriksa format tanpa mengubah file (CI Check)
./vendor/bin/pint --test

# Analisis statis kode dengan Larastan (PHPStan Level 5)
./vendor/bin/phpstan analyse
```

---

## 📁 Struktur Direktori

```text
pakelagi-api/
├── app/
├── Actions/ # Aturan bisnis (CreateProduct, DeleteCategory, dll)
├── Enums/ # ProductStatus, ProductCondition
├── Http/
│ ├── Controllers/Api/V1/ # Public & Auth Controller
│ │ └── Admin/ # Admin Controllers (Product, Category, ProductImage)
│ ├── Requests/ # Form Request validations
│ └── Resources/ # JSON API Resources
├── Jobs/ # RevalidateFrontendCache
├── Models/ # Category, Product, ProductImage
├── Services/ # Technical services (ProductImageProcessor, FrontendCache)
└── Support/ # Helper utilities (UniqueSlug)
├── config/ # Pengaturan aplikasi (pakelagi.php, scramble.php)
├── database/ # Migrasi, Factory, dan Seeder
├── docs/ # Dokumentasi arsitektur, ERD, User Flow, & OpenAPI JSON
└── tests/ # Feature tests API
```

---

## 🔗 Dokumentasi Terkait

- 📄 [Requirements & Aturan Bisnis (`docs/requirement.md`)](./docs/requirement.md)
- 🗄 [Entity Relationship Diagram (`docs/erd.md`)](./docs/erd.md)
- 🔄 [User Flow & Technical Flow (`docs/userflow.md`)](./docs/userflow.md)
- 🌐 [Integrasi Cache Frontend (`docs/frontend-revalidation.md`)](./docs/frontend-revalidation.md)
