# rtdua — Aplikasi Manajemen Warga RT

Aplikasi PWA manajemen warga untuk satu RT.

- **Aplikasi warga** + **Aplikasi pengurus** (dua PWA, satu kode, satu database, satu backend)
- Frontend: Vue 3 + Vite + Tailwind CSS (JavaScript biasa)
- Backend: CodeIgniter 4 (API JSON)
- Hosting: shared hosting cPanel (tanpa SSH)
- Deploy: GitHub Actions → FTP

## Struktur repo

```
backend/          CodeIgniter 4 (API)
frontend/         Vue 3 (shared + warga + pengurus)
sql/              Migrasi SQL bernomor (001_....sql, ...)
deploy/           File tipis subdomain (api/, .htaccess)
docs/             Spesifikasi (01–04)
.github/workflows Deploy & bootstrap
```

## Cara kerja patch (penting)

1. AI mengirim **zip patch**
2. Ekstrak dan **timpa** ke folder repo di laptop (hanya butuh Git)
3. Commit + push ke GitHub
4. GitHub Actions menjalankan `composer install`, build frontend, lalu upload ke hosting via FTP
5. Bila ada SQL: **import SQL dulu lewat phpMyAdmin**, baru push kode

## Stage saat ini

**Stage 0** — Setup kerangka (belum ada fitur aplikasi)

Lihat `docs/04-prompt-build-bertahap.md` untuk daftar Stage lengkap.
EOF