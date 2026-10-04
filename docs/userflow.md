# PakeLagi API — User Flow

| | |
|---|---|
| **Versi dokumen** | 0.1 (Draft) |
| **Tanggal** | 4 Oktober 2026 |
| **Dokumen terkait** | [Requirements](./requirements.md) · [ERD](./erd.md) |

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
- Wishlist dan keranjang disimpan di browser (localStorage). Saat membuka wishlist, frontend memanggil `GET /products?slugs=a,b,c`; produk yang sudah terjual tetap tampil dengan label, produk yang sudah tidak ada diabaikan.
- Pembeli tidak membuat akun dan tidak membayar lewat situs pada versi 1.

## 2. Alur Admin

Admin masuk, mengelola produk, dan menandai barang terjual setelah transaksi selesai di WhatsApp.

```mermaid
flowchart TD
    A(["Admin membuka halaman admin"]) --> B{"Sudah login?"}
    B -- "Belum" --> C["Halaman login: email dan kata sandi"]
    C --> D{"Kredensial benar?"}
    D -- "Tidak" --> E["Tampilkan pesan error, batasi percobaan"]
    E --> C
    D -- "Ya" --> F["Daftar produk admin"]
    B -- "Sudah" --> F
    F --> G{"Pilih tindakan"}

    G -- "Tambah produk" --> H["Isi form: nama, kategori, harga, ukuran, kondisi"]
    H --> I["Isi ukuran dalam cm sesuai kategori"]
    I --> J["Unggah foto dan tentukan foto utama"]
    J --> K["Simpan produk"]
    K --> F

    G -- "Ubah produk" --> L["Edit data atau foto"]
    L --> F

    G -- "Tandai terjual" --> M["Konfirmasi tandai terjual"]
    M --> N["Status menjadi sold, sold_at terisi"]
    N --> F

    G -- "Sembunyikan atau tayangkan" --> O["Ubah status hidden atau available"]
    O --> F

    G -- "Hapus produk" --> P["Konfirmasi hapus"]
    P --> Q["Data produk dan file foto dihapus"]
    Q --> F

    G -- "Kelola kategori" --> R["Tambah, ubah, atau hapus kategori"]
    R --> F

    G -- "Logout" --> S(["Kembali ke halaman login"])
```

**Catatan:**
- Penghapusan kategori ditolak bila kategori masih punya produk.
- Setiap perubahan produk memicu revalidasi cache di frontend (lihat bagian 5).

## 3. Siklus Hidup Produk

```mermaid
stateDiagram-v2
    [*] --> hidden: dibuat sebagai draf
    [*] --> available: dibuat dan langsung tayang
    hidden --> available: ditayangkan
    available --> hidden: ditarik sementara
    available --> sold: ditandai terjual
    sold --> available: pembelian batal
    sold --> hidden: disembunyikan
```

Perilaku di API publik: `available` tampil normal, `sold` tetap bisa dibuka lewat slug dengan label terjual, `hidden` tidak pernah tampil.

## 4. Alur Teknis: Login Admin

Direncanakan memakai Sanctum berbasis cookie (SPA), sehingga frontend dan API perlu berada di domain induk yang sama (`pakelagi.works` dan `api.pakelagi.works`).

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
        API-->>FE: 200 OK + cookie sesi
        FE->>API: GET /api/v1/auth/me
        API-->>FE: Data admin
        FE-->>Admin: Masuk ke daftar produk
    else Kredensial salah
        API-->>FE: 422 pesan validasi
        FE-->>Admin: Tampilkan error
    else Terlalu banyak percobaan
        API-->>FE: 429 Too Many Requests
        FE-->>Admin: Minta menunggu
    end
```

## 5. Alur Teknis: Tandai Terjual dan Revalidasi Cache

Next.js menyimpan cache halaman produk. Tanpa revalidasi, halaman publik bisa masih menampilkan "tersedia" setelah barang terjual.

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Next.js Admin
    participant API as Laravel API
    participant DB as MySQL
    participant Web as Next.js Publik

    Admin->>FE: Klik Tandai terjual
    FE->>API: POST /api/v1/admin/products/{id}/sold
    API->>API: Cek autentikasi dan aturan bisnis
    API->>DB: UPDATE status = sold, sold_at = sekarang
    DB-->>API: Berhasil
    API->>Web: POST endpoint revalidasi (slug produk)
    Note over API,Web: Bila gagal, dicatat di log dan<br/>tidak menggagalkan permintaan admin
    API-->>FE: 200 OK + data produk terbaru
    FE-->>Admin: Tampilkan status Terjual
```

## 6. Alur Teknis: Unggah Foto Produk

```mermaid
sequenceDiagram
    actor Admin
    participant FE as Next.js Admin
    participant API as Laravel API
    participant ST as Storage
    participant DB as MySQL

    Admin->>FE: Pilih satu atau beberapa foto
    FE->>FE: Kompres foto di sisi klien
    FE->>API: POST /api/v1/admin/products/{id}/images (multipart)
    API->>API: Validasi tipe file dan ukuran
    alt Valid
        API->>ST: Simpan file dengan nama baru dan buat ukuran thumbnail
        API->>DB: INSERT product_images (path, sort_order, is_primary)
        API-->>FE: 201 Created + daftar foto
        FE-->>Admin: Foto muncul di galeri produk
    else Tidak valid
        API-->>FE: 422 pesan validasi
        FE-->>Admin: Tampilkan error
    end
```

## 7. Alur Teknis: Membuka Katalog dan Detail Produk

```mermaid
sequenceDiagram
    actor Pembeli
    participant Web as Next.js Publik
    participant API as Laravel API
    participant DB as MySQL

    Pembeli->>Web: Buka katalog (dengan filter)
    Web->>API: GET /api/v1/products?category=...&size=...&page=1
    API->>DB: Query produk (tidak hidden) + eager load foto
    DB-->>API: Hasil per halaman
    API-->>Web: 200 OK (JSON, dengan paginasi)
    Web-->>Pembeli: Tampilkan grid produk

    Pembeli->>Web: Buka detail produk
    Web->>API: GET /api/v1/products/{slug}
    alt Ditemukan dan tidak hidden
        API-->>Web: 200 OK detail produk
        Web-->>Pembeli: Tampilkan galeri, kondisi, ukuran
    else Tidak ada atau hidden
        API-->>Web: 404 Not Found
        Web-->>Pembeli: Halaman tidak ditemukan
    end
```