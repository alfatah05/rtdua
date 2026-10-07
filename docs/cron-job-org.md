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

Isi: pastikan tagihan kas bulan berjalan, notifikasi tagihan baru (tgl 1), bersihkan notifikasi/file lama.

## 2. Sore — 17:00

- URL: `https://pengurus.<domain>/api/tugas/sore`
- Jadwal: setiap hari pukul **17:00**
- Method: GET
- Header: `X-Cron-Token`

Isi: pengingat ronda malam ini (penuh setelah Stage 13).

## 3. Tiap 5 menit

- URL: `https://pengurus.<domain>/api/tugas/5menit`
- Jadwal: setiap **5 menit**
- Method: GET
- Header: `X-Cron-Token`

Isi: ulang kirim push yang gagal; nonaktifkan langganan mati.

## Uji manual (Postman)

```http
GET https://pengurus.<domain-dev>/api/tugas/harian
Header: X-Cron-Token: <token>
```

Jalankan **dua kali** — respons kedua harus `skip` / tidak menambah tagihan ganda.

## .env yang perlu

```env
CRON_TOKEN=buat_string_acak_panjang
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:email-kamu@contoh.com
```

Generate VAPID (di mesin yang punya PHP + library, atau online tool terpercaya sekali):

```bash
# setelah composer install di CI/server
php -r "require 'vendor/autoload.php'; print_r(Minishlink\WebPush\VAPID::createVapidKeys());"
```

Simpan kunci di `.env` hosting (bukan di repo).
