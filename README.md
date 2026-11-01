# Patch: Detail kas + Batalkan transaksi

## Fitur
- Halaman `/keuangan/kas/:id` memuat data nyata (bukan placeholder)
- Field: keterangan, nominal, jenis, kategori, pos kas, metode, tanggal, dicatat oleh, waktu
- **Batalkan transaksi** memanggil `POST /keuangan/kas/{id}/batal`
- Kas otomatis dari iuran: tombol batal disembunyikan (batal lewat pembatalan pembayaran)
- Alokasi antar pos: konfirmasi, kedua sisi dibatalkan (logika backend yang sudah ada)

## Backend
- `KasService::detail($id)`
- `GET /keuangan/kas/(:num)` → `detailKas`
- `batalkanKas` tetap

## Setelah push
Deploy Dev (frontend + backend).
