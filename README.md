# Patch: Pilih warga = pola halaman Warga (cepat)

## Perubahan
Halaman `/kelola-pengurus/angkat` sekarang:
1. **Load semua keluarga sekali** (`listKeluarga`) — sama seperti halaman Warga
2. **Search client-side** (instant, filter nama/alamat)
3. Pilih keluarga → tampil anggota aktif keluarga itu
4. Pilih anggota → **Jadikan pengurus** → form akun

Tidak lagi search API per ketikan (lambat / kosong).

## File
- `frontend/src/pengurus/views/AngkatPengurusView.vue` (utama)
- `AngkatPengurusFormView.vue` + `router/index.js` (ikut, bila belum ter-deploy)

## Setelah push
Jalankan **Deploy Dev**.
