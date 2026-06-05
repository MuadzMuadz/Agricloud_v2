# Dokumentasi API — AgriCloud v2

Dokumen ini adalah **kontrak REST API** AgriCloud v2 yang menjadi acuan bersama untuk web
dashboard dan aplikasi mobile. Dokumen dibagi menjadi dua bagian:

- **[Tersedia](#endpoint-tersedia)** — endpoint yang sudah diimplementasikan dan dapat dipanggil.
- **[Direncanakan](#endpoint-direncanakan)** — endpoint yang sudah disepakati kontraknya tetapi
  **belum diimplementasikan** (controller masih skeleton).

> ⚠️ **Status implementasi:** saat ini controller (`AuthController`, `Admin/UserController`) masih
> kosong. Satu-satunya route aktif adalah `GET /api/user`. Bagian *Direncanakan* berisi rancangan
> kontrak agar tim web & mobile dapat menyiapkan integrasi lebih awal — jangan diasumsikan sudah
> berjalan sampai statusnya diperbarui di sini.

---

## Informasi Umum

| Item                | Nilai                                                        |
| ------------------- | ------------------------------------------------------------ |
| Base URL (lokal)    | `http://localhost:8000`                                      |
| Prefix API          | `/api`                                                       |
| Base URL mobile     | `http://10.0.2.2:8000` (emulator Android → host)             |
| Format pertukaran   | JSON (`Content-Type: application/json`)                      |
| Autentikasi         | Laravel Sanctum — token bearer                               |
| Health check        | `GET /up` (disediakan framework, tanpa prefix `/api`)        |

### Header Standar

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>   # untuk endpoint terproteksi
```

---

## Autentikasi

API memakai **Laravel Sanctum** dengan skema **token bearer**. Alur yang direncanakan:

1. Klien mengirim kredensial ke `POST /api/auth/login`.
2. Server membalas dengan token akses.
3. Klien menyertakan token pada header `Authorization: Bearer <token>` di setiap permintaan ke
   endpoint terproteksi (middleware `auth:sanctum`).

---

## Konvensi Response

Seluruh response API diharapkan konsisten mengikuti struktur berikut (kontrak untuk web & mobile).

### Sukses

```json
{
  "success": true,
  "message": "Deskripsi singkat hasil",
  "data": { }
}
```

### Gagal / Error

```json
{
  "success": false,
  "message": "Deskripsi kesalahan",
  "errors": { }
}
```

### Kode Status HTTP

| Kode | Makna                                                |
| ---- | ---------------------------------------------------- |
| 200  | OK — permintaan berhasil                             |
| 201  | Created — resource baru berhasil dibuat              |
| 401  | Unauthorized — token tidak ada / tidak valid         |
| 403  | Forbidden — token valid tapi tanpa hak akses         |
| 404  | Not Found — resource tidak ditemukan                 |
| 422  | Unprocessable Entity — validasi input gagal          |
| 500  | Internal Server Error — kesalahan pada server        |

---

## Endpoint Tersedia

### `GET /api/user`

Mengembalikan data pengguna yang sedang terautentikasi.

- **Autentikasi:** wajib (`auth:sanctum`)
- **Parameter:** —

**Contoh permintaan**

```bash
curl -X GET http://localhost:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

**Contoh response `200 OK`**

```json
{
  "id": 1,
  "name": "Budi Tani",
  "role_id": 2,
  "profil_url": null,
  "phone_number": "081234567890",
  "email": "budi@example.com",
  "email_verified_at": null,
  "created_at": "2025-10-23T02:13:44.000000Z",
  "updated_at": "2025-10-23T02:13:44.000000Z"
}
```

> Catatan: kolom `password` dan `remember_token` disembunyikan dari serialisasi.

**Response `401 Unauthorized`** — token tidak disertakan atau tidak valid.

---

## Endpoint Direncanakan

Daftar berikut adalah rancangan kontrak. **Belum aktif** sampai controller & route diimplementasikan.

### Autentikasi

| Method | Path                | Auth      | Deskripsi                                   |
| ------ | ------------------- | --------- | ------------------------------------------- |
| POST   | `/api/auth/login`   | —         | Login, mengembalikan token bearer.          |
| GET    | `/api/auth/user`    | sanctum   | Data pengguna terautentikasi.               |
| POST   | `/api/auth/logout`  | sanctum   | Mencabut token aktif.                       |

**`POST /api/auth/login` — body**

```json
{
  "email": "budi@example.com",
  "password": "rahasia"
}
```

**Response `200 OK` (rancangan)**

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|abcdef...",
    "user": {
      "id": 1,
      "name": "Budi Tani",
      "email": "budi@example.com",
      "role_id": 2
    }
  }
}
```

### Lahan (Lands)

| Method | Path               | Auth    | Deskripsi                                  |
| ------ | ------------------ | ------- | ------------------------------------------ |
| GET    | `/api/myfields`    | sanctum | Daftar lahan milik pengguna (farmer).      |
| POST   | `/api/myfields`    | sanctum | Menambah lahan baru.                       |
| GET    | `/api/myfields/{id}` | sanctum | Detail satu lahan.                       |
| PUT    | `/api/myfields/{id}` | sanctum | Memperbarui data lahan.                  |
| DELETE | `/api/myfields/{id}` | sanctum | Menghapus lahan.                         |

**`POST /api/myfields` — body (rancangan)**

```json
{
  "name": "Sawah Utara",
  "description": "Lahan padi blok A",
  "latitude": -7.7956,
  "longitude": 110.3695,
  "area": 1200.50,
  "image_url": null
}
```

### Template Tanaman (Crops)

| Method | Path                  | Auth    | Deskripsi                                       |
| ------ | --------------------- | ------- | ----------------------------------------------- |
| GET    | `/api/crop-templates` | sanctum | Daftar template tanaman beserta tahapan (stage).|

### Entitas Lain (Direncanakan)

Mengikuti pola RESTful (`index`, `store`, `show`, `update`, `destroy`) untuk:
`cycles`, `phases`, `stages`, `warehouses`, `items`, `categories`, `movements`, `move-types`,
`needs`, dan manajemen `users` (admin). Detail kolom tiap entitas mengacu pada
[`docs/DATABASE.md`](DATABASE.md).

---

## Referensi

- Skema database lengkap: [`docs/DATABASE.md`](DATABASE.md)
- Definisi route aktif: `routes/api.php`
- Model & relasi: `app/Models/`
