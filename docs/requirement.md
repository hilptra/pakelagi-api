# PakeLagi API — Dokumen Kebutuhan (Requirements)

|                     |                                                |
| ------------------- | ---------------------------------------------- |
| **Proyek**          | pakelagi.works — toko pakaian bekas (preloved) |
| **Repositori**      | `pakelagi-api` (backend)                       |
| **Versi dokumen**   | 0.2 (Draft)                                    |
| **Tanggal**         | 4 Oktober 2026                                 |
| **Dokumen terkait** | [ERD](./erd.md) · [User Flow](./user-flow.md)  |

---

## 1. Deskripsi Proyek

PakeLagi adalah website untuk menjual pakaian bekas (preloved). Repositori ini berisi **backend berupa REST API** yang menyimpan data produk, mengelola foto, dan mengamankan akses admin. Tampilan untuk pembeli dan halaman admin dibangun terpisah di repositori frontend (Next.js) dan mengonsumsi API ini.

Proyek ini punya tiga tujuan sekaligus: **dipakai berjualan sungguhan** setelah di-deploy, **sarana belajar**, dan **karya portofolio**. Karena itu kualitas kode, dokumentasi, dan proses kerja (Git, CI, tes) sama pentingnya dengan fiturnya.

**Status:** MVP (Minimum Viable Product). Scope sengaja dibatasi agar dapat dikerjakan sendiri dan segera dipakai berjualan.

**Estimasi waktu pengerjaan:** belum ditetapkan (isi sesuai kapasitas pengerjaan, misalnya dalam minggu part-time).

---

## 2. Tujuan

1. Menyediakan REST API yang stabil dan terdokumentasi untuk katalog toko preloved, yang melayani web dan dapat melayani klien lain (misalnya aplikasi mobile) di masa depan.
2. Memungkinkan admin mengelola produk (termasuk upload foto) tanpa menyentuh database langsung.
3. Menunjukkan siklus REST yang lengkap (baca, tambah, ubah, hapus, autentikasi), bukan sekadar `GET`.
4. Menunjukkan penerapan prinsip desain perangkat lunak: pemisahan tanggung jawab, validasi terpusat, enum, dan abstraksi pada bagian yang mungkin berganti.
5. Menunjukkan praktik kerja profesional: alur Git `main`/`develop`, Pull Request, CI, dan tes.
6. Menghasilkan proyek yang dapat didemokan end-to-end dan siap dipakai berjualan sungguhan.

---

## 3. Tech Stack

| Bagian                       | Teknologi                                                                                                                               |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Backend                      | Laravel 12, PHP 8.2–8.5                                                                                                                 |
| API                          | REST, berversi di URL (`/api/v1`), format JSON                                                                                          |
| Authentication               | Laravel Sanctum (direncanakan berbasis cookie untuk SPA, domain induk yang sama)                                                        |
| Database                     | MySQL (encoding `utf8mb4`)                                                                                                              |
| Queue                        | Laravel Queue, driver database untuk MVP (usulan: dipakai untuk revalidasi cache dan pemrosesan gambar); Redis opsional di tahap lanjut |
| Penyimpanan foto             | Laravel Filesystem (disk lokal dulu; dapat dialihkan ke layanan eksternal tanpa mengubah logika)                                        |
| Web Frontend (repo terpisah) | Next.js, TypeScript, Tailwind CSS                                                                                                       |
| Testing                      | Pest/PHPUnit (backend, prioritas utama)                                                                                                 |
| Kualitas kode                | Laravel Pint (format), Larastan (analisis statis)                                                                                       |
| CI                           | GitHub Actions (Pint, Larastan, tes pada setiap Pull Request)                                                                           |
| Dokumentasi API              | Scribe atau OpenAPI/Swagger (dipilih saat implementasi)                                                                                 |
| Version Control              | Git & GitHub (`main` + `develop` + branch per fitur)                                                                                    |
| Local Dev Environment        | Laravel Herd atau Laragon (PHP, Composer, MySQL)                                                                                        |
| Domain                       | `pakelagi.works` (frontend), `api.pakelagi.works` (API)                                                                                 |
| Hosting                      | Hosting PHP atau VPS (belum diputuskan)                                                                                                 |
| Pembayaran online (tahap 2)  | Midtrans atau Xendit (belum dipilih)                                                                                                    |

> **Catatan:** Laravel 12 sudah memasuki fase perbaikan keamanan saja (sampai 24 Februari 2027). Rencanakan upgrade ke Laravel 13 sebagai pekerjaan terjadwal.

---

## 4. Struktur Repository

Rencana saat ini adalah **dua repositori terpisah**: `pakelagi-api` (dokumen ini) dan `pakelagi-web` (Next.js). Struktur `pakelagi-api`:

```
pakelagi-api/
├── app/
│   ├── Actions/                 (aturan bisnis, mis. MarkProductAsSold)
│   ├── Enums/                   (ProductStatus, ProductCondition)
│   ├── Http/
│   │   ├── Controllers/Api/V1/  (controller tipis)
│   │   ├── Requests/            (validasi input)
│   │   └── Resources/           (format output JSON)
│   └── Models/                  (Category, Product, ProductImage)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/                 (tes endpoint)
│   └── Unit/
├── docs/                        (requirements, ERD, user flow)
├── .github/workflows/ci.yml     (CI)
├── .env.example
├── AGENTS.md                    (opsional: panduan untuk AI coding agent)
└── README.md
```

Folder `Actions` bukan bawaan Laravel; kita membuatnya sendiri untuk menampung aturan bisnis agar controller tetap tipis.

---

## 5. Scope MVP

### 5.1 Fitur Wajib (In Scope)

- Katalog produk publik dengan filter (kategori, ukuran, harga), pencarian, pengurutan, dan paginasi.
- Detail produk: foto, kondisi, ukuran dalam cm, deskripsi, dan status.
- Pengambilan banyak produk berdasarkan daftar slug (untuk wishlist di sisi frontend).
- Autentikasi admin (login, logout, data admin) via Laravel Sanctum.
- Manajemen produk oleh admin: tambah, ubah, hapus, sembunyikan/tayangkan, tandai terjual.
- Manajemen foto produk: unggah banyak foto, foto utama, urutan, hapus.
- Manajemen kategori beserta daftar jenis ukuran (`measurement_fields`).
- Pemberitahuan ke frontend (revalidasi cache) saat produk berubah.

### 5.2 Di Luar Scope MVP (Future Improvements)

- Pembayaran online (Midtrans/Xendit) — tahap 2; versi 1 memakai checkout via WhatsApp.
- Akun pembeli dan wishlist yang tersinkron antar perangkat.
- Ongkos kirim otomatis.
- Reservasi sementara barang yang sedang dinegosiasikan.
- Redis (cache dan queue).
- Deploy otomatis (CD).
- Aplikasi mobile.

**Diputuskan tidak dibuat:** statistik penjualan.

### 5.3 Asumsi

- Wishlist disimpan di browser pembeli (localStorage), bukan di database. Backend hanya menyediakan pengambilan produk berdasarkan daftar slug.
- Keranjang dan pembuatan pesan WhatsApp sepenuhnya ditangani frontend; backend tidak menyimpan pesanan.
- Hanya ada satu peran pengguna terautentikasi: **admin**.

---

## 6. Aktor

| Aktor                | Deskripsi                                             | Akses                                             |
| -------------------- | ----------------------------------------------------- | ------------------------------------------------- |
| **Pembeli (tamu)**   | Pengunjung yang menjelajah katalog. Tidak perlu akun. | Endpoint publik, hanya baca                       |
| **Admin**            | Pemilik toko yang mengelola produk.                   | Endpoint publik + endpoint admin (terautentikasi) |
| **Frontend Next.js** | Klien API (sisi publik dan halaman admin).            | Memanggil API atas nama pembeli/admin             |

---

## 7. Entity Relationship (Ringkasan)

Tabel inti:

- `users` — akun admin (dibuat lewat seeder/perintah artisan, bukan registrasi publik)
- `categories` — kategori pakaian; menyimpan `measurement_fields`, yaitu daftar jenis ukuran yang relevan per kategori
- `products` — produk unik (stok 1); menyimpan harga (integer rupiah), kondisi, ukuran, status, dan `sold_at`
- `product_images` — foto produk beserta urutan dan penanda foto utama

Relasi: satu kategori punya banyak produk (hapus dibatasi), satu produk punya banyak foto (hapus ikut terhapus).

Detail kolom, tipe data, indeks, dan diagram ada di [`docs/erd.md`](./erd.md).

---

## 8. Alur Utama

```
Admin menambah produk (nama, kategori, harga, ukuran, kondisi)
   ↓
Admin mengunggah foto dan menentukan foto utama
   ↓
Produk ditayangkan (status: available)
   ↓
Backend memicu revalidasi cache di Next.js
   ↓
Pembeli melihat katalog (filter/cari) dan membuka detail produk
   ↓
Pembeli menyimpan ke wishlist atau keranjang (di browser)
   ↓
Pembeli checkout via WhatsApp (pesan berisi daftar barang)
   ↓
Negosiasi dan pembayaran diselesaikan di luar situs
   ↓
Admin menandai produk terjual (status: sold, sold_at terisi)
   ↓
Backend memicu revalidasi cache di Next.js
   ↓
Katalog dan halaman produk menampilkan label Terjual
```

Versi diagram lengkap (alur pembeli, alur admin, dan alur teknis) ada di [`docs/user-flow.md`](./user-flow.md).

---

## 9. Aturan Bisnis

| ID    | Aturan                                                                                                                                                                                                  |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| BR-01 | Setiap produk bersifat **unik (stok 1)**. Tidak ada kolom stok; ketersediaan ditentukan oleh `status`.                                                                                                  |
| BR-02 | Status produk hanya salah satu dari: `available`, `sold`, `hidden`.                                                                                                                                     |
| BR-03 | Kondisi produk hanya salah satu dari: `like_new`, `good`, `fair`, ditambah catatan bebas (`condition_notes`) untuk minus.                                                                               |
| BR-04 | Produk `hidden` **tidak pernah** muncul di API publik.                                                                                                                                                  |
| BR-05 | Produk `sold` **tetap bisa dibuka lewat slug** agar link yang sudah dibagikan tidak error. Katalog menampilkannya di urutan bawah atau menyembunyikannya setelah beberapa hari (berdasarkan `sold_at`). |
| BR-06 | Saat produk ditandai terjual, sistem mengisi `sold_at` dengan waktu saat itu.                                                                                                                           |
| BR-07 | `slug` produk dan kategori bersifat unik dan dipakai di URL.                                                                                                                                            |
| BR-08 | Harga disimpan sebagai bilangan bulat dalam rupiah (tanpa desimal).                                                                                                                                     |
| BR-09 | Ukuran (`measurements`) dalam cm dan harus sesuai daftar `measurement_fields` milik kategori produk tersebut.                                                                                           |
| BR-10 | Kategori yang masih memiliki produk **tidak boleh dihapus**.                                                                                                                                            |
| BR-11 | Menghapus produk ikut menghapus data foto-fotonya, **termasuk file di storage** (dikerjakan lewat kode, bukan hanya cascade database).                                                                  |
| BR-12 | Setiap produk punya paling banyak satu foto utama (`is_primary`).                                                                                                                                       |
| BR-13 | Foto ditampilkan berurutan menurut `sort_order`.                                                                                                                                                        |

**Risiko yang diketahui:** karena checkout lewat WhatsApp dan stok hanya 1, dua pembeli bisa menghubungi untuk barang yang sama. Keranjang tidak menjamin reservasi; admin yang memutuskan dan menandai barang terjual secara manual.

---

## 10. Prinsip Desain yang Dipegang

1. **Slug sebagai identitas publik** — URL dan API publik memakai `slug`, bukan `id` internal, sehingga link mudah dibaca dan dibagikan.
2. **Status berbasis enum, bukan string bebas** — `ProductStatus` dan `ProductCondition` dijaga oleh PHP Enum untuk mencegah data tidak konsisten.
3. **Backend sebagai satu-satunya sumber kebenaran** — validasi dan aturan bisnis (misalnya kecocokan ukuran dengan kategori, visibilitas produk) ada di backend; frontend tidak menduplikasi logika tersebut.
4. **Kategori berbasis data** — jenis ukuran per kategori disimpan di `measurement_fields`, sehingga menambah kategori baru cukup dengan data, tanpa mengubah kode.
5. **Barang terjual tidak dihapus** — produk `sold` dipertahankan agar link yang sudah dibagikan tetap valid dan `sold_at` tercatat.
6. **Operasi admin aman diulang (idempotent)** — menandai produk yang sudah terjual tidak menimpa `sold_at` dan tidak menghasilkan error.
7. **Controller tipis, logika di Action** — controller hanya menerima request dan mengembalikan respons; aturan bisnis di kelas Action yang dapat dites.
8. **Abstraksi pada bagian yang mungkin berganti** — penyimpanan foto lewat Laravel Filesystem (pindah dari disk lokal ke layanan eksternal cukup lewat konfigurasi), dan pembayaran online di tahap 2 lewat interface gateway agar Midtrans/Xendit dapat ditukar tanpa mengubah logika inti.
9. **API berversi dan konsisten** — `/api/v1`, format respons dan error seragam, kode status HTTP yang tepat.
10. **Harga sebagai snapshot saat pesanan ada** — saat tabel pesanan ditambahkan di tahap 2, harga disalin ke pesanan sehingga perubahan harga produk tidak memengaruhi pesanan yang sudah dibuat.
11. **Sederhana dulu** — hindari abstraksi yang belum dibutuhkan; refaktor saat kebutuhan nyata muncul.

---

## 11. Kebutuhan Fungsional

Prioritas memakai skala MoSCoW: **M** (Must), **S** (Should), **C** (Could).

### 11.1 API publik

| ID    | Kebutuhan                                                                                                                                    | Prioritas |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-01 | Menampilkan daftar kategori beserta `measurement_fields`-nya.                                                                                | M         |
| FR-02 | Menampilkan daftar produk dengan paginasi.                                                                                                   | M         |
| FR-03 | Memfilter produk berdasarkan kategori, ukuran (`size_label`), dan rentang harga.                                                             | M         |
| FR-04 | Mencari produk berdasarkan kata kunci pada nama/merek/deskripsi.                                                                             | S         |
| FR-05 | Mengurutkan produk (terbaru, harga naik, harga turun).                                                                                       | S         |
| FR-06 | Menampilkan detail produk berdasarkan `slug`, lengkap dengan foto, kondisi, ukuran, dan status.                                              | M         |
| FR-07 | Mengambil banyak produk sekaligus berdasarkan daftar slug (dukungan wishlist). Slug yang sudah tidak ada diabaikan, bukan menyebabkan error. | M         |
| FR-08 | Hanya menampilkan produk yang tidak `hidden` (lihat BR-04, BR-05).                                                                           | M         |

### 11.2 Autentikasi admin

| ID    | Kebutuhan                                                        | Prioritas |
| ----- | ---------------------------------------------------------------- | --------- |
| FR-10 | Admin dapat login dengan email dan kata sandi.                   | M         |
| FR-11 | Admin dapat logout.                                              | M         |
| FR-12 | Frontend dapat mengambil data admin yang sedang login (`me`).    | M         |
| FR-13 | Semua endpoint admin menolak permintaan tanpa autentikasi (401). | M         |
| FR-14 | Percobaan login dibatasi lajunya (rate limiting).                | M         |

### 11.3 Manajemen produk (admin)

| ID    | Kebutuhan                                                                              | Prioritas |
| ----- | -------------------------------------------------------------------------------------- | --------- |
| FR-20 | Menampilkan semua produk termasuk berstatus `hidden` dan `sold`, dengan filter status. | M         |
| FR-21 | Menambah produk; `slug` dibuat otomatis dari nama dan dijamin unik.                    | M         |
| FR-22 | Mengubah data produk.                                                                  | M         |
| FR-23 | Menghapus produk (lihat BR-11).                                                        | M         |
| FR-24 | Menandai produk sebagai terjual (mengisi `sold_at`, lihat BR-06).                      | M         |
| FR-25 | Menyembunyikan dan menayangkan kembali produk (`hidden` ↔ `available`).                | M         |
| FR-26 | Membatalkan status terjual (kembali ke `available`) bila pembelian batal.              | S         |

### 11.4 Manajemen foto (admin)

| ID    | Kebutuhan                                                                                                                                                                                      | Prioritas |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-30 | Mengunggah satu atau banyak foto untuk sebuah produk.                                                                                                                                          | M         |
| FR-31 | Memvalidasi file unggahan: format JPG/PNG/WebP, **maksimal 2 MB per foto**, **maksimal 8 foto per permintaan**, dan dimensi maksimal 4000 × 4000 px. Frontend mengompres foto sebelum dikirim. | M         |
| FR-32 | Membuat beberapa ukuran gambar (misalnya thumbnail dan ukuran detail) agar katalog ringan.                                                                                                     | S         |
| FR-33 | Menentukan foto utama dan mengatur urutan foto.                                                                                                                                                | M         |
| FR-34 | Menghapus foto (data dan file).                                                                                                                                                                | M         |

### 11.5 Manajemen kategori (admin)

| ID    | Kebutuhan                                                                                       | Prioritas |
| ----- | ----------------------------------------------------------------------------------------------- | --------- |
| FR-40 | Menambah dan mengubah kategori beserta `measurement_fields` (daftar jenis ukuran yang relevan). | M         |
| FR-41 | Menghapus kategori hanya jika tidak punya produk (BR-10).                                       | M         |
| FR-42 | Kategori awal: atasan, bawahan, outer, aksesori (diisi lewat seeder).                           | M         |

### 11.6 Integrasi dengan frontend

| ID    | Kebutuhan                                                                                                                                                                                                                                     | Prioritas |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------- |
| FR-50 | Setelah produk dibuat, diubah, ditandai terjual, disembunyikan, atau dihapus, backend memanggil endpoint revalidasi di Next.js agar halaman terkait diperbarui. Kegagalan pemanggilan dicatat di log dan tidak menggagalkan permintaan admin. | M         |
| FR-51 | CORS dan cookie autentikasi dikonfigurasi agar hanya domain frontend yang diizinkan.                                                                                                                                                          | M         |

---

## 12. Kebutuhan Non-Fungsional

### 12.1 Keamanan

- Validasi semua input lewat Form Request; lindungi dari _mass assignment_ dengan `$fillable`.
- Rate limiting pada login dan endpoint publik.
- Unggahan file divalidasi (tipe MIME, ekstensi, ukuran); nama file dibuat ulang oleh server, bukan memakai nama dari pengguna.
- Batas PHP dan web server (`upload_max_filesize`, `post_max_size`, dan `client_max_body_size` pada Nginx) harus lebih longgar daripada aturan validasi Laravel, supaya Laravel yang menolak dengan pesan yang jelas. Catat sebagai bagian dari konfigurasi produksi.
- Rahasia (`.env`, kunci, kata sandi) tidak pernah masuk Git; sediakan `.env.example`.
- HTTPS di produksi; cookie autentikasi ditandai `secure` dan `httpOnly`.
- Respons error tidak membocorkan detail internal (matikan `APP_DEBUG` di produksi).

### 12.2 Performa

- Indeks pada kolom yang dipakai filter (`status`, `category_id`, `price`).
- Semua daftar menggunakan paginasi dengan batas maksimum `per_page`.
- Hindari _N+1 query_ dengan _eager loading_ (misalnya memuat kategori dan foto sekaligus).
- Foto dikompres dan tersedia dalam beberapa ukuran.

### 12.3 Kualitas dan keterpeliharaan

- Controller tipis; aturan bisnis di kelas Action/Service yang dapat dites.
- Validasi lewat Form Request, format output lewat API Resources.
- Status dan kondisi memakai PHP Enum.
- Filter katalog dikumpulkan sebagai query scope di model.
- Format kode otomatis dengan Pint, analisis statis dengan Larastan.
- Feature test untuk endpoint utama; CI menolak merge bila tes atau lint gagal.

### 12.4 Konsistensi API

- Versi di URL (`/api/v1`).
- Format respons dan format error yang seragam.
- Kode status HTTP yang tepat (200, 201, 204, 401, 403, 404, 422, 429).
- Dokumentasi API otomatis yang selalu sinkron dengan kode.

### 12.5 Kompatibilitas

- API melayani frontend yang **responsif** (mobile-first); respons harus ringan karena sebagian besar pembeli memakai HP.

---

## 13. Gambaran Endpoint (rencana)

Semua rute berawalan `/api/v1`.

### Publik

| Method | Path               | Fungsi                                                                                                            | Kebutuhan              |
| ------ | ------------------ | ----------------------------------------------------------------------------------------------------------------- | ---------------------- |
| GET    | `/categories`      | Daftar kategori                                                                                                   | FR-01                  |
| GET    | `/products`        | Daftar produk (parameter: `category`, `size`, `min_price`, `max_price`, `q`, `sort`, `page`, `per_page`, `slugs`) | FR-02–05, FR-07, FR-08 |
| GET    | `/products/{slug}` | Detail produk                                                                                                     | FR-06                  |

### Autentikasi

| Method | Path                   | Fungsi                                   | Kebutuhan    |
| ------ | ---------------------- | ---------------------------------------- | ------------ |
| GET    | `/sanctum/csrf-cookie` | Mengambil cookie CSRF (alur SPA Sanctum) | FR-10        |
| POST   | `/auth/login`          | Login admin                              | FR-10, FR-14 |
| POST   | `/auth/logout`         | Logout                                   | FR-11        |
| GET    | `/auth/me`             | Data admin yang login                    | FR-12        |

### Admin (wajib autentikasi)

| Method    | Path                                  | Fungsi                                 | Kebutuhan    |
| --------- | ------------------------------------- | -------------------------------------- | ------------ |
| GET       | `/admin/products`                     | Daftar produk semua status             | FR-20        |
| POST      | `/admin/products`                     | Tambah produk                          | FR-21        |
| GET       | `/admin/products/{id}`                | Detail produk (semua status)           | FR-20        |
| PUT/PATCH | `/admin/products/{id}`                | Ubah produk                            | FR-22        |
| DELETE    | `/admin/products/{id}`                | Hapus produk                           | FR-23        |
| POST      | `/admin/products/{id}/sold`           | Tandai terjual                         | FR-24        |
| PATCH     | `/admin/products/{id}/status`         | Sembunyikan/tayangkan/batalkan terjual | FR-25, FR-26 |
| POST      | `/admin/products/{id}/images`         | Unggah foto                            | FR-30–32     |
| PATCH     | `/admin/products/{id}/images/{image}` | Atur foto utama dan urutan             | FR-33        |
| DELETE    | `/admin/products/{id}/images/{image}` | Hapus foto                             | FR-34        |
| POST      | `/admin/categories`                   | Tambah kategori                        | FR-40        |
| PUT/PATCH | `/admin/categories/{id}`              | Ubah kategori                          | FR-40        |
| DELETE    | `/admin/categories/{id}`              | Hapus kategori                         | FR-41        |

---

## 14. Alur Kerja Pengembangan

### 14.1 Strategi branch

- `main`: hanya kode siap rilis; deploy ke produksi dari sini.
- `develop`: tempat semua fitur digabung dan diuji. Jadi _default branch_ di GitHub.
- `feature/<nama>`, `fix/<nama>`, `chore/<nama>`, `docs/<nama>`: dibuat dari `develop`, digabung kembali lewat Pull Request.
- `hotfix/<nama>`: dibuat dari `main` untuk perbaikan darurat, lalu digabung ke `main` **dan** `develop`.

### 14.2 Aturan merge

- Fitur → `develop`: **Squash and merge**.
- `develop` → `main`: **Create a merge commit**, lalu beri tag versi (`v0.x.x`, `v1.0.0` untuk rilis pertama yang dipakai berjualan).
- Semua perubahan lewat Pull Request; _force push_ dan penghapusan branch dilarang pada `main` dan `develop`.
- Pesan commit mengikuti Conventional Commits: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.

### 14.3 CI (GitHub Actions)

Setiap Pull Request menjalankan: Pint (cek format), Larastan (analisis statis), dan seluruh tes. Hasilnya menjadi _status check_ wajib sebelum merge.

---

## 15. Kriteria Selesai (Definition of Done) untuk MVP

- [ ] Pembeli dapat melihat katalog, memfilter/mencari, dan membuka detail produk lewat API
- [ ] Endpoint pengambilan produk berdasarkan daftar slug berfungsi untuk wishlist
- [ ] Admin dapat login dan logout; endpoint admin menolak akses tanpa autentikasi
- [ ] Admin dapat menambah, mengubah, menghapus produk, serta mengunggah dan mengatur foto
- [ ] Admin dapat mengelola kategori beserta `measurement_fields`
- [ ] Menandai produk terjual mengisi `sold_at` dan tercermin di API publik (produk `hidden` tidak pernah tampil)
- [ ] Revalidasi cache ke Next.js berjalan saat produk berubah
- [ ] Feature test untuk endpoint utama lulus dan CI berstatus hijau
- [ ] Dokumentasi API tersedia dan sesuai dengan kode
- [ ] Alur lengkap dapat didemokan end-to-end: admin menambah produk → muncul di katalog → pembeli checkout via WhatsApp → admin menandai terjual → katalog menampilkan label Terjual
- [ ] README menjelaskan cara menjalankan backend secara lokal dan menautkan ke repositori frontend
- [ ] API ter-deploy di `api.pakelagi.works` dengan HTTPS

---

## 16. Roadmap Pengerjaan

- [ ] **Fase 0 — Fondasi:** instalasi Laravel 12, koneksi MySQL, `install:api`, Larastan, repositori Git dengan branch `main` dan `develop`, branch protection.
- [ ] **Fase 1 — Data:** enum, migrasi, model, relasi, dan scope (`categories`, `products`, `product_images`).
- [ ] **Fase 2 — Data contoh:** factory dan seeder (kategori awal + produk contoh).
- [ ] **Fase 3 — API publik:** kategori, daftar produk (filter, cari, urut, paginasi, slugs), detail produk.
- [ ] **Fase 4 — Admin:** autentikasi Sanctum, CRUD produk, status/terjual, upload dan pengelolaan foto, CRUD kategori.
- [ ] **Fase 5 — Integrasi:** revalidasi cache Next.js, CORS dan cookie.
- [ ] **Fase 6 — Kualitas:** feature test, dokumentasi API, workflow CI, README.
- [ ] **Fase 7 — Deploy:** pilih hosting PHP/VPS, konfigurasi produksi, subdomain `api.pakelagi.works`, HTTPS.
- [ ] **Tahap 2 (nanti):** pembayaran online.

Frontend (Next.js) dikerjakan setelah backend selesai.

---

## 17. Pertanyaan Terbuka

| #   | Pertanyaan                                                                                                                        | Dampak                                         |
| --- | --------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| 1   | Struktur repositori: dua repo terpisah (rencana saat ini) atau monorepo satu repo dengan folder `backend/`, `frontend/`, `docs/`? | Struktur proyek, CI, dan deploy                |
| 2   | Estimasi waktu pengerjaan dan target tanggal live?                                                                                | Penjadwalan fase                               |
| 3   | Penyimpanan foto: disk lokal server atau layanan eksternal (misalnya Cloudinary/S3)?                                              | Struktur kode upload dan biaya                 |
| 4   | Hosting backend: shared hosting PHP atau VPS?                                                                                     | Kemampuan queue, worker, dan proses revalidasi |
| 5   | Berapa lama produk terjual tetap tampil di katalog sebelum disembunyikan (BR-05)?                                                 | Logika filter katalog                          |
| 6   | Apakah admin perlu lebih dari satu akun?                                                                                          | Desain tabel `users` dan otorisasi             |
| 7   | Apakah perlu pencatatan modal beli atau harga jual aktual (nego) untuk keperluan pribadi, meski statistik tidak dibuat?           | Kolom tambahan di `products`                   |
| 8   | Cara menangani produk yang sama diminati dua pembeli (reservasi sementara atau cukup manual)?                                     | Aturan bisnis dan status baru                  |
| 9   | Apakah queue (driver database) dipakai untuk revalidasi dan pemrosesan gambar?                                                    | Kebutuhan worker di hosting                    |

---

## 18. Log Keputusan

| Keputusan                                           | Alasan                                                                                               |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Backend Laravel 12 sebagai REST API murni           | Tujuan portofolio: menunjukkan siklus REST lengkap, bukan hanya `GET`.                               |
| Frontend dan halaman admin di Next.js               | SEO dan preview link WhatsApp lebih baik; admin ikut memakai API.                                    |
| Tanpa Filament                                      | Admin dibangun di atas API yang sama agar portofolio memperlihatkan CRUD dan autentikasi lewat REST. |
| Checkout via WhatsApp dulu, pembayaran online nanti | Cepat live; pembayaran online ditunda ke tahap 2.                                                    |
| Wishlist hanya di browser pembeli                   | Tanpa akun pembeli; lebih sederhana.                                                                 |
| Statistik penjualan tidak dibuat                    | Keputusan pemilik proyek.                                                                            |
| Alur Git: `main` + `develop` + branch per fitur     | Kode di `main` terjamin siap rilis.                                                                  |
| Backend dikerjakan lebih dulu, lalu frontend        | API stabil mempermudah frontend.                                                                     |
