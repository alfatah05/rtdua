# Stage 9 — Backend fondasi: panduan deploy

## 1. Database

1. Buat database + user di cPanel.
2. Import `sql/001_skema_awal.sql` lewat phpMyAdmin (utf8mb4).
3. (Opsional) buat akun developer: lihat `sql/002_akun_developer.md`.

## 2. Backend di hosting

1. Upload isi `backend/` (setelah `composer install`) ke folder **di luar** Document Root, contoh: `rt-app/`.
2. Salin `.env.example` → `.env`, isi database + `app.baseURL`.
3. Pastikan `writable/` bisa ditulis (chmod 775).
4. Folder `api/` di masing-masing subdomain (warga-dev / pengurus-dev) berisi thin proxy `index.php` yang mengarah ke `rt-app/public/index.php`.

## 3. Setup awal (sekali)

```http
POST /api/setup
Content-Type: application/json

{
  "nama_rt": "Rukun Warga 002",
  "nama_perumahan": "Perum. Pesona Gading Cibitung 2",
  "username": "ketua",
  "password": "minimal8karakter",
  "nama_ketua": "Budi Santoso",
  "blok": ["AB1", "AB2", "AB11", "AB12"]
}
```

Setelah sukses, endpoint setup terkunci.

Cek: `GET /api/setup/status`

## 4. Auth

| Sisi | Login | Cookie sesi |
|------|--------|-------------|
| warga | username + PIN (6 digit) | `sesi_warga` (di level app session) |
| pengurus | username + password | `sesi_pengurus` |

**Username pengurus (Stage 9):** teks bebas, unik, case-insensitive (contoh: `ketua`, `budi`). Boleh diubah nanti.

- Login lintas sisi ditolak (role dicek).
- 5 gagal dalam 15 menit → terkunci sementara.
- PIN awal warga: `123456` (wajib ganti).

```http
POST /api/auth/login
{ "username": "ketua", "password": "..." }

GET /api/auth/me
POST /api/auth/logout
POST /api/auth/change-credential
{ "new_password": "..." }  // atau new_pin untuk warga
```

## 5. Health

`GET /api/health` → `{ ok, side, time, tz: Asia/Jakarta }`

## 6. Catatan composer

Di Actions / lokal:

```bash
cd backend && composer install --no-dev --optimize-autoloader
```

Framework CI4 mengisi `public/index.php` resmi.
