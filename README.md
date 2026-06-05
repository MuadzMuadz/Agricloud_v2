# AgriCloud v2 — Backend

Backend **AgriCloud v2** dibangun dengan **Laravel 12** sebagai sumber data utama (REST API)
untuk seluruh ekosistem AgriCloud: web dashboard dan aplikasi mobile. API ini mengelola data
pengguna, lahan, siklus tanam, fase pertumbuhan, gudang, stok barang, dan logistik pergerakan
barang. Dirancang modular, aman (Laravel Sanctum), dan siap diintegrasikan dengan sistem IoT
serta dashboard analitik.

> **Bagian dari proyek AgriCloud** — terdiri dari tiga repo: backend (`Agricloud_v2`, repo ini),
> web (`agricloud_fe`), dan mobile (`Frontend_AgriCloudMobile`).

---

## Daftar Isi

- [Stack Teknologi](#stack-teknologi)
- [Status Pengembangan](#status-pengembangan)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi](#instalasi)
- [Menjalankan Server](#menjalankan-server)
- [Pengujian](#pengujian)
- [Struktur Proyek](#struktur-proyek)
- [Dokumentasi Lanjutan](#dokumentasi-lanjutan)

---

## Stack Teknologi

| Komponen    | Teknologi                       |
| ----------- | ------------------------------- |
| Framework   | Laravel 12                      |
| Bahasa      | PHP 8.2                         |
| Database    | PostgreSQL (`agricloud_v2`)     |
| Autentikasi | Laravel Sanctum (token bearer)  |
| Formatter   | Laravel Pint                    |
| Pengujian   | PHPUnit                         |
| Port resmi  | **8000** (`php artisan serve`)  |

> **Catatan database:** lingkungan pengembangan saat ini memakai **PostgreSQL** yang berjalan di
> container Docker pada port **5434** (bukan MySQL). Sesuaikan kredensial pada berkas `.env`.

---

## Status Pengembangan

| Area                       | Status          | Keterangan                                                     |
| -------------------------- | --------------- | -------------------------------------------------------------- |
| Skema database & migration | ✅ Selesai      | 14 tabel domain, lihat [`docs/DATABASE.md`](docs/DATABASE.md). |
| Model Eloquent & relasi    | ✅ Selesai      | 14 model dengan relasi lengkap di `app/Models/`.               |
| Seeder & factory           | ✅ Selesai      | Data uji untuk seluruh entitas.                                |
| Controller & route API     | 🟡 Skeleton     | Baru tersedia `GET /api/user`. Controller lain masih kosong.   |
| Endpoint Auth, Lahan, dll. | 🔴 Direncanakan | Lihat [`docs/API.md`](docs/API.md) bagian *Direncanakan*.      |

---

## Kebutuhan Sistem

- PHP **8.2** atau lebih baru
- [Composer](https://getcomposer.org/)
- PostgreSQL (disarankan via Docker, port 5434)
- Node.js + npm (untuk aset frontend Laravel, opsional)

---

## Instalasi

```bash
# 1. Pasang dependency PHP
composer install

# 2. Siapkan berkas environment
cp .env.example .env
php artisan key:generate

# 3. Sesuaikan koneksi database di .env
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5434
#    DB_DATABASE=agricloud_v2
#    DB_USERNAME=postgres
#    DB_PASSWORD=postgres

# 4. Jalankan migration + seeder
php artisan migrate --seed
```

---

## Menjalankan Server

Gunakan **port resmi 8000**. Cek dulu apakah server sudah berjalan sebelum menjalankan ulang.

```bash
php artisan serve
# API tersedia di http://localhost:8000
```

---

## Pengujian

Pengujian memakai database PostgreSQL terpisah (`agricloud_v2_test`, lihat `phpunit.xml`).

```bash
php artisan test
```

Sebelum melapor selesai, format kode dengan Laravel Pint:

```bash
./vendor/bin/pint
```

---

## Struktur Proyek

```
app/
├── Http/Controllers/        # Controller (sebagian besar masih skeleton)
│   ├── AuthController.php
│   └── Admin/UserController.php
└── Models/                  # 14 model Eloquent + relasi

database/
├── migrations/              # Skema 14 tabel domain
├── factories/               # Factory data uji
└── seeders/                 # Seeder data uji

routes/
├── api.php                  # Route API (prefix /api)
└── web.php                  # Route web

docs/
├── API.md                   # Dokumentasi REST API
└── DATABASE.md              # Skema database & ERD
```

---

## Dokumentasi Lanjutan

- **[Dokumentasi API](docs/API.md)** — daftar endpoint, autentikasi, format response, dan kontrak API.
- **[Dokumentasi Database](docs/DATABASE.md)** — skema tabel, kolom, relasi, dan diagram ERD.
- **`CLAUDE.md`** — aturan kerja dan konvensi teknis untuk kontributor (dan asisten AI).
