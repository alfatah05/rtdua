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

> **Selesai 2026-10-09:** UI tambah anggota, status meninggal/keluarkan (+ganti kepala), buka NIK + audit.  
> **Kode siap 2026-10-09 (perlu uji di dev setelah deploy):** absen warga + foto, ganti keluarga malam, detail program & album galeri warga, card iuran 3 kondisi, batalkan pembayaran/denda di UI pengurus.

- [ ] `/ronda/isi-otomatis` — isi otomatis jadwal ronda (regu tetap vs bergiliran masih terbuka di dokumen)
- [ ] `/warga/scan-kk` — Scan KK (**Stage 14**, sengaja belakangan)

### Stage 10 — Data warga & pengaturan

- [ ] Alur keluarga **"mulai bulan depan"** + data belum lengkap — uji end-to-end di dev
- [ ] (opsional) rapikan dokumentasi API di README ini agar selaras endpoint terbaru

### Stage 11 — Keuangan

- [ ] Card iuran warga 3 kondisi — **kode sudah**; uji data nyata (tagihan / lunas / kelebihan)
- [ ] **Batalkan pembayaran** — **UI + API sudah**; uji end-to-end di dev
- [ ] **Batalkan denda** — **UI + API sudah**; uji end-to-end di dev
- [ ] Ubah nominal kas/denda + toggle **"terapkan ke bulan ini"** + **pratinjau dampak**
- [ ] Kategori kas sesuai dokumen (saldo awal, pengembalian kelebihan, dll.)
- [ ] Pastikan unit test alokasi jalan di CI (file `backend/tests/unit/AlokasiPembayaranTest.php` ada)

### Stage 12 — Notifikasi, push, cron

- [ ] Pasang **3 job** di cron-job.org (harian, sore, tiap 5 menit) + header token
- [ ] Uji **push** di Android (dan iPhone setelah Add to Home Screen)
- [ ] Kelengkapan jenis notifikasi sesuai dokumen 02 (bukan hanya sebagian event)
- [ ] Badge lonceng **belum dibaca** konsisten di app warga & pengurus

### Stage 13 — Konten & ronda

- [ ] **Absen warga + foto** — **kode backend+UI sudah**; uji di jam ronda di warga-dev
- [ ] **Ganti keluarga malam** — **kode backend+UI sudah**; uji di pengurus-dev
- [ ] **Isi otomatis** ronda
- [ ] Detail program & album galeri warga — **kode sudah**; uji di warga-dev
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
