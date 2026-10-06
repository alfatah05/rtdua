# rtdua — Aplikasi Manajemen Warga RT

Aplikasi PWA manajemen warga untuk satu RT.

- **Aplikasi warga** + **Aplikasi pengurus** (dua PWA, satu kode, satu database, satu backend)
- Frontend: Vue 3 + Vite + Tailwind CSS (JavaScript biasa)
- Backend: CodeIgniter 4 (API JSON)
- Hosting: shared hosting cPanel (tanpa SSH)
- Deploy: GitHub Actions → FTP

## Stage saat ini

**Stage 9 selesai (fondasi backend).** Siap menuju **Stage 10** (data warga, blok, pengaturan + hubungkan UI).

| Area | Status |
|------|--------|
| UI warga & pengurus (Stage 1–8) | Ada, data masih mock |
| Backend auth, setup, audit, skema SQL | Ada |
| Lapisan service mock/real di frontend | Ada (`shared/services`, `shared/mock`) |
| Hubungkan UI ke API | Belum (Stage 10) |

## Struktur repo

```
backend/          CodeIgniter 4 (API)
frontend/         Vue 3 (shared + warga + pengurus)
  src/shared/
    mock/         Data dummy terpusat
    services/     Facade mock | real per modul
    config/       Saklar USE_MOCK per modul
    utils/        Fungsi murni (alokasi pembayaran, format)
sql/              Migrasi SQL bernomor (001_....sql, ...)
deploy/           File tipis subdomain (api/, .htaccess)
docs/             Spesifikasi (01–04)
.github/workflows Deploy
```

## Cara kerja patch

1. AI mengirim **zip patch** (atau commit langsung ke branch `dev`)
2. Commit + push ke GitHub
3. GitHub Actions: `composer install`, build frontend, upload FTP
4. Bila ada SQL: **import SQL dulu lewat phpMyAdmin**, baru push kode

Lihat `docs/04-prompt-build-bertahap.md` untuk daftar Stage lengkap.
