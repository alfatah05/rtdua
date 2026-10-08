# rtdua — Aplikasi Manajemen Warga RT

Dua PWA: **warga** (`warga-dev.rtdua.my.id`) dan **pengurus** (`pengurus-dev.rtdua.my.id`).  
Stack: CodeIgniter 4 + Vue 3/Vite. Deploy dev lewat GitHub Actions **manual** (`workflow_dispatch`).

## Status umum

| Area | Status |
|------|--------|
| Stage 0–8 (UI kerangka) | Selesai |
| Stage 9 (auth, session, audit) | Selesai |
| Stage 10–13 (backend + hubung UI) | **Sebagian** — lihat backlog di bawah |
| Stage 14 (Scan KK) | Belum |
| Stage 15 (hardening / prod) | Belum |
| `USE_MOCK` di `frontend/src/shared/config/dataSource.js` | Semua modul **`false`** (API real) |

**Cara pakai backlog:** tiap item di bawah = belum selesai.  
Kalau fitur sudah ditambahkan / diuji OK → **hapus baris itu dari README** (jangan biarkan menumpuk).

---

## Backlog — belum ada / belum lengkap

### Halaman masih Placeholder (dummy)

- [ ] `/warga/:id/tambah-anggota` — UI tambah anggota (backend `tambahAnggota` sudah ada)
- [ ] `/warga/:id/meninggal` — UI tandai meninggal / keluarkan anggota (backend status anggota sudah ada)
- [ ] `/warga/:id/reset-pin` — route masih Placeholder (aksi reset PIN sudah ada di **detail warga**; rapihkan route/menu)
- [ ] `/ronda/malam/edit` — ganti keluarga di malam ronda (edit malam)
- [ ] `/ronda/isi-otomatis` — isi otomatis jadwal ronda
- [ ] `/warga/scan-kk` — Scan KK (**Stage 14**, sengaja belakangan)

### Stage 10 — Data warga & pengaturan

- [ ] UI **tambah anggota** lengkap (form, validasi, refresh detail)
- [ ] UI **tandai meninggal / keluarkan** anggota
- [ ] Alur keluarga **"mulai bulan depan"** + data belum lengkap — uji end-to-end di dev
- [ ] Pembukaan **NIK** di pengurus + tercatat di Aktivitas — pastikan UI lengkap
- [ ] (opsional) rapikan dokumentasi API di README ini agar selaras endpoint terbaru

### Stage 11 — Keuangan

- [ ] Card iuran warga: tiga kondisi **ada tagihan / lunas / kelebihan bayar** — uji di data nyata
- [ ] **Batalkan pembayaran** end-to-end
- [ ] **Batalkan denda** end-to-end
- [ ] Ubah nominal kas/denda + toggle **"terapkan ke bulan ini"** + **pratinjau dampak**
- [ ] Kategori kas sesuai dokumen (saldo awal, pengembalian kelebihan, dll.)
- [ ] Pastikan unit test alokasi jalan di CI (file `backend/tests/unit/AlokasiPembayaranTest.php` ada)

### Stage 12 — Notifikasi, push, cron

- [ ] Pasang **3 job** di cron-job.org (harian, sore, tiap 5 menit) + header token
- [ ] Uji **push** di Android (dan iPhone setelah Add to Home Screen)
- [ ] Kelengkapan jenis notifikasi sesuai dokumen 02 (bukan hanya sebagian event)
- [ ] Badge lonceng **belum dibaca** konsisten di app warga & pengurus

### Stage 13 — Konten & ronda

- [ ] **Absen warga + foto** — endpoint backend + UI di app warga (saat ini warga hanya lihat jadwal)
- [ ] **Edit malam ronda** (ganti keluarga)
- [ ] **Isi otomatis** ronda
- [ ] Detail **program** di app warga (drill-down, bukan hanya list)
- [ ] Detail **album galeri** di app warga (lihat foto / unduh)
- [ ] Penguncian absensi + terbit denda lewat **job cron** — uji siklus penuh di dev
- [ ] Keputusan produk: hapus pengumuman / hapus album (dokumen masih terbuka)

### Stage 14 — Scan KK

- [ ] Seluruh fitur Scan KK (UI + backend OCR/parsing sesuai dokumen 02)

### Stage 15 / operasional

- [ ] Hardening production (env, HTTPS, secret, rate limit, dll.)
- [ ] Optimasi deploy: **skip FTP backend/vendor** bila yang berubah hanya frontend (FTP backend sering ~30 menit)
- [ ] System nav bar Android: perilaku OEM tidak konsisten; theme-color saat ini **#000000** tetap

---

## Deploy (dev)

- Workflow: `.github/workflows/deploy-dev.yml`
- **Tidak auto** saat push — hanya **Actions → Deploy Dev → Run workflow** (manual)
- Target: warga-dev, pengurus-dev, folder backend `rt-app`

## Env penting

- `backend/.env`: database, `encryption.key` (unik per RT, cadangkan aman), VAPID (push), token cron tugas

## Dokumen acuan

- `docs/01-stack-dan-arsitektur.md`
- `docs/02-fitur-dan-menu.md`
- `docs/03-ui-design-system.md`
- `docs/04-prompt-build-bertahap.md`
