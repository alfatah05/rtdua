# rtdua — Aplikasi Manajemen Warga RT

Dua PWA: **warga** (`warga-dev.rtdua.my.id`) dan **pengurus** (`pengurus-dev.rtdua.my.id`).  
Stack: CodeIgniter 4 + Vue 3/Vite. Deploy dev lewat GitHub Actions **manual** (`workflow_dispatch`).

## Status umum

| Area | Status |
|------|--------|
| Stage 0–8 (UI kerangka) | Selesai |
| Stage 9 (auth, session, audit) | Selesai |
| Stage 10–13 (backend + hubung UI) | **Sebagian** — lihat backlog |
| Stage 14 (Scan KK) | Belum |
| Stage 15 (hardening / prod) | Belum |
| `USE_MOCK` | Semua modul **`false`** |

**Backlog:** hapus baris setelah fitur diuji OK di dev.

---

## Backlog — belum ada / belum lengkap

### Placeholder

- [ ] `/warga/scan-kk` — Scan KK (**Stage 14**)

### Stage 10

- [ ] Alur keluarga **"mulai bulan depan"** — uji end-to-end di dev

### Stage 11 — Keuangan

- [ ] Card iuran 3 kondisi / batalkan bayar / denda — **kode sudah**; uji data nyata
- [ ] Ubah nominal + terapkan bulan ini + pratinjau — **kode sudah**; uji di pengaturan warga
- [ ] Kategori kas manual — **kode sudah** (Saldo awal, Sumbangan, Pengembalian kelebihan, Operasional, …)
- [ ] Unit test alokasi di CI

### Stage 12 — Notifikasi, push, cron

- [ ] Pasang **3 job** di cron-job.org (lihat bagian Cron di bawah)
- [ ] Uji **push** di Android / iPhone (A2HS)
- [ ] Kelengkapan jenis notifikasi (dokumen 02)
- [ ] Badge lonceng konsisten (header sudah ada; uji setelah baca)

### Stage 13 — Konten & ronda

- [ ] Absen warga + foto / ganti keluarga / detail program-galeri — **kode sudah**; uji setelah deploy
- [ ] **Isi otomatis** ronda — **kode sudah**; uji di pengurus (bagi KK ke jadwal tetap + generate malam)
- [ ] Penguncian absensi + denda lewat cron — uji siklus penuh
- [ ] Keputusan produk: hapus pengumuman / album

### Stage 14–15

- [ ] Scan KK
- [ ] Hardening production
- [ ] Deploy: opsi **skip backend** sudah ada di workflow (input `deploy_backend`)

---

## Deploy (dev)

1. Actions → **Deploy Dev** → Run workflow
2. Input:
   - `deploy_frontend` (default true)
   - `deploy_backend` (default true) — **matikan** jika hanya ubah UI agar FTP tidak 30 menit

## Cron (cron-job.org)

Pasang 3 job, method GET, header `X-Cron-Token: <token dari backend/.env>`:

| Jadwal | URL |
|--------|-----|
| Harian ~00:10 | `https://warga-dev.rtdua.my.id/api/tugas/harian` |
| Sore 17:00 | `https://warga-dev.rtdua.my.id/api/tugas/sore` |
| Tiap 5 menit | `https://warga-dev.rtdua.my.id/api/tugas/5menit` |

(Token harus sama dengan filter CronToken di backend.)

## Env penting

- `backend/.env`: DB, `encryption.key`, VAPID, token cron

## Dokumen

- `docs/01` … `docs/04`
