# PakeLagi API — Dokumen Kebutuhan (Requirements)

| | |
|---|---|
| **Proyek** | pakelagi.works — toko pakaian bekas (preloved) |
| **Repositori** | `pakelagi-api` (backend) |
| **Versi dokumen** | 0.3 (Draft) |
| **Tanggal** | 5 Oktober 2026 |
| **Dokumen terkait** | [ERD](./erd.md) · [User Flow](./user-flow.md) · [Integrasi Frontend](./frontend-integration.md) |

---

## 1. Deskripsi Proyek

PakeLagi adalah website untuk menjual pakaian bekas (preloved). Repositori ini berisi **backend berupa REST API** yang menyimpan data produk, mengelola foto, dan mengamankan akses admin. Tampilan untuk pembeli dan halaman admin dibangun terpisah di repositori frontend (Next.js) dan mengonsumsi API ini.

Proyek ini punya tiga tujuan sekaligus: **dipakai berjualan sungguhan** setelah di-deploy, **sarana belajar**, dan **karya portofolio**. Karena itu kualitas kode, dokumentasi, dan proses kerja (Git, CI, tes) sama pentingnya dengan fiturnya.

**Status:** MVP (Minimum Viable Product). Scope sengaja dibatasi agar dapat dikerjakan sendiri dan segera dipakai berjualan. Backend dikerjakan lebih dulu, frontend menyusul setelah backend selesai.

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

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 12, **PHP 8.3+** (disyaratkan Intervention Image 4) |
| API | REST, berversi di URL (`/api/v1`), format JSON |
| Authentication | Laravel Sanctum, mode SPA berbasis cookie sesi (domain induk yang sama) |
| Database | MySQL (encoding `utf8mb4`) |
| Pengolahan gambar | Intervention Image 4 lewat `intervention/image-laravel`, driver GD (dukungan WebP wajib aktif) |
| Queue | Laravel Queue, driver `database`; dipakai untuk job `RevalidateFrontendCache`. Membutuhkan proses pekerja (worker) |
| Penyimpanan foto | Laravel Filesystem, disk `public` (lokal) secara bawaan; dapat dialihkan lewat `PRODUCT_IMAGE_DISK` |
| Web Frontend (repo terpisah) | Next.js, TypeScript, Tailwind CSS |
| Testing | PHPUnit/Pest lewat `php artisan test`; database tes terpisah (`pakelagi_test`, MySQL) |
| Kualitas kode | Laravel Pint (format), Larastan (analisis statis) |
| CI | GitHub Actions: Pint, Larastan, dan tes pada setiap Pull Request. Membutuhkan PHP 8.3+, ekstensi GD dengan WebP, dan layanan MySQL |
| Dokumentasi API | Scribe atau OpenAPI/Swagger (dipilih saat implementasi) |
| Version Control | Git & GitHub (`main` + `develop` + branch per fitur) |
| Local Dev Environment | Laragon (PHP, Composer, MySQL) |
| Domain | `pakelagi.works` (frontend), `api.pakelagi.works` (API) |
| Hosting | Hosting PHP 8.3+ atau VPS (belum diputuskan) |
| Pembayaran online (tahap 2) | Midtrans atau Xendit (belum dipilih) |

> **Catatan:** Laravel 12 sudah memasuki fase perbaikan keamanan saja (sampai 24 Februari 2027). Rencanakan upgrade ke Laravel 13 sebagai pekerjaan terjadwal.

---

## 4. Struktur Repository

Rencana saat ini adalah **dua repositori terpisah**: `pakelagi-api` (dokumen ini) dan `pakelagi-web` (Next.js). Struktur `pakelagi-api`:

```
pakelagi-api/
├── app/
│   ├── Actions/
│   │   ├── Categories/              (CreateCategory, UpdateCategory, DeleteCategory)
│   │   └── Products/                (aturan bisnis produk dan foto)
│   ├── Enums/                       (ProductStatus, ProductCondition)
│   ├── Http/
│   │   ├── Controllers/Api/V1/      (publik dan AuthController)
│   │   │   └── Admin/               (Product, ProductImage, Category)
│   │   ├── Requests/                (validasi input)
│   │   └── Resources/               (format output JSON)
│   ├── Jobs/                        (RevalidateFrontendCache)
│   ├── Models/                      (Category, Product, ProductImage)
│   ├── Services/                    (ProductImageProcessor, FrontendCache)
│   └── Support/                     (UniqueSlug)
├── config/
│   └── pakelagi.php                 (akun admin awal, batas foto, revalidasi)
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   └── Feature/                     (Api/V1, Api/V1/Admin, Jobs, Services)
├── docs/                            (requirements, ERD, user flow, integrasi frontend)
├── .github/workflows/ci.yml         (rencana, Fase 6)
├── .env.example
├── AGENTS.md                        (panduan untuk AI coding agent)
└── README.md                        (rencana, Fase 6)
```

Folder `Actions`, `Services`, `Support`, dan `Jobs` bukan bawaan Laravel; kita membuatnya agar controller tetap tipis. Pembagiannya: **Action** menjalankan satu aturan bisnis, **Service** menangani pekerjaan teknis (mengolah gambar, memicu revalidasi), dan **Support** berisi pembantu kecil yang dipakai bersama.

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
- Pemberitahuan ke frontend (revalidasi cache) saat data berubah.

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
- Hanya ada satu peran pengguna terautentikasi: **admin**. Akunnya dibuat lewat seeder dari variabel `.env`, bukan registrasi publik.

---

## 6. Aktor

| Aktor | Deskripsi | Akses |
|---|---|---|
| **Pembeli (tamu)** | Pengunjung yang menjelajah katalog. Tidak perlu akun. | Endpoint publik, hanya baca |
| **Admin** | Pemilik toko yang mengelola produk. | Endpoint publik + endpoint admin (terautentikasi) |
| **Frontend Next.js** | Klien API (sisi publik dan halaman admin). | Memanggil API atas nama pembeli/admin; menerima pemberitahuan revalidasi dari backend |

---

## 7. Entity Relationship (Ringkasan)

Tabel inti:

- `users` — akun admin (dibuat lewat seeder dari `ADMIN_*` di `.env`)
- `categories` — kategori pakaian; menyimpan `measurement_fields`, yaitu daftar jenis ukuran yang relevan per kategori
- `products` — produk unik (stok 1); menyimpan harga (integer rupiah), kondisi, ukuran, status, dan `sold_at`
- `product_images` — foto produk: lokasi file penuh dan thumbnail, urutan, dan penanda foto utama

Relasi: satu kategori punya banyak produk (hapus dibatasi), satu produk punya banyak foto (hapus ikut terhapus).

Detail kolom, tipe data, indeks, dan diagram ada di [`docs/erd.md`](./erd.md).

---

## 8. Alur Utama

```
Admin menambah produk (nama, kategori, harga, ukuran, kondisi)
   ↓
Produk dibuat sebagai draf (status: hidden)
   ↓
Admin mengunggah foto, menentukan foto utama dan urutan
   ↓
Admin menayangkan produk (status: available)
   ↓
Backend mengantrekan pemberitahuan revalidasi ke Next.js
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
Backend mengantrekan pemberitahuan revalidasi ke Next.js
   ↓
Katalog dan halaman produk menampilkan label Terjual
```

Versi diagram lengkap (alur pembeli, alur admin, dan alur teknis) ada di [`docs/user-flow.md`](./user-flow.md).

---

## 9. Aturan Bisnis

| ID | Aturan |
|---|---|
| BR-01 | Setiap produk bersifat **unik (stok 1)**. Tidak ada kolom stok; ketersediaan ditentukan oleh `status`. |
| BR-02 | Status produk hanya salah satu dari: `available`, `sold`, `hidden`. |
| BR-03 | Kondisi produk hanya salah satu dari: `like_new`, `good`, `fair`, ditambah catatan bebas (`condition_notes`) untuk minus. |
| BR-04 | Produk `hidden` **tidak pernah** muncul di API publik (daftar maupun detail; detail dibalas 404). |
| BR-05 | Produk `sold` **tetap bisa dibuka lewat slug** agar link yang sudah dibagikan tidak error. Di katalog, produk terjual selalu berada di urutan bawah. Berapa lama produk terjual tampil sebelum disembunyikan masih pertanyaan terbuka. |
| BR-06 | Saat produk ditandai terjual, sistem mengisi `sold_at` dengan waktu saat itu. |
| BR-07 | `slug` produk dan kategori bersifat unik dan dipakai di URL. |
| BR-08 | Harga disimpan sebagai bilangan bulat dalam rupiah (tanpa desimal), maksimal 100.000.000. |
| BR-09 | Ukuran (`measurements`) dalam cm (1–500) dan kuncinya harus termasuk `measurement_fields` milik kategori produk tersebut. |
| BR-10 | Kategori yang masih memiliki produk, **apa pun statusnya**, tidak boleh dihapus. |
| BR-11 | Menghapus produk ikut menghapus data foto-fotonya, **termasuk file di storage** (dikerjakan lewat kode, bukan hanya cascade database). |
| BR-12 | Setiap produk punya paling banyak satu foto utama (`is_primary`). Foto pertama yang diunggah otomatis menjadi foto utama; bila foto utama dihapus, foto berikutnya (urutan terkecil) dipromosikan. |
| BR-13 | Foto ditampilkan berurutan menurut `sort_order`. |
| BR-14 | Produk baru berstatus `hidden` (draf), kecuali admin memilih `available` saat membuatnya. `sold` tidak bisa dipilih saat membuat produk. |
| BR-15 | `slug` dibuat otomatis dari nama saat dibuat (ditambah `-2`, `-3`, dan seterusnya bila bentrok) dan **tidak berubah** walau nama diubah, agar link yang sudah dibagikan tetap valid. Berlaku untuk produk dan kategori. |
| BR-16 | Produk hanya bisa ditandai terjual dari status `available`. Produk `hidden` harus ditayangkan dulu. Menandai ulang produk yang sudah terjual tidak mengubah apa pun (idempotent, `sold_at` tidak ditimpa). |
| BR-17 | Mengembalikan produk terjual ke `available` mengosongkan `sold_at`; menyembunyikan produk terjual mempertahankannya. |
| BR-18 | `status` tidak bisa diubah lewat endpoint ubah produk. Perubahan hanya lewat endpoint khusus: `/sold` dan `/status` (yang hanya menerima `available` atau `hidden`). |
| BR-19 | Nama kategori unik (divalidasi di aplikasi). `measurement_fields` boleh kosong (mis. aksesori), maksimal 12 jenis, berformat `snake_case` (contoh `lebar_dada`), dan tidak boleh ganda. |
| BR-20 | Mengubah `measurement_fields` suatu kategori tidak mengubah ukuran produk yang sudah ada. BR-09 baru diperiksa saat produk disimpan. |
| BR-21 | Batas foto: format JPG/PNG/WebP, maksimal **2 MB per foto**, **8 foto per unggahan**, **10 foto per produk**, dimensi maksimal 4000 × 4000 px. |
| BR-22 | Semua foto disimpan sebagai **WebP dalam dua ukuran**: penuh (sisi terpanjang maksimal 1600 px) dan thumbnail (480 px), kualitas 80. Foto yang lebih kecil tidak diperbesar. Nama file dibuat oleh server (UUID), disimpan di `products/{id_produk}/`. |

**Risiko yang diketahui:** karena checkout lewat WhatsApp dan stok hanya 1, dua pembeli bisa menghubungi untuk barang yang sama. Keranjang tidak menjamin reservasi; admin yang memutuskan dan menandai barang terjual secara manual.

---

## 10. Prinsip Desain yang Dipegang

1. **Slug sebagai identitas publik** — URL dan API publik memakai `slug`, bukan `id` internal, sehingga link mudah dibaca dan dibagikan. Slug tidak berubah setelah dibuat.
2. **Status berbasis enum, bukan string bebas** — `ProductStatus` dan `ProductCondition` dijaga oleh PHP Enum untuk mencegah data tidak konsisten.
3. **Backend sebagai satu-satunya sumber kebenaran** — validasi dan aturan bisnis (misalnya kecocokan ukuran dengan kategori, visibilitas produk) ada di backend; frontend tidak menduplikasi logika tersebut.
4. **Kategori berbasis data** — jenis ukuran per kategori disimpan di `measurement_fields`, sehingga menambah kategori baru cukup dengan data, tanpa mengubah kode.
5. **Barang terjual tidak dihapus** — produk `sold` dipertahankan agar link yang sudah dibagikan tetap valid dan `sold_at` tercatat.
6. **Operasi admin aman diulang (idempotent)** — menandai produk yang sudah terjual tidak menimpa `sold_at` dan tidak menghasilkan error.
7. **Controller tipis, logika di Action** — controller hanya menerima request dan mengembalikan respons; aturan bisnis di kelas Action yang dapat dites.
8. **Abstraksi pada bagian yang mungkin berganti** — penyimpanan foto lewat Laravel Filesystem dengan disk dari konfigurasi (pindah ke layanan eksternal cukup lewat `.env`), dan pembayaran online di tahap 2 lewat interface gateway agar Midtrans/Xendit dapat ditukar tanpa mengubah logika inti.
9. **API berversi dan konsisten** — `/api/v1`, format respons dan error seragam (error rute `api/*` selalu JSON), kode status HTTP yang tepat.
10. **Harga sebagai snapshot saat pesanan ada** — saat tabel pesanan ditambahkan di tahap 2, harga disalin ke pesanan sehingga perubahan harga produk tidak memengaruhi pesanan yang sudah dibuat.
11. **Efek samping dipicu eksplisit dari Action** — pemberitahuan revalidasi ke frontend dipanggil dari setiap Action yang mengubah data, bukan lewat Model Observer, karena update massal tidak memicu event model dan observer bisa menembak ganda.
12. **Konfigurasi di satu tempat** — semua batas dan pengaturan proyek ada di `config/pakelagi.php` dan dibaca lewat `config()`, bukan `env()` langsung.
13. **Tes tidak menyentuh dunia luar** — tes memakai database terpisah, storage dan antrean palsu, dan `Http::preventStrayRequests()` agar tidak ada panggilan jaringan sungguhan.
14. **Sederhana dulu** — hindari abstraksi yang belum dibutuhkan; refaktor saat kebutuhan nyata muncul.

---

## 11. Kebutuhan Fungsional

Prioritas memakai skala MoSCoW: **M** (Must), **S** (Should), **C** (Could).

### 11.1 API publik

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-01 | Menampilkan daftar kategori beserta `measurement_fields`-nya (tanpa `id` internal). | M |
| FR-02 | Menampilkan daftar produk dengan paginasi (bawaan 12 per halaman, maksimal 50). | M |
| FR-03 | Memfilter produk berdasarkan kategori (slug), ukuran (`size_label`), dan rentang harga. Harga maksimum tidak boleh lebih kecil dari harga minimum. | M |
| FR-04 | Mencari produk berdasarkan kata kunci (maksimal 100 karakter) pada nama, merek, dan deskripsi. | S |
| FR-05 | Mengurutkan produk: `newest` (bawaan), `price_asc`, `price_desc`. Produk terjual selalu di urutan bawah. | S |
| FR-06 | Menampilkan detail produk berdasarkan `slug`, lengkap dengan foto, kondisi, ukuran, dan status. | M |
| FR-07 | Mengambil banyak produk sekaligus berdasarkan daftar slug (maksimal 50) untuk wishlist. Slug yang tidak ada atau tersembunyi diabaikan, bukan menyebabkan error. | M |
| FR-08 | Hanya menampilkan produk yang tidak `hidden` (lihat BR-04, BR-05). | M |
| FR-09 | Endpoint publik dibatasi 60 permintaan per menit per klien (rate limiting, balasan 429). | M |

### 11.2 Autentikasi admin

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-10 | Admin dapat login dengan email dan kata sandi (alur SPA Sanctum: `csrf-cookie` lalu login). ID sesi diganti setelah login. | M |
| FR-11 | Admin dapat logout (sesi dihapus, token CSRF diganti). | M |
| FR-12 | Frontend dapat mengambil data admin yang sedang login (`me`, hanya nama dan email). | M |
| FR-13 | Semua endpoint admin menolak permintaan tanpa autentikasi (401, JSON). | M |
| FR-14 | Percobaan login dibatasi **5 kali per menit** untuk kombinasi email dan IP yang sama (balasan 429). | M |
| FR-15 | Pesan gagal login tidak membedakan email salah dan kata sandi salah. Permintaan login yang tidak berasal dari domain frontend terdaftar ditolak (400). | M |

### 11.3 Manajemen produk (admin)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-20 | Menampilkan semua produk termasuk `hidden` dan `sold`, dengan filter yang sama seperti katalog publik ditambah filter `status` (bawaan 20 per halaman, maksimal 50). | M |
| FR-21 | Menambah produk; `slug` dibuat otomatis dan dijamin unik; status awal `hidden` (draf) kecuali dipilih `available` (BR-14, BR-15). | M |
| FR-22 | Mengubah produk dengan `PUT` (penggantian penuh: semua kolom wajib dikirim ulang). `slug` dan `status` tidak berubah. | M |
| FR-23 | Menghapus produk (lihat BR-11). | M |
| FR-24 | Menandai produk sebagai terjual hanya dari `available` (mengisi `sold_at`; idempotent). Lihat BR-06 dan BR-16. | M |
| FR-25 | Menyembunyikan dan menayangkan kembali produk (`hidden` ↔ `available`) lewat endpoint status. | M |
| FR-26 | Membatalkan status terjual (kembali ke `available`, `sold_at` dikosongkan) bila pembelian batal. | S |

### 11.4 Manajemen foto (admin)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-30 | Mengunggah satu atau banyak foto untuk sebuah produk (maksimal 8 per unggahan, 10 per produk). Jika pemrosesan gagal di tengah jalan, file yang sudah tersimpan dibersihkan. | M |
| FR-31 | Memvalidasi file unggahan: format JPG/PNG/WebP, maksimal 2 MB per foto, dimensi maksimal 4000 × 4000 px. Frontend mengompres foto sebelum dikirim. Batas PHP/server lebih longgar daripada aturan Laravel. | M |
| FR-32 | Menyimpan setiap foto dalam dua ukuran WebP (penuh dan thumbnail) agar katalog ringan (BR-22). | S |
| FR-33 | Menentukan foto utama (`PATCH`, hanya `is_primary: true`) dan mengatur urutan seluruh foto (`PUT .../images/order` dengan daftar ID lengkap). | M |
| FR-34 | Menghapus foto (data dan kedua file). | M |
| FR-35 | API publik menampilkan URL foto dan thumbnail tanpa membocorkan `id` foto; API admin menyertakan `id` foto. | M |

### 11.5 Manajemen kategori (admin)

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-40 | Menambah dan mengubah kategori beserta `measurement_fields`. Slug dibuat saat dibuat dan tetap (BR-15, BR-19). | M |
| FR-41 | Menghapus kategori hanya jika tidak punya produk apa pun (BR-10); bila gagal, pesan menyebut jumlah produk (422). | M |
| FR-42 | Kategori awal: Atasan, Bawahan, Outer, Aksesori (diisi lewat `CategorySeeder`, aman dijalankan berulang). | M |
| FR-43 | Daftar kategori untuk admin menyertakan `id` dan `products_count`, agar halaman admin tahu kategori mana yang bisa dihapus. | M |

### 11.6 Integrasi dengan frontend

| ID | Kebutuhan | Prioritas |
|---|---|---|
| FR-50 | Setiap Action yang mengubah data produk atau kategori mengantrekan job `RevalidateFrontendCache` berisi **tag cache** (`catalog`, `product:{slug}`, `categories`). Job dikirim setelah transaksi commit, diulang 3 kali (jeda 10 dan 60 detik), dan kegagalan akhir dicatat di log tanpa mengganggu admin. Fitur mati bila URL atau secret kosong. Operasi yang tidak mengubah data tidak memicu job. Kontrak lengkap ada di [Integrasi Frontend](./frontend-integration.md). | M |
| FR-51 | CORS dan cookie autentikasi dikonfigurasi agar hanya domain frontend yang diizinkan (`FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`). | M |

---

## 12. Kebutuhan Non-Fungsional

### 12.1 Keamanan

- Validasi semua input lewat Form Request; lindungi dari *mass assignment* dengan `$fillable`.
- Rate limiting: login 5 per menit per email dan IP, endpoint publik 60 per menit.
- Login berbasis cookie `httpOnly`; ID sesi diganti setelah login; pesan gagal login tidak membocorkan email mana yang terdaftar.
- Unggahan file divalidasi (tipe MIME, ekstensi, ukuran, dimensi); nama file dibuat ulang oleh server, bukan memakai nama dari pengguna. Foto di-encode ulang; opsi penghapusan metadata (EXIF/GPS) pada konfigurasi gambar perlu diverifikasi sebelum rilis.
- Batas PHP dan web server (`upload_max_filesize`, `post_max_size`, dan `client_max_body_size` pada Nginx) harus lebih longgar daripada aturan validasi Laravel, supaya Laravel yang menolak dengan pesan yang jelas.
- Rahasia (`.env`, `REVALIDATE_SECRET`, kata sandi admin) tidak pernah masuk Git; sediakan `.env.example`.
- HTTPS di produksi; cookie autentikasi ditandai `secure`.
- Respons error tidak membocorkan detail internal (`APP_DEBUG=false` di produksi); error rute `api/*` selalu berbentuk JSON.

### 12.2 Performa

- Indeks pada kolom yang dipakai filter (`status`, `category_id`, `price`).
- Semua daftar menggunakan paginasi dengan batas maksimum `per_page` (50).
- Hindari *N+1 query* dengan *eager loading* (kategori, foto utama, atau semua foto sesuai kebutuhan).
- Daftar produk memakai resource ringkas (tanpa deskripsi dan seluruh foto).
- Foto dikompres sebagai WebP dalam dua ukuran.

### 12.3 Kualitas dan keterpeliharaan

- Controller tipis; aturan bisnis di kelas Action, pekerjaan teknis di Service.
- Validasi lewat Form Request, format output lewat API Resources.
- Status dan kondisi memakai PHP Enum.
- Filter katalog dikumpulkan sebagai query scope di model.
- Format kode otomatis dengan Pint, analisis statis dengan Larastan.
- Feature test untuk setiap endpoint, termasuk jalur gagal (401, 404, 422, 429); CI menolak merge bila tes atau lint gagal.
- Migrasi yang sudah digabung ke `develop` tidak diubah; perubahan struktur lewat migrasi baru.

### 12.4 Konsistensi API

- Versi di URL (`/api/v1`).
- Format respons dan format error yang seragam.
- Kode status HTTP yang tepat (200, 201, 204, 400, 401, 404, 422, 429).
- Dokumentasi API otomatis yang selalu sinkron dengan kode (Fase 6).

### 12.5 Kompatibilitas

- API melayani frontend yang **responsif** (mobile-first); respons harus ringan karena sebagian besar pembeli memakai HP.
- Server produksi harus menjalankan PHP 8.3+ dengan ekstensi GD (dukungan WebP).

---

## 13. Gambaran Endpoint

Semua rute berawalan `/api/v1` (kecuali `csrf-cookie`). Pada rute admin, `{product}`, `{image}`, dan `{category}` adalah **ID angka**; pada rute publik, `{slug}` adalah slug produk.

### Publik

| Method | Path | Fungsi | Kebutuhan |
|---|---|---|---|
| GET | `/categories` | Daftar kategori | FR-01 |
| GET | `/products` | Daftar produk (parameter: `category`, `size`, `min_price`, `max_price`, `q`, `sort`, `page`, `per_page`, `slugs`) | FR-02–05, FR-07–09 |
| GET | `/products/{slug}` | Detail produk | FR-06 |

### Autentikasi

| Method | Path | Fungsi | Kebutuhan |
|---|---|---|---|
| GET | `/sanctum/csrf-cookie` | Mengambil cookie CSRF (alur SPA Sanctum; tanpa awalan `/api/v1`) | FR-10 |
| POST | `/auth/login` | Login admin | FR-10, FR-14, FR-15 |
| POST | `/auth/logout` | Logout | FR-11 |
| GET | `/auth/me` | Data admin yang login | FR-12 |

### Admin — produk (wajib autentikasi)

| Method | Path | Fungsi | Kebutuhan |
|---|---|---|---|
| GET | `/admin/products` | Daftar produk semua status | FR-20 |
| POST | `/admin/products` | Tambah produk | FR-21 |
| GET | `/admin/products/{product}` | Detail produk (semua status) | FR-20 |
| PUT/PATCH | `/admin/products/{product}` | Ubah produk (penggantian penuh) | FR-22 |
| DELETE | `/admin/products/{product}` | Hapus produk | FR-23 |
| POST | `/admin/products/{product}/sold` | Tandai terjual | FR-24 |
| PATCH | `/admin/products/{product}/status` | Sembunyikan/tayangkan/batalkan terjual | FR-25, FR-26 |

### Admin — foto

| Method | Path | Fungsi | Kebutuhan |
|---|---|---|---|
| POST | `/admin/products/{product}/images` | Unggah foto | FR-30–32 |
| PUT | `/admin/products/{product}/images/order` | Atur urutan seluruh foto | FR-33 |
| PATCH | `/admin/products/{product}/images/{image}` | Jadikan foto utama | FR-33 |
| DELETE | `/admin/products/{product}/images/{image}` | Hapus foto | FR-34 |

### Admin — kategori

| Method | Path | Fungsi | Kebutuhan |
|---|---|---|---|
| GET | `/admin/categories` | Daftar kategori dengan `id` dan `products_count` | FR-43 |
| POST | `/admin/categories` | Tambah kategori | FR-40 |
| PUT/PATCH | `/admin/categories/{category}` | Ubah kategori | FR-40 |
| DELETE | `/admin/categories/{category}` | Hapus kategori | FR-41 |

---

## 14. Alur Kerja Pengembangan

### 14.1 Strategi branch

- `main`: hanya kode siap rilis; deploy ke produksi dari sini.
- `develop`: tempat semua fitur digabung dan diuji. Jadi *default branch* di GitHub, sehingga Pull Request otomatis mengarah ke sana. Tetap periksa kolom **base** sebelum membuat Pull Request.
- `feature/<nama>`, `fix/<nama>`, `chore/<nama>`, `docs/<nama>`: dibuat dari `develop`, digabung kembali lewat Pull Request.
- `hotfix/<nama>`: dibuat dari `main` untuk perbaikan darurat, lalu digabung ke `main` **dan** `develop`.

### 14.2 Aturan merge

- Fitur → `develop`: **Squash and merge**.
- `develop` → `main`: **Create a merge commit**, lalu beri tag versi (`v0.x.x`, `v1.0.0` untuk rilis pertama yang dipakai berjualan).
- Semua perubahan lewat Pull Request; *force push* dan penghapusan branch dilarang pada `main` dan `develop`.
- Pesan commit mengikuti Conventional Commits: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`.

### 14.3 CI (GitHub Actions)

Setiap Pull Request menjalankan: Pint (cek format), Larastan (analisis statis), dan seluruh tes. Hasilnya menjadi *status check* wajib sebelum merge. Karena tes memakai MySQL dan memproses gambar, workflow membutuhkan: PHP 8.3 atau lebih baru dengan ekstensi GD (WebP), layanan MySQL untuk database tes, dan `.env` tes yang tidak memuat URL revalidasi.

---

## 15. Kriteria Selesai (Definition of Done) untuk MVP

- [ ] Pembeli dapat melihat katalog, memfilter/mencari, dan membuka detail produk lewat API
- [ ] Endpoint pengambilan produk berdasarkan daftar slug berfungsi untuk wishlist
- [ ] Admin dapat login dan logout; endpoint admin menolak akses tanpa autentikasi
- [ ] Admin dapat menambah, mengubah, menghapus produk, serta mengunggah dan mengatur foto
- [ ] Admin dapat mengelola kategori beserta `measurement_fields`
- [ ] Menandai produk terjual mengisi `sold_at` dan tercermin di API publik (produk `hidden` tidak pernah tampil)
- [ ] Revalidasi cache ke Next.js berjalan saat data berubah, dengan pekerja queue aktif di produksi
- [ ] Feature test untuk seluruh endpoint lulus dan CI berstatus hijau
- [ ] Dokumentasi API tersedia dan sesuai dengan kode
- [ ] Alur lengkap dapat didemokan end-to-end: admin menambah produk → muncul di katalog → pembeli checkout via WhatsApp → admin menandai terjual → katalog menampilkan label Terjual
- [ ] README menjelaskan cara menjalankan backend secara lokal dan menautkan ke repositori frontend
- [ ] API ter-deploy di `api.pakelagi.works` dengan HTTPS dan PHP 8.3+

---

## 16. Roadmap Pengerjaan

- [ ] **Fase 0 — Fondasi**
  - [x] Instalasi Laravel 12, koneksi MySQL, `install:api`
  - [x] Repositori Git dengan branch `main` dan `develop`
  - [ ] Default branch `develop` dan branch protection di GitHub
  - [ ] Larastan terpasang dan dikonfigurasi (`phpstan.neon`)
- [x] **Fase 1 — Data:** enum, migrasi, model, relasi, dan scope (`categories`, `products`, `product_images`).
- [x] **Fase 2 — Data contoh:** factory dan seeder (kategori awal, produk contoh, akun admin dari `.env`).
- [x] **Fase 3 — API publik:** kategori, daftar produk (filter, cari, urut, paginasi, slugs), detail produk.
- [x] **Fase 4 — Admin:** autentikasi Sanctum, CRUD produk, status/terjual, upload dan pengelolaan foto, CRUD kategori.
- [ ] **Fase 5 — Integrasi:** revalidasi cache Next.js (queue job dan tag), CORS dan cookie. Sisi backend sudah dikerjakan; centang setelah tes lulus dan PR digabung. Sisi Next.js menyusul bersama frontend.
- [ ] **Fase 6 — Kualitas:** dokumentasi API otomatis, workflow CI, README.
- [ ] **Fase 7 — Deploy:** pilih hosting (PHP 8.3+), konfigurasi produksi, pekerja queue, subdomain `api.pakelagi.works`, HTTPS.
- [ ] **Tahap 2 (nanti):** pembayaran online.

Frontend (Next.js) dikerjakan setelah backend selesai.

---

## 17. Pertanyaan Terbuka

| # | Pertanyaan | Dampak |
|---|---|---|
| 1 | Struktur repositori: dua repo terpisah (rencana saat ini) atau monorepo satu repo dengan folder `backend/`, `frontend/`, `docs/`? | Struktur proyek, CI, dan deploy |
| 2 | Estimasi waktu pengerjaan dan target tanggal live? | Penjadwalan fase |
| 3 | Penyimpanan foto di produksi: disk lokal server atau layanan eksternal (Cloudinary/S3)? Kode sudah siap lewat `PRODUCT_IMAGE_DISK`. | Biaya dan konfigurasi deploy |
| 4 | Hosting backend: shared hosting atau VPS? Wajib mendukung PHP 8.3+ dengan GD/WebP. | Kemampuan menjalankan worker queue dan versi PHP |
| 5 | Berapa lama produk terjual tetap tampil di katalog sebelum disembunyikan (BR-05)? Saat ini tampil selamanya di urutan bawah. | Logika filter katalog |
| 6 | Apakah admin perlu lebih dari satu akun? | Desain tabel `users` dan otorisasi |
| 7 | Apakah perlu pencatatan modal beli atau harga jual aktual (nego) untuk keperluan pribadi, meski statistik tidak dibuat? | Kolom tambahan di `products` |
| 8 | Cara menangani produk yang sama diminati dua pembeli (reservasi sementara atau cukup manual)? | Aturan bisnis dan status baru |
| 9 | Cara menjalankan pekerja queue di produksi: Supervisor (VPS), cron (shared hosting), atau driver `sync`? | Keandalan pemberitahuan revalidasi |
| 10 | Apakah `size_label` perlu daftar baku (S, M, L, XL, All size)? Saat ini teks bebas sehingga "M", "m", dan "Medium" dianggap berbeda oleh filter. | Validasi dan filter katalog |
| 11 | Kapan upgrade ke Laravel 13? Laravel 12 hanya menerima perbaikan keamanan sampai 24 Februari 2027. | Jadwal pemeliharaan |
| 12 | Opsi penghapusan metadata gambar (`strip`) pada konfigurasi Intervention Image: apakah foto hasil olahan benar-benar bebas data GPS? | Privasi |

---

## 18. Log Keputusan

| Keputusan | Alasan |
|---|---|
| Backend Laravel 12 sebagai REST API murni | Tujuan portofolio: menunjukkan siklus REST lengkap, bukan hanya `GET`. |
| Frontend dan halaman admin di Next.js | SEO dan preview link WhatsApp lebih baik; admin ikut memakai API. |
| Tanpa Filament | Admin dibangun di atas API yang sama agar portofolio memperlihatkan CRUD dan autentikasi lewat REST. |
| Checkout via WhatsApp dulu, pembayaran online nanti | Cepat live; pembayaran online ditunda ke tahap 2. |
| Wishlist hanya di browser pembeli | Tanpa akun pembeli; lebih sederhana. |
| Statistik penjualan tidak dibuat | Keputusan pemilik proyek. |
| Alur Git: `main` + `develop` + branch per fitur | Kode di `main` terjamin siap rilis. |
| Backend dikerjakan lebih dulu, lalu frontend | API stabil mempermudah frontend. |
| Foto: maksimal 2 MB per foto, 8 per unggahan | Kesepakatan dengan pemilik proyek; batas total 10 per produk adalah usulan tambahan. |
| Foto disimpan sebagai WebP dalam dua ukuran (1600 px dan 480 px) | Katalog ringan untuk pembeli di HP; thumbnail dibuat server. |
| Intervention Image 4 (`decode()`, `WebpEncoder`), mensyaratkan PHP 8.3+ | Versi yang terpasang; API berbeda dari versi 3. Hosting dan CI harus PHP 8.3+. |
| Login admin berbasis cookie sesi Sanctum | Cookie `httpOnly` lebih tahan pencurian sesi lewat XSS dibanding token di localStorage. |
| Produk baru berstatus draf (`hidden`) | Foto belum ada saat produk dibuat; admin menayangkan setelah selesai. |
| Slug produk dan kategori tidak berubah | Link yang sudah dibagikan tetap valid. |
| `UniqueSlug` dipakai bersama produk dan kategori | Satu aturan di satu tempat (DRY). |
| Revalidasi memakai tag cache lewat queue job, dipicu dari Action | Backend tidak perlu tahu URL frontend; admin tidak ikut tertahan bila frontend mati. |
| Kategori tidak bisa dihapus selama masih punya produk (semua status) | Menjaga integritas data; pesan jelas lewat 422 alih-alih error database. |