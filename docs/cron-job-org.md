# Memasang 3 job cron-job.org (Stage 12)

Zona waktu: **Asia/Jakarta (WIB)**.

Semua URL memakai subdomain **pengurus** (dev atau prod).

Header wajib:
```
X-Cron-Token: <isi CRON_TOKEN dari .env>
```

## 1. Harian — 00:05

- URL: `https://pengurus.<domain>/api/tugas/harian`
- Jadwal: setiap hari pukul **00:05**
- Method: GET
- Header: `X-Cron-Token`

## 2. Sore — 17:00

- URL: `https://pengurus.<domain>/api/tugas/sore`
- Jadwal: setiap hari pukul **17:00**
- Method: GET
- Header: `X-Cron-Token`

## 3. Tiap 5 menit

- URL: `https://pengurus.<domain>/api/tugas/5menit`
- Jadwal: setiap **5 menit**
- Method: GET
- Header: `X-Cron-Token`

Lihat juga `docs/PANDUAN-STAGE12-LENGKAP.md`.
