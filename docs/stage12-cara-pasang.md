# Stage 12 — cara pasang

## 1. SQL dulu
Import `sql/003_stage12_notifikasi_push.sql` di phpMyAdmin (DB dev).
Abaikan error Duplicate column jika sudah pernah dijalankan.

## 2. .env hosting (File Manager, di luar repo)
Tambah:
```
CRON_TOKEN=string_acak_panjang_rahasia
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:emailkamu@contoh.com
```
VAPID bisa digenerate setelah deploy (composer install di Actions membawa minishlink/web-push).

## 3. Kode
Ekstrak zip ini di root repo, commit, push `dev`.

## 4. Uji API
- GET /api/notifikasi (cookie login)
- GET /api/notifikasi/badge
- GET /api/tugas/harian + header X-Cron-Token (dua kali, tidak boleh double tagihan)

## 5. cron-job.org
Ikuti `docs/cron-job-org.md`.

## Catatan default Stage 12
- Denda ronda + kunci absensi: tanggal 1 setelah 12:00 WIB (implementasi penuh Stage 13).
- Cadangan pemicu tagihan saat akses: tetap (TagihanService saat buka app / pastikanTagihanKas).
- Pembuatan malam ronda & pengingat sore: stub sampai Stage 13.
