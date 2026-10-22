# Patch: Alur angkat pengurus 2 langkah

## Alur baru
1. Kelola pengurus → **+ Angkat pengurus**
2. Halaman **Pilih warga** (search + list, pilih satu)
3. Tombol **Jadikan pengurus** → halaman **Buat akun pengurus** (username, password, jabatan, HP)
4. Simpan → kembali ke daftar pengurus

## File
- `frontend/src/pengurus/views/AngkatPengurusView.vue` — list pilih warga
- `frontend/src/pengurus/views/AngkatPengurusFormView.vue` — **baru** form akun
- `frontend/src/pengurus/router/index.js` — route `/kelola-pengurus/angkat/form`
- `frontend/src/shared/services/warga.real.js` / `warga.js` — cariAnggota

## Setelah push
Jalankan **Deploy Dev** agar frontend rebuild.
