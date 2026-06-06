# Dokumentasi API — AgriCloud v2

Dokumen ini adalah **kontrak REST API** AgriCloud v2 yang menjadi acuan bersama untuk web
dashboard dan aplikasi mobile.

> **Status sumber:** dokumen ini mencerminkan implementasi yang ada di kode (`routes/api.php`,
> `app/Http/Controllers/`, `app/Http/Resources/`). Sebagian endkoint sudah berfungsi dan tervalidasi
> oleh test (`tests/Feature/AuthApiTest.php`, 11 test lulus).

---

## Informasi Umum

| Item              | Nilai                                                       |
| ----------------- | ----------------------------------------------------------- |
| Base URL (resmi)  | `http://localhost:8000`                                     |
| Prefix API        | `/api`                                                      |
| Format pertukaran | JSON (`Content-Type: application/json`)                     |
| Autentikasi       | Laravel Sanctum — token bearer                              |
| Health check      | `GET /up` (disediakan framework, tanpa prefix `/api`)       |

### Catatan Lingkungan & Port

Port resmi backend pada `CLAUDE.md` adalah **8000**. Pada lingkungan pengembangan tim saat ini
disepakati: **backend 8005, web 8006, mobile 8007**. Konfigurasi **CORS** (`config/cors.php`)
mengizinkan origin web `http://localhost:8006` dan `http://172.24.170.241:8006`
(`supports_credentials: true`).

### Header Standar

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>   # untuk endpoint terproteksi
```

---

## Autentikasi

API memakai **Laravel Sanctum** dengan skema **token bearer**:

1. Klien melakukan `POST /api/auth/register` atau `POST /api/auth/login` untuk memperoleh token.
2. Token disertakan pada header `Authorization: Bearer <token>` di setiap permintaan ke endpoint
   terproteksi (middleware `auth:sanctum`).
3. `POST /api/auth/logout` mencabut token yang sedang dipakai.

---

## Konvensi Response

Mengikuti default Laravel API Resource.

- **Resource tunggal & koleksi** dibungkus dalam objek `data`.
- **Token** dikembalikan seragam sebagai `{ "data": { "access_token", "token_type": "Bearer" } }`
  (berlaku untuk register maupun login).
- **Error validasi** (HTTP 422) memakai bentuk standar Laravel:

```json
{
  "message": "Pesan ringkas",
  "errors": {
    "field": ["deskripsi kesalahan"]
  }
}
```

### Kode Status HTTP

| Kode | Makna                                                |
| ---- | ---------------------------------------------------- |
| 200  | OK — permintaan berhasil                             |
| 201  | Created — resource baru berhasil dibuat              |
| 401  | Unauthorized — token tidak ada / tidak valid         |
| 422  | Unprocessable Entity — validasi input gagal          |

---

## Daftar Endpoint

| Method | Path                   | Auth     | Deskripsi                                  |
| ------ | ---------------------- | -------- | ------------------------------------------ |
| POST   | `/api/auth/register`   | publik   | Daftar user baru, kembalikan token.        |
| POST   | `/api/auth/login`      | publik   | Login, kembalikan token.                   |
| GET    | `/api/auth/user`       | sanctum  | Profil user yang sedang login.             |
| POST   | `/api/auth/logout`     | sanctum  | Cabut token aktif.                         |
| GET    | `/api/myfields`        | sanctum  | Daftar lahan milik user login.             |
| POST   | `/api/myfields`        | sanctum  | Tambah lahan baru (multipart).             |
| GET    | `/api/crop-templates`  | publik   | Daftar template tanaman.                   |
| GET    | `/api/notifications`             | sanctum  | Daftar notifikasi user login (paginated).        |
| GET    | `/api/notifications/unread-count`| sanctum  | Jumlah notifikasi belum dibaca (badge).          |
| POST   | `/api/notifications/{id}/read`   | sanctum  | Tandai satu notifikasi sebagai dibaca.           |
| POST   | `/api/notifications/read-all`    | sanctum  | Tandai semua notifikasi sebagai dibaca.          |
| GET    | `/api/user`            | sanctum  | User login (endpoint bawaan, kompatibilitas).|

---

## Auth

### `POST /api/auth/register`

Membuat user baru lalu mengembalikan Bearer token. Field `role` dikirim sebagai **nama** role
(mis. `"farmer"`); server memetakannya ke `role_id`. Jika `role` tidak dikirim, default `farmer`;
jika nama role tidak ada di tabel `roles` → **422**.

**Body**

```json
{
  "name": "Budi Tani",
  "email": "budi@example.com",
  "phone_number": "081234567890",
  "password": "rahasia123",
  "role": "farmer"
}
```

Aturan validasi: `name` wajib; `email` wajib & unik; `phone_number` wajib & unik; `password`
wajib min. 8 karakter; `role` opsional.

**Response `201 Created`**

```json
{
  "data": {
    "access_token": "1|abcdef...",
    "token_type": "Bearer"
  }
}
```

### `POST /api/auth/login`

**Body**

```json
{
  "email": "budi@example.com",
  "password": "rahasia123"
}
```

**Response `200 OK`** — sama bentuknya dengan register:

```json
{
  "data": {
    "access_token": "2|ghijkl...",
    "token_type": "Bearer"
  }
}
```

**Response `422` — kredensial salah**

```json
{
  "message": "Email atau password salah.",
  "errors": {
    "email": ["Email atau password salah."]
  }
}
```

### `GET /api/auth/user`

- **Auth:** wajib (`auth:sanctum`)

Mengembalikan profil user login. Kolom DB dipetakan ke kontrak FE: `role_id` → `role` (nama),
`profil_url` → `profile_photo` & `profile_photo_url`.

**Response `200 OK`**

```json
{
  "data": {
    "id": 1,
    "name": "Budi Tani",
    "email": "budi@example.com",
    "email_verified_at": null,
    "phone_number": "081234567890",
    "role": "farmer",
    "profile_photo": null,
    "created_at": "2026-06-06T08:00:00.000000Z",
    "updated_at": "2026-06-06T08:00:00.000000Z",
    "profile_photo_url": null
  }
}
```

### `POST /api/auth/logout`

- **Auth:** wajib (`auth:sanctum`)

Mencabut token yang sedang dipakai. **Response `200 OK`**: `{ "message": "Logged out." }`

---

## MyFields (Lahan)

### `GET /api/myfields`

- **Auth:** wajib (`auth:sanctum`)

Mengembalikan daftar lahan milik user login (terbaru lebih dulu).

**Response `200 OK`**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Sawah Utara",
      "description": "Lahan padi blok A",
      "thumbnail": "http://localhost:8000/storage/lands/abc.jpg",
      "location": { "latitude": "-7.7956000", "longitude": "110.3695000" },
      "area": "1200.50",
      "owner": { "id": 1, "name": "Budi Tani" },
      "created_at": "2026-06-06T08:00:00.000000Z",
      "updated_at": "2026-06-06T08:00:00.000000Z"
    }
  ]
}
```

### `POST /api/myfields`

- **Auth:** wajib (`auth:sanctum`)
- **Content-Type:** `multipart/form-data`

**Field**

| Field         | Tipe          | Wajib | Keterangan                       |
| ------------- | ------------- | ----- | -------------------------------- |
| `name`        | string        | ya    | Nama lahan.                      |
| `description` | string        | tidak | —                                |
| `area`        | numeric       | tidak | Luas lahan.                      |
| `latitude`    | numeric       | tidak | Koordinat lintang.               |
| `longitude`   | numeric       | tidak | Koordinat bujur.                 |
| `thumbnail`   | file (image)  | tidak | Maks 5 MB. Disimpan ke `storage`.|

**Response `201 Created`** — satu objek `LandResource` (bentuk sama seperti item pada `GET /myfields`).
Thumbnail disimpan ke disk `public` (`storage/app/public/lands`) dan dikembalikan sebagai URL absolut.
Membutuhkan `php artisan storage:link`.

---

## Crop Templates

### `GET /api/crop-templates`

- **Auth:** publik (tanpa token)

**Response `200 OK`**

```json
{
  "data": [
    { "id": 1, "name": "Padi", "description": "Tanaman padi sawah" },
    { "id": 2, "name": "Jagung", "description": null }
  ]
}
```

---

## Notifikasi (In-App)

Notifikasi in-app per user, disimpan di tabel `notifications` (channel `database` bawaan Laravel).
Semua endpoint **wajib token** (`auth:sanctum`) dan hanya mengakses notifikasi **milik user login**.
Tidak ada push (FCM) maupun real-time (WebSocket) di tier ini — FE melakukan _polling_
(mis. panggil `unread-count` berkala untuk badge).

### Bentuk objek notifikasi (`NotificationResource`)

Ini **kontrak tetap** untuk satu notifikasi. `id` berupa **UUID string** (bukan integer).

```jsonc
{
  "id": "58dc1db7-d33c-4f4f-8094-80cdc897a04f", // UUID string
  "type": "low_stock",                          // jenis notifikasi (lihat tabel di bawah)
  "title": "Stok menipis",                      // judul siap-tampil
  "body": "Pupuk Urea tersisa 8 kg",            // isi siap-tampil
  "data": {                                      // konteks spesifik per type (lihat tabel)
    "item_id": 51,
    "warehouse_id": 11,
    "stock": 8,
    "unit": "kg",
    "threshold": 10
  },
  "is_read": false,                              // boolean; true bila sudah dibaca
  "created_at": "2026-06-06T05:20:20+00:00"      // ISO 8601
}
```

> **Penting untuk FE:** ada **dua** `data` yang berbeda — `data` terluar adalah _wrapper_ Laravel
> (berisi resource), dan `data` di dalam objek notifikasi adalah **payload konteks**. Untuk badge &
> daftar, pakai field siap-tampil `title`/`body`; pakai `data` (konteks) hanya bila ingin
> deep-link/navigasi (mis. buka item `data.item_id`).

#### Jenis (`type`) & isi `data` konteks

| `type`              | Kapan muncul                                 | Isi `data` (konteks)                                                        | Status pemicu       |
| ------------------- | -------------------------------------------- | --------------------------------------------------------------------------- | ------------------- |
| `low_stock`         | Stok item < ambang batas global              | `item_id, warehouse_id, stock, unit, threshold`                             | **Aktif**           |
| `phase_schedule`    | Perubahan/peringatan jadwal fase             | `phase_id, cycle_id, stage_id, started_at, ended_at`                        | Disiapkan (nonaktif)|
| `movement_status`   | Status pergerakan stok berubah               | `movement_id, warehouse_id, item_id, status_id, status, quantity`          | Disiapkan (nonaktif)|
| `needs_unfulfilled` | Kebutuhan input fase belum terpenuhi         | `need_id, phase_id, item_id, quantity_needed`                              | Disiapkan (nonaktif)|

> Saat ini hanya `low_stock` yang benar-benar terkirim otomatis (oleh `ItemsObserver` saat stok item
> turun di bawah `config('agricloud.low_stock_threshold')`, default **10**). Tiga jenis lain sudah
> punya class & bentuk payload final, tetapi pemicunya menyusul di scope berikutnya. **FE sebaiknya
> menangani `type` secara generik** (fallback ke `title`/`body`) agar tidak perlu rilis ulang saat
> jenis baru dinyalakan.

### `GET /api/notifications`

- **Auth:** wajib. **Query:** `?page=N` (paginasi Laravel standar, 15 per halaman, terbaru dulu).

**Response `200 OK`** — koleksi terbungkus `data` + metadata paginasi (`links`, `meta`):

```jsonc
{
  "data": [
    {
      "id": "58dc1db7-d33c-4f4f-8094-80cdc897a04f",
      "type": "low_stock",
      "title": "Stok menipis",
      "body": "Pupuk Urea tersisa 8 kg",
      "data": { "item_id": 51, "warehouse_id": 11, "stock": 8, "unit": "kg", "threshold": 10 },
      "is_read": false,
      "created_at": "2026-06-06T05:20:20+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1 }
}
```

Bila kosong: `data` = `[]`, `meta.total` = `0`.

### `GET /api/notifications/unread-count`

- **Auth:** wajib. Untuk angka **badge**. Ringan, aman dipanggil berkala.

**Response `200 OK`**

```json
{ "data": { "unread_count": 3 } }
```

### `POST /api/notifications/{id}/read`

- **Auth:** wajib. `{id}` = **UUID** notifikasi. Tanpa body.
- Menandai **satu** notifikasi milik user sebagai dibaca (idempotent — aman dipanggil ulang).

**Response `200 OK`** — objek notifikasi terbaru (terbungkus `data`), `is_read` jadi `true`:

```jsonc
{
  "data": {
    "id": "58dc1db7-d33c-4f4f-8094-80cdc897a04f",
    "type": "low_stock",
    "title": "Stok menipis",
    "body": "Pupuk Urea tersisa 8 kg",
    "data": { "item_id": 51, "warehouse_id": 11, "stock": 8, "unit": "kg", "threshold": 10 },
    "is_read": true,
    "created_at": "2026-06-06T05:20:20+00:00"
  }
}
```

**Response `404 Not Found`** — bila `{id}` bukan milik user login atau tidak ada.

### `POST /api/notifications/read-all`

- **Auth:** wajib. Tanpa body. Menandai **semua** notifikasi belum dibaca milik user jadi dibaca.

**Response `200 OK`**

```json
{ "data": { "unread_count": 0 } }
```

### Error umum

- **401** — tanpa token / token invalid. _Selalu kirim_ `Accept: application/json` agar dapat 401
  JSON (tanpa header itu server mencoba redirect ke route login dan bisa balas 500).

### Contoh alur FE (badge + daftar)

```http
# 1) Badge — panggil saat load & berkala
GET /api/notifications/unread-count
Authorization: Bearer <token>
Accept: application/json

# 2) Buka panel notifikasi
GET /api/notifications?page=1
Authorization: Bearer <token>
Accept: application/json

# 3) User klik satu notifikasi
POST /api/notifications/58dc1db7-d33c-4f4f-8094-80cdc897a04f/read
Authorization: Bearer <token>
Accept: application/json

# 4) Tombol "Tandai semua dibaca"
POST /api/notifications/read-all
Authorization: Bearer <token>
Accept: application/json
```

---

## Endpoint Direncanakan (belum ada)

Entitas berikut **belum punya endpoint** dan mengikuti pola RESTful saat nanti dibuat:
`cycles`, `phases`, `stages`, `warehouses`, `items`, `categories`, `movements`, `move-types`,
`needs`, serta manajemen `users` (admin — `Admin/UserController` masih kosong). Detail kolom tiap
entitas mengacu pada [`docs/DATABASE.md`](DATABASE.md).

---

## Referensi

- Definisi route: `routes/api.php`
- Controller: `app/Http/Controllers/`
- Bentuk response (mapping kolom): `app/Http/Resources/`
- Skema database: [`docs/DATABASE.md`](DATABASE.md)
