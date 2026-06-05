# CLAUDE.md — Backend (`Agricloud_v2`)

Patuhi dulu aturan global di `../CLAUDE.md`. File ini menambahkan aturan teknis backend.

## Stack

- Laravel 12, PHP 8.2, PostgreSQL (`agricloud_v2`, Docker port 5434)
- Auth: **Laravel Sanctum** (token bearer)
- Format: **Laravel Pint** — jalankan sebelum lapor selesai.
- Test: PHPUnit (`php artisan test`)

## Port Resmi

- **8000** via `php artisan serve`.
- Cek dulu apakah sudah jalan sebelum start. **Jangan** start di port lain.

## Status Saat Ini

- ✅ 14 model + migration lengkap (`app/Models/`, `database/migrations/`).
- 🟡 Controller & route masih skeleton — baru `GET /user`.
- Endpoint yang sudah dipanggil web & perlu diimplementasikan: `POST /auth/login`, `GET /auth/user`, `GET /myfields`, `POST /myfields`, `GET /crop-templates`.

## Konvensi

- Endpoint API baru → `routes/api.php`, lindungi dengan `auth:sanctum` bila perlu autentikasi.
- Response API konsisten (struktur JSON yang sama) — ini jadi kontrak untuk web & mobile.
- 🚫 Jangan ubah migration/model yang sudah ada tanpa menyebutkannya di plan & di-approve.
- Validasi input pakai Form Request / `$request->validate()`.
- Seeder & factory dipakai untuk data uji, bukan data hardcoded di controller.

## Verifikasi

- Jalankan `php artisan test` untuk perubahan logic.
- Tes endpoint nyata (curl/Postman) terhadap `localhost:8000` dan tunjukkan response.
