# PakeLagi API — User Flow

| | |
|---|---|
| **Versi dokumen** | 0.2 (Draft) |
| **Tanggal** | 5 Oktober 2026 |
| **Dokumen terkait** | [Requirements](./requirements.md) · [ERD](./erd.md) · [Integrasi Frontend](./frontend-integration.md) |

Dokumen ini menggambarkan alur penggunaan dari sudut pandang **pembeli** dan **admin**, serta alur teknis antara frontend (Next.js) dan API (Laravel). Diagram memakai Mermaid, yang tampil otomatis di GitHub.

## 1. Alur Pembeli (Tanpa Akun)

Pembeli menjelajah katalog, menyimpan barang ke wishlist atau keranjang (disimpan di browser), lalu menyelesaikan pesanan lewat WhatsApp. Backend hanya dipakai untuk membaca data produk.

```mermaid
flowchart TD
    A(["Pembeli membuka link atau situs"]) --> B["Melihat katalog produk"]
    B --> C{"Ingin menyaring atau mencari?"}
    C -- "Ya" --> D["Filter kategori, ukuran, harga atau kata kunci"]
    D --> B
    C -- "Tidak" --> E["Memilih produk"]
    E --> F["Membuka detail produk"]
    F --> G{"Status produk?"}
    G -- "sold" --> H["Melihat label Terjual dan kembali ke katalog"]
    H --> B
    G -- "available" --> I["Melihat foto, kondisi, ukuran dalam cm"]
    I --> J{"Keputusan"}
    J -- "Belum yakin" --> K["Simpan ke wishlist di browser"]
    K --> B
    J -- "Mau beli" --> L["Tambah ke keranjang"]
    L --> M["Klik Checkout via WhatsApp"]
    M --> N["WhatsApp terbuka dengan pesan berisi daftar barang"]
    N --> O["Pembeli mengirim pesan ke penjual"]
    O --> P(["Negosiasi, pembayaran, dan pengiriman diurus di luar situs"])
```

**Catatan:**
- Katalog menampilkan produk tersedia lebih dulu; produk terjual berada di urutan bawah dengan label Terjual. Produk tersembunyi (`hidden`) tidak pernah tampil, dan membuka linknya menghasilkan halaman tidak ditemukan.
- Wishlist dan keranjang disimpan di browser (localStorage). Saat membuka wishlist, frontend memanggil `GET /products?slugs=a,b,c`; produk yang sudah terjual tetap tampil dengan label, sedangkan produk yang sudah dihapus atau disembunyikan diabaikan.
- Pembeli tidak membuat akun dan tidak membayar lewat situs pada versi 1.
- Dua pembeli bisa menghubungi untuk barang yang sama; admin yang memutuskan dan menandai terjual.

## 2. Alur Admin

Admin masuk, menyiapkan produk (dibuat sebagai draf, diberi foto, lalu ditayangkan), dan menandai barang terjual setelah transaksi selesai di WhatsApp.

```mermaid
flowchart TD
    A(["Admin membuka halaman admin"]) --> B{"Sudah login?"}
    B -- "Belum" --> C["Halaman login: email dan kata sandi"]
    C --> D{"Kredensial benar?"}
    D -- "Tidak" --> E["Pesan error, maksimal 5 percobaan per menit"]
    E --> C
    D -- "Ya" --> F["Daftar produk admin"]
    B -- "Sudah" --> F
    F --> G{"Pilih tindakan"}

    G -- "Tambah produk" --> H["Isi form: nama, kategori, harga, ukuran, kondisi"]
    H --> I["Isi ukuran dalam cm sesuai kategori"]
    I --> J["Simpan: produk dibuat sebagai draf hidden"]
    J --> K["Unggah foto, atur foto utama dan urutan"]
    K --> L["Tayangkan: status menjadi available"]
    L --> F

    G -- "Ubah produk" --> M["Edit data produk, slug dan status tidak berubah"]
    M --> F

    G -- "Kelola foto" --> N["Unggah, jadikan utama, urutkan, atau hapus foto"]
    N --> F

    G -- "Tandai terjual" --> O{"Status produk?"}
    O -- "available" --> P["Status sold, sold_at terisi"]
    O -- "hidden" --> Q["Ditolak: tayangkan dulu"]
    O -- "sold" --> R["Tidak ada perubahan"]
    P --> F
    Q --> F
    R --> F

    G -- "Sembunyikan atau tayangkan" --> S["Ubah status hidden atau available"]
    S --> F

    G -- "Hapus produk" --> T["Konfirmasi hapus: data dan file foto dihapus"]
    T --> F

    G -- "Kelola kategori" --> U["Tambah, ubah, atau hapus kategori"]
    U --> F

    G -- "Logout" --> V(["Kembali ke halaman login"])
```

**Catatan:**
- Produk yang baru dibuat berstatus draf (`hidden`) karena fotonya belum ada; admin menayangkannya setelah siap. Admin juga boleh memilih langsung `available` saat membuat.
- Setiap perubahan data memicu pemberitahuan revalidasi ke frontend (lihat bagian 6).
- Maksimal 5 percobaan login per menit untuk kombinasi email dan IP yang sama.

## 3. Siklus Hidup Produk

```mermaid
stateDiagram-v2
    [*] --> hidden: dibuat (bawaan, sebagai draf)
    [*] --> available: dibuat dan langsung tayang
    hidden --> available: ditayangkan
    available --> hidden: ditarik sementara
    available --> sold: ditandai terjual
    sold --> available: pembelian batal, sold_at dikosongkan
    sold --> hidden: disembunyikan, sold_at dipertahankan
```

Perilaku di API publik: `available` tampil normal, `sold` tetap bisa dibuka lewat slug dengan label terjual (dan berada di urutan bawah katalog), `hidden` tidak pernah tampil. Transisi `hidden` → `sold` tidak diizinkan.

## 4. Alur Kategori

Kategori dapat ditambah dan diubah kapan saja. Slug tidak berubah setelah dibuat, sedangkan penghapusan dibatasi.

```mermaid
flowchart TD
    A(["Admin menghapus kategori"]) --> B{"Masih punya produk?"}
    B -- "Ya, termasuk hidden dan sold" --> C["Ditolak 422, pesan menyebut jumlah produk"]
    B -- "Tidak" --> D["Kategori dihapus"]
    D --> E["Antrekan revalidasi: categories dan catalog"]
```

Mengubah `measurement_fields` suatu kategori tidak mengubah ukuran produk yang sudah ada; aturan kecocokan ukuran baru diperiksa saat sebuah produk disimpan.

## 5. Alur Teknis: Login Admin

Memakai Sanctum berbasis cookie (SPA), sehingga frontend dan API perlu berada di domain induk yang sama (`pakelagi.works` dan `api.pakelagi.works`).

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Next.js Admin
    participant API as Laravel API

    Admin->>FE: Isi email dan kata sandi
    FE->>API: GET /sanctum/csrf-cookie
    API-->>FE: Set cookie XSRF-TOKEN
    FE->>API: POST /api/v1/auth/login
    alt Kredensial benar
        API-->>FE: 200 OK + cookie sesi (ID sesi diganti)
        FE->>API: GET /api/v1/auth/me
        API-->>FE: Nama dan email admin
        FE-->>Admin: Masuk ke daftar produk
    else Kredensial salah
        API-->>FE: 422 pesan validasi (tidak membedakan email atau kata sandi)
        FE-->>Admin: Tampilkan error
    else Terlalu banyak percobaan
        API-->>FE: 429 Too Many Requests
        FE-->>Admin: Minta menunggu
    else Permintaan bukan dari frontend terdaftar
        API-->>FE: 400 Bad Request
    end
```

## 6. Alur Teknis: Tandai Terjual dan Revalidasi Cache

Next.js menyimpan cache halaman produk. Tanpa revalidasi, halaman publik bisa masih menampilkan "tersedia" setelah barang terjual. Pemberitahuan dikirim lewat queue, setelah database menyimpan perubahan, sehingga admin tidak menunggu frontend.

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Next.js Admin
    participant API as Laravel API
    participant DB as MySQL
    participant Q as Queue
    participant Web as Next.js Publik

    Admin->>FE: Klik Tandai terjual
    FE->>API: POST /api/v1/admin/products/{id}/sold
    API->>API: Cek autentikasi dan aturan bisnis
    API->>DB: UPDATE status = sold, sold_at = sekarang
    DB-->>API: Berhasil (commit)
    API->>Q: Antrekan RevalidateFrontendCache (catalog, product:slug)
    API-->>FE: 200 OK + data produk terbaru
    FE-->>Admin: Tampilkan status Terjual
    Q->>Web: POST /api/revalidate (Bearer secret, tags)
    Web-->>Q: 200 OK
    Note over Q,Web: Bila gagal diulang 3 kali (jeda 10 dan 60 detik),<br/>lalu dicatat di log tanpa mengganggu admin
```

Operasi yang tidak mengubah data (misalnya menandai ulang produk yang sudah terjual, atau mengubah status ke nilai yang sama) tidak mengantrekan pemberitahuan. Daftar tag dan kontrak lengkap ada di [Integrasi Frontend](./frontend-integration.md).

## 7. Alur Teknis: Unggah Foto Produk

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Next.js Admin
    participant API as Laravel API
    participant ST as Storage
    participant DB as MySQL
    participant Q as Queue

    Admin->>FE: Pilih satu atau beberapa foto
    FE->>FE: Kompres foto di sisi klien
    FE->>API: POST /api/v1/admin/products/{id}/images (multipart)
    API->>API: Validasi format, ukuran maksimal 2 MB, maksimal 8 file, dimensi
    API->>API: Cek total foto produk maksimal 10
    alt Valid
        API->>ST: Simpan versi penuh dan thumbnail (WebP)
        API->>DB: Transaksi: buat baris product_images
        API->>Q: Antrekan revalidasi cache
        API-->>FE: 201 Created + daftar foto (dengan id)
        FE-->>Admin: Foto muncul di galeri
    else Tidak valid atau melebihi batas
        API-->>FE: 422 pesan validasi
        FE-->>Admin: Tampilkan error
    end
```

Foto pertama yang diunggah untuk sebuah produk otomatis menjadi foto utama. Bila pemrosesan atau penyimpanan data gagal di tengah jalan, file yang sudah tersimpan dihapus kembali agar tidak ada file yatim di storage.

## 8. Alur Teknis: Mengelola Foto yang Sudah Ada

```mermaid
flowchart TD
    A(["Admin membuka galeri foto produk"]) --> B{"Pilih tindakan"}
    B -- "Jadikan foto utama" --> C["PATCH images/id dengan is_primary true"]
    C --> D["Foto lain kehilangan penanda utama, dalam satu transaksi"]
    B -- "Ubah urutan" --> E["PUT images/order dengan daftar ID lengkap"]
    E --> F{"Daftar memuat semua foto produk ini, masing-masing sekali?"}
    F -- "Tidak" --> G["Ditolak 422"]
    F -- "Ya" --> H["sort_order diperbarui sesuai posisi"]
    B -- "Hapus foto" --> I["DELETE images/id"]
    I --> J{"Foto utama?"}
    J -- "Ya" --> K["Foto berikutnya dipromosikan jadi utama"]
    J -- "Tidak" --> L["Baris dan kedua file dihapus"]
    K --> L
    D --> M["Antrekan revalidasi cache"]
    H --> M
    L --> M
```

Foto yang bukan milik produk di URL (misalnya `/products/1/images/99` padahal foto 99 milik produk lain) dibalas 404.

## 9. Alur Teknis: Membuka Katalog dan Detail Produk

```mermaid
sequenceDiagram
    actor Pembeli
    participant Web as Next.js Publik
    participant API as Laravel API
    participant DB as MySQL

    Pembeli->>Web: Buka katalog (dengan filter)
    Web->>API: GET /api/v1/products?category=...&size=...&page=1
    API->>API: Validasi parameter (per_page maksimal 50, sort, rentang harga)
    API->>DB: Query produk (tidak hidden), terjual di urutan bawah, eager load kategori dan foto utama
    DB-->>API: Hasil per halaman
    API-->>Web: 200 OK (JSON, dengan paginasi)
    Web-->>Pembeli: Tampilkan grid produk

    Pembeli->>Web: Buka detail produk
    Web->>API: GET /api/v1/products/{slug}
    alt Ditemukan dan tidak hidden
        API-->>Web: 200 OK detail produk
        Web-->>Pembeli: Tampilkan galeri, kondisi, ukuran
    else Tidak ada atau hidden
        API-->>Web: 404 Not Found (JSON)
        Web-->>Pembeli: Halaman tidak ditemukan
    end
```

Parameter yang tidak valid (misalnya `per_page=100` atau `sort=acak`) dibalas 422 dengan rincian per parameter, dan permintaan yang melebihi 60 kali per menit dibalas 429.