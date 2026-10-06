# rtdua — Aplikasi Manajemen Warga RT

## Stage saat ini: Stage 10

Backend data warga, blok, pengaturan, pengurus, aktivitas. UI default masih **mock** sampai saklar di `frontend/src/shared/config/dataSource.js` diganti.

### API utama

| Modul | Route |
|-------|-------|
| Pengaturan | `GET/PUT /api/pengaturan/*`, `GET/POST /api/blok` |
| Keluarga | `GET/POST /api/warga`, `GET/PUT /api/warga/:id`, anggota, pindah, reset-pin |
| Pengurus | `GET/POST /api/pengurus`, `GET /api/struktur`, `GET /api/bantuan` |
| Aktivitas | `GET /api/aktivitas` |
| Portal warga | `GET /api/portal/warga` (tanpa NIK) |

### Hubungkan UI ke API

```js
// frontend/src/shared/config/dataSource.js
export const USE_MOCK = {
  auth: false,
  pengaturan: false,
  warga: false,
  aktivitas: false,
}
```

### Env

Set `encryption.key` di `backend/.env` (unik per RT, cadangkan aman).
