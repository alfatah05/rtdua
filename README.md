# Patch: Angkat Pengurus — search floating + restore KeluargaService

Branch: `redesain-ui-warga`

## File yang WAJIB diganti (rusak di repo)

1. `backend/app/Services/KeluargaService.php`  
   - Restore full dari commit sehat 360de7c  
   - + method `cariAnggotaAktif`  
   - + filter `q` by nama anggota (EXISTS subquery)

2. `backend/app/Config/Routes.php`  
   - Tambah: `GET warga/cari-anggota` (sebelum `warga/(:num)`)

## File yang sudah OK di repo (ikut di zip untuk referensi)

- `backend/app/Controllers/KeluargaController.php` — method `cariAnggota`
- `frontend/src/pengurus/views/AngkatPengurusView.vue` — floating search
- `frontend/src/shared/services/warga.js`
- `frontend/src/shared/services/warga.real.js`

## Cara push

### Opsi A — GitHub Web
1. Buka file di branch `redesain-ui-warga`
2. Ganti isi dengan file dari zip ini
3. Commit message: `Restore KeluargaService + route cari-anggota (angkat pengurus)`

### Opsi B — Local git
```bash
# dari root repo, branch redesain-ui-warga
cp path/ke/zip-extracted/backend/app/Services/KeluargaService.php backend/app/Services/
cp path/ke/zip-extracted/backend/app/Config/Routes.php backend/app/Config/
php -l backend/app/Services/KeluargaService.php
php -l backend/app/Config/Routes.php
git add backend/app/Services/KeluargaService.php backend/app/Config/Routes.php
git commit -m "Restore KeluargaService + route cari-anggota (angkat pengurus)"
git push origin redesain-ui-warga
```

## Cek setelah push
- `php -l` tidak error
- Size KeluargaService.php ~26 KB (bukan 24 byte / stub)
- Tidak ada kata PLACEHOLDER / SEE_ARTIFACTS / file://
- Coba search di halaman Angkat Pengurus (ketik nama, floating list muncul)
