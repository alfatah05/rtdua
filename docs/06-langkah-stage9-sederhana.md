# Stage 9 — Langkah sederhana (hampir semua di GitHub Actions)

## Prinsip

| Tempat | Apa yang dikerjakan |
|--------|---------------------|
| **Windows (lokal)** | Hanya: ekstrak patch → `git add` → `git commit` → `git push` |
| **GitHub Actions** | Build frontend, `composer install` backend, upload FTP otomatis |
| **cPanel (sekali saja)** | Buat database, import SQL, buat file `.env` — **tidak bisa diganti Actions** karena butuh akses phpMyAdmin & rahasia DB |

---

## A. Sekali di cPanel (manual, ±10 menit)

Lakukan **satu kali**. Setelah ini, push kode saja sudah cukup.

### A1. Database
1. cPanel → **MySQL Databases** → buat database + user, hubungkan user ke DB.
2. Catat: nama DB, username, password.
3. **phpMyAdmin** → pilih database → **Import** → unggah file  
   `sql/001_skema_awal.sql` dari repo → Go.

### A2. Folder backend
Pastikan ada folder di **atas** atau **sejajar** subdomain (yang bisa diakses FTP account), contoh:
```
/home/USERNAME/rt-app/          ← backend (bukan di dalam public_html kalau bisa)
/home/USERNAME/public_html/aa_sub_domain/warga-dev/
/home/USERNAME/public_html/aa_sub_domain/pengurus-dev/
```
Actions akan mengisi isinya lewat FTP.

### A3. File `.env` di server (RAHASIA — jangan di-commit)
Di File Manager, masuk folder `rt-app/`, buat file `.env`:

```env
CI_ENVIRONMENT = production

app.baseURL = 'https://pengurus-dev.rtdua.my.id/'
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = NAMA_DB_KAMU
database.default.username = USER_DB_KAMU
database.default.password = PASSWORD_DB_KAMU
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

### A4. GitHub Secrets (Settings → Secrets and variables → Actions)

| Secret | Contoh isi |
|--------|------------|
| `FTP_SERVER` | `ftp.domainmu.com` atau IP |
| `FTP_USERNAME` | user FTP |
| `FTP_PASSWORD` | password FTP |
| `FTP_SERVER_DIR_WARGA` | path folder warga-dev, contoh `/public_html/aa_sub_domain/warga-dev/` |
| `FTP_SERVER_DIR_PENGURUS` | path folder pengurus-dev |
| `FTP_SERVER_DIR_BACKEND` | path folder `rt-app/`, contoh `/rt-app/` |

Path harus **absolut dari root FTP** (yang terlihat saat login FTP).

---

## B. Di Windows (setiap update kode)

```bat
:: 1. Ekstrak patch Stage 9, timpa folder di repo
:: 2. Commit & push
git add .
git commit -m "Stage 9 backend fondasi"
git push origin dev
```

Tidak perlu Node/PHP/composer di Windows.

---

## C. GitHub Actions (otomatis setelah push)

Workflow `Deploy Dev` akan:
1. `composer install` di `backend/`
2. `npm install` + `npm run build` di `frontend/`
3. FTP upload:
   - build warga → folder warga-dev
   - build pengurus → folder pengurus-dev
   - backend → folder rt-app

Cek tab **Actions** di GitHub: harus hijau.

---

## D. Setelah deploy sukses — setup aplikasi (sekali)

Pakai browser atau tools seperti Postman / `curl`:

```text
POST https://pengurus-dev.rtdua.my.id/api/setup
Content-Type: application/json

{
  "nama_rt": "Rukun Warga 002",
  "nama_perumahan": "Perum. Pesona Gading Cibitung 2",
  "username": "ketua",
  "password": "passwordminimal8",
  "nama_ketua": "Budi Santoso",
  "blok": ["AB1", "AB2", "AB11", "AB12"]
}
```

Kalau sukses, setup terkunci. Login di aplikasi pengurus dengan username/password itu.

Uji cepat:
- `GET https://pengurus-dev.rtdua.my.id/api/health`
- `GET https://warga-dev.rtdua.my.id/api/health`  
  → harus `ok: true`, `side` sesuai subdomain.

---

## Ringkas

```
[Windows]  git push
    ↓
[GitHub Actions]  build + FTP deploy
    ↓
[Hosting]  sudah terisi kode
    ↓
[Kamu sekali]  SQL + .env + POST /api/setup
    ↓
Selesai Stage 9
```

Kalau FTP path masih salah, cek log Actions atau sesuaikan Secrets `FTP_SERVER_DIR_*`.
