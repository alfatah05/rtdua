# Panduan Stage 12 (ringkas)

1. Import SQL `sql/003_stage12_notifikasi_push.sql` di phpMyAdmin (DB dev).
2. Generate secrets: `php scripts/generate-secrets.php` → tempel ke `.env` hosting.
3. Deploy branch `dev` (sudah di-push).
4. Uji Postman: `GET /api/tugas/harian` + header `X-Cron-Token` (2x, tidak double).
5. Pasang 3 job di cron-job.org (lihat `docs/cron-job-org.md`).

Detail lengkap ada di zip `stage12-panduan-generate.zip` di project artifacts.
