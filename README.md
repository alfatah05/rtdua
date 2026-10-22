# Patch: fix search angkat pengurus (hasil kosong)

## Perubahan
1. **KeluargaService::cariAnggotaAktif** — query raw SQL (lebih stabil), LEFT JOIN blok, fallback Query Builder jika SQL gagal.
2. **AngkatPengurusView** — jika endpoint anggota kosong, fallback lewat `listKeluarga` + `getKeluarga` (endpoint yang sudah terbukti jalan).
3. **warga.real.js** — normalisasi `res.data` selalu array.

## File yang diganti
- `backend/app/Services/KeluargaService.php`
- `frontend/src/pengurus/views/AngkatPengurusView.vue`
- `frontend/src/shared/services/warga.real.js`

## Setelah push
Jalankan **Deploy Dev** (workflow_dispatch) agar frontend ikut rebuild.
