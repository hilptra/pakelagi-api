# AGENTS.md — Panduan untuk AI Coding Agent (pakelagi-api)

File ini dibaca oleh AI coding agent (dan manusia) sebelum mengerjakan repositori ini. Isinya aturan kerja, konvensi kode, dan batasan proyek. Detail lengkap ada di folder `docs/`; file ini adalah ringkasan yang harus selalu diikuti.

## 1. Gambaran Proyek

`pakelagi-api` adalah backend REST API untuk **pakelagi.works**, toko pakaian bekas (preloved). Frontend (Next.js) dan halaman admin ada di repositori terpisah (`pakelagi-web`) dan mengonsumsi API ini. Proyek ini dipakai berjualan sungguhan, sekaligus sarana belajar dan portofolio.

**Sumber kebenaran** (baca sebelum membuat perubahan besar):

- `docs/requirements.md` — kebutuhan, aturan bisnis (BR-xx), kebutuhan fungsional (FR-xx), daftar endpoint, roadmap, pertanyaan terbuka
- `docs/erd.md` — struktur database dan enum
- `docs/user-flow.md` — alur pembeli, admin, dan alur teknis

Jika kode yang diminta bertentangan dengan dokumen di atas, **hentikan dan tanyakan** pemilik proyek, atau perbarui dokumen dalam perubahan yang sama bila memang keputusan berubah.

## 2. Tech Stack

- Laravel 12, PHP 8.2+ (Laravel 13 butuh PHP 8.3+; upgrade dijadwalkan terpisah)
- MySQL (`utf8mb4`)
- Laravel Sanctum untuk autentikasi admin (berbasis cookie untuk SPA)
- Pest/PHPUnit untuk tes, Laravel Pint untuk format, Larastan untuk analisis statis
- Queue: driver database (rencana)
- API berversi di URL: `/api/v1`

## 3. Perintah Umum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed       # buat tabel dan isi data contoh
php artisan serve                # jalankan server lokal

php artisan test                 # jalankan semua tes
./vendor/bin/pint                # rapikan format kode
./vendor/bin/pint --test         # cek format tanpa mengubah file (dipakai CI)
./vendor/bin/phpstan analyse     # analisis statis (setelah phpstan.neon dikonfigurasi)
```

## 4. Struktur dan Konvensi Kode

```
app/Actions/                 aturan bisnis (mis. MarkProductAsSold)
app/Enums/                   ProductStatus, ProductCondition
app/Http/Controllers/Api/V1/ controller tipis
app/Http/Requests/           validasi input (Form Request)
app/Http/Resources/          format output JSON (API Resource)
app/Models/                  Category, Product, ProductImage
```

Aturan:

- **Controller tipis.** Controller hanya menerima request, memanggil Action/Service, dan mengembalikan respons. Jangan taruh aturan bisnis di controller.
- **Validasi lewat Form Request**, bukan `$request->validate()` di controller.
- **Output lewat API Resource.** Jangan mengembalikan model mentah dari endpoint.
- **Status dan kondisi memakai Enum** (`ProductStatus`, `ProductCondition`), bukan string literal tersebar.
- **Filter katalog sebagai query scope** di model (contoh: `Product::visible()`).
- **Model:** definisikan `$fillable`, gunakan method `casts()`, dan deklarasikan tipe kembalian relasi.
- **Hindari N+1 query:** gunakan eager loading (`with('category', 'images')`).
- **Semua daftar dipaginasi** dengan batas maksimum `per_page`.
- **Penamaan:** kode, nama class/variabel, dan kolom database dalam bahasa Inggris. Tabel jamak dan `snake_case` (`product_images`), class `PascalCase`, variabel dan method `camelCase`.
- **Identitas publik memakai `slug`**, bukan `id` internal, pada endpoint publik.
- **Harga** adalah integer rupiah (tanpa desimal). Jangan memakai float.
- **Penyimpanan file lewat Laravel Filesystem** (`Storage`), jangan menulis path disk secara langsung, agar bisa dialihkan ke layanan eksternal.
- **Sederhana dulu.** Jangan menambah lapisan abstraksi yang belum dibutuhkan (misalnya repository pattern untuk setiap model). Refaktor saat kebutuhan nyata muncul.

## 5. Aturan Bisnis Kritis

Ringkasan dari `docs/requirements.md` (bagian 9). Jangan dilanggar:

- Setiap produk **unik (stok 1)**; tidak ada kolom stok. Ketersediaan ditentukan `status`.
- Status hanya `available`, `sold`, `hidden`. Kondisi hanya `like_new`, `good`, `fair`.
- Produk `hidden` **tidak pernah** muncul di API publik. Produk `sold` **tetap bisa dibuka lewat slug**.
- Menandai terjual mengisi `sold_at`; operasi ini **idempotent** (menandai ulang tidak menimpa `sold_at`).
- `measurements` harus sesuai `measurement_fields` milik kategori produk.
- Kategori yang masih punya produk tidak boleh dihapus.
- Menghapus produk atau foto harus ikut menghapus **file di storage**, bukan hanya baris database.
- Maksimal satu foto utama (`is_primary`) per produk.

## 6. Keamanan

- Jangan pernah meng-commit `.env`, kunci, atau kata sandi. Perbarui `.env.example` bila menambah variabel.
- Validasi semua input. Jangan menonaktifkan perlindungan *mass assignment*.
- Unggahan file: validasi tipe MIME, ekstensi, dan ukuran; buat nama file baru di server.
- Pasang rate limiting pada login dan endpoint publik.
- Endpoint admin wajib di balik autentikasi (`auth:sanctum`).
- Jangan membocorkan detail internal di respons error; `APP_DEBUG=false` di produksi.

## 7. Testing

- Setiap endpoint atau Action baru **wajib** disertai tes (feature test untuk endpoint).
- Gunakan factory untuk data tes dan `RefreshDatabase` agar tes saling terisolasi.
- Uji juga jalur gagal: tanpa autentikasi (401), validasi gagal (422), data tidak ada (404).
- Jalankan `php artisan test` sebelum menyatakan pekerjaan selesai.

## 8. Alur Git

- Branch utama: `main` (siap rilis) dan `develop` (integrasi dan pengujian). **Jangan commit atau push langsung ke `main` maupun `develop`.**
- Buat branch dari `develop` dengan nama `feature/<nama>`, `fix/<nama>`, `chore/<nama>`, atau `docs/<nama>`.
- Pesan commit mengikuti Conventional Commits (bahasa Inggris): `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`. Contoh: `feat: add product model and migrations`.
- Satu branch untuk satu fitur; commit kecil dan fokus.
- Perubahan masuk lewat Pull Request ke `develop`. Agent **tidak** melakukan merge sendiri dan tidak melakukan *force push*.
- Migrasi yang sudah digabung ke `develop` **tidak boleh diubah**; buat migrasi baru.

## 9. Cara Bekerja dengan Pemilik Proyek

- **Pemilik proyek sedang belajar.** Setiap kali memberi kode, fungsi, atau logika, jelaskan apa fungsinya dan mengapa dibuat begitu, bukan hanya kodenya.
- Penjelasan dalam bahasa Indonesia; kode, identifier, dan pesan commit dalam bahasa Inggris.
- Utamakan praktik terbaik dan kode bersih agar proyek mudah dikembangkan.
- Jika ada keputusan arsitektur yang belum ditetapkan (lihat "Pertanyaan Terbuka" di `docs/requirements.md`), **tanyakan dulu**; jangan memutuskan sendiri.
- Bila perubahan memengaruhi struktur tabel, endpoint, atau alur, perbarui `docs/` pada perubahan yang sama.

## 10. Di Luar Scope (Jangan Dikerjakan Kecuali Diminta)

- Pembayaran online (tahap 2)
- Akun pembeli dan wishlist di database (wishlist ada di browser pembeli)
- Statistik penjualan (diputuskan tidak dibuat)
- Ongkos kirim otomatis, reservasi barang, Redis, deploy otomatis, aplikasi mobile

## 11. Kriteria Selesai untuk Satu Tugas

- [ ] Sesuai `docs/requirements.md` dan aturan bisnis di atas
- [ ] Ada tes untuk perubahan baru dan `php artisan test` lulus
- [ ] `./vendor/bin/pint --test` bersih
- [ ] `./vendor/bin/phpstan analyse` bersih (bila sudah dikonfigurasi)
- [ ] Dokumentasi di `docs/` diperbarui bila struktur tabel atau endpoint berubah
- [ ] Dikerjakan di branch yang benar dan siap di-Pull Request ke `develop`
- [ ] Kode yang diberikan disertai penjelasan