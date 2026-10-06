# SQL migrations

File bernomor: `001_nama.sql`, `002_nama.sql`, ...

- File lama **tidak diubah**, hanya ditambah.
- Import lewat phpMyAdmin **sebelum** push kode yang bergantung pada skema baru.
- Encoding: utf8mb4.

## Stage 9

1. `001_skema_awal.sql` — seluruh tabel
2. `002_akun_developer.md` — cara buat akun developer (bukan SQL otomatis)
