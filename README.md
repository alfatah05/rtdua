# Fitur Catatan Pengurus

## Database — otomatis
Tabel `catatan_pengurus` **dibuat otomatis** saat API catatan pertama kali dipanggil.
Tidak wajib import SQL. Opsional: `backend/sql/catatan_pengurus.optional.sql`.

## Akses
Menu → **Catatan** (`/catatan`)

## Deploy
```bash
unzip -o catatan-pengurus.zip
# deploy backend + rebuild frontend pengurus
```
