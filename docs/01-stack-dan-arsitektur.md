# 01. Stack dan Arsitektur

Dokumen ini hanya membahas **teknologi, arsitektur, alur pengembangan, deploy, dan model data**. Fitur dan menu ada di `02-fitur-dan-menu.md`. Tampilan dan komponen UI ada di `03-ui-design-system.md`.

Nama aplikasi memakai placeholder `NAMA_APP` (kandidat: Rukun, Balai) dan bisa diubah per RT lewat Pengaturan aplikasi.

---

## 1. Gambaran singkat

- Aplikasi manajemen warga untuk satu RT, dipecah menjadi **dua aplikasi PWA terpisah**:
  - **Aplikasi warga**, contoh `warga.rtdua.my.id`
  - **Aplikasi pengurus** (ketua dan pengurus), contoh `pengurus.rtdua.my.id`
- **Satu kode, satu database, satu backend** untuk keduanya.
- Arsitektur SPA agar terasa seperti aplikasi native.
- Berjalan di **shared hosting entry level** (cPanel, tanpa SSH).
- Dijual ke RT lain nanti: **kode yang sama**, tiap RT punya hosting, domain, database, dan `.env` sendiri. Tidak ada teks khusus RT di kode (bagian 11).
- Pengembangan dilakukan lewat chat AI, bukan di laptop (bagian 6).

---

## 2. Stack

### Frontend
| Komponen | Pilihan |
|---|---|
| Framework | Vue 3 + Vite |
| Routing | Vue Router |
| Styling | Tailwind CSS |
| Bahasa | **JavaScript biasa (tanpa TypeScript)** |
| State | `ref` dan composable, **tanpa Pinia** |
| PWA | `vite-plugin-pwa` (manifest, service worker, offline shell) |
| Ikon | `lucide-vue-next` |
| Font | Plus Jakarta Sans |
| Editor artikel (Program RT) | Tiptap (paragraf, daftar, tabel, gambar) |
| Struktur proyek | `shared/` (komponen dan design system), `warga/`, `pengurus/`, dua entry point, dua build |

TypeScript dan Pinia boleh ditambahkan nanti tanpa membongkar struktur.

Pembuatan file ekspor (PDF dan XLSX), kompresi gambar, overlay foto absen, dan thumbnail galeri semuanya **dilakukan di browser**, bukan di server. Pilihan library untuk PDF dan XLSX ditentukan saat tahap build.

### Backend
| Komponen | Pilihan |
|---|---|
| Framework | PHP **CodeIgniter 4** sebagai **API JSON** (tanpa Laravel) |
| PHP | 8.1 atau lebih baru (syarat CI4), cek di hosting |
| Database | MariaDB/MySQL bawaan hosting |
| Push | library PHP `web-push` (butuh ekstensi `openssl` dan `curl`) |
| Enkripsi | CI4 Encryption (untuk NIK) |

### Hosting dan tooling
- Shared hosting entry level, cPanel, **tanpa SSH**.
- cPanel mendukung **subdomain tanpa batas** dengan **root folder yang bisa diatur** per subdomain.
- GitHub (repo) + GitHub Actions (build dan deploy).
- cron-job.org (pemicu tugas terjadwal, bagian 7).

---

## 3. Arsitektur dua aplikasi

### Subdomain dan folder di hosting
```
rt-app/        backend CI4 (kode, vendor, writable, .env), DI LUAR folder publik mana pun
warga/         root subdomain warga: hasil build aplikasi warga + folder api/ tipis
pengurus/      root subdomain pengurus: hasil build aplikasi pengurus + folder api/ tipis
```
- Folder `api/` di tiap subdomain hanya berisi file kecil yang meneruskan permintaan ke backend bersama di `rt-app/`. Kode backend tetap satu.
- Tiap subdomain memanggil API di alamatnya sendiri (`warga.rtdua.my.id/api`, `pengurus.rtdua.my.id/api`), jadi tidak ada urusan CORS.
- Karena root tiap subdomain diatur sendiri di cPanel, tidak perlu trik `.htaccess` untuk memilih build. `.htaccess` hanya mengarahkan route non-file ke `index.html` (agar SPA tidak 404 saat refresh) dan `/api` ke backend.
- `.env`, `writable/`, dan folder upload sensitif berada di luar atau diproteksi dari akses publik.

### Pembatasan sisi di server
- **Server mengenali sisi dari nama host.**
- Subdomain warga hanya menerima login warga dan route `/api/portal/*`.
- Subdomain pengurus hanya menerima login pengurus dan route pengurus.
- Login atau route sisi yang salah **ditolak di server**, bukan hanya disembunyikan di tampilan.

### Dua aplikasi terpasang
- Masing-masing punya `manifest` sendiri: nama (contoh "Warga RT 002" dan "Pengurus RT 002") dan **ikon berbeda** agar tidak tertukar.
- Pengurus bisa memasang kedua aplikasi sekaligus.
- Aplikasi warga lebih ringan karena kode halaman pengurus tidak ikut diunduh.

### Lingkungan development
Pola yang sama: dua subdomain dev dan **satu database terpisah**, di domain khusus development. Semua uji coba dilakukan di sana, bukan di production.

---

## 4. Autentikasi dan sesi

| | Warga | Pengurus dan ketua |
|---|---|---|
| Kredensial | username + **PIN 6 angka** | username (belum dibahas, bagian 13) + **password** (minimal 8 karakter) |
| Username | gabungan blok-nomor rumah, contoh `AB2-22a` | |
| Akun milik | **satu keluarga** (per rumah) | **individu** (ditautkan ke satu anggota warga) |

- Satu orang bisa punya **dua akun terpisah**: akun pengurus (individu) dan akun warga (keluarganya). Login-nya di dua aplikasi berbeda, dan keduanya bisa hidup bersamaan.
- **Cookie sesi** terpisah per host: `sesi_warga` dan `sesi_pengurus`, **tanpa atribut domain induk** agar tidak dibagi antar subdomain. HttpOnly, Secure, SameSite. CSRF aktif.
- Hash password/PIN memakai `password_hash` bawaan PHP.
- **Rate limit** login dan **penguncian sementara** setelah beberapa kali salah.
- Username warga tidak membedakan huruf besar kecil dan mengabaikan spasi.
- PIN awal `123456`, wajib diganti saat login pertama (aturan lengkap ada di dokumen fitur).
- **Role dicek di server** di setiap route. Warga hanya bisa membaca dan hanya data miliknya.

### Role di database
Hanya tiga: `ketua`, `pengurus`, `warga`.
- **Jabatan** (Bendahara, Sekretaris, dan seterusnya) hanyalah **label teks bebas** yang diketik ketua dan tidak memengaruhi hak akses.
- **Akun developer**: role `ketua` dengan penanda `is_developer`. Dibuat lewat SQL saat setup awal. Perilaku lengkapnya ada di dokumen fitur.
- Ketua pertama dibuat lewat **halaman setup awal** (sekali pakai, otomatis terkunci setelahnya).

---

## 5. Hosting: hal yang perlu dicek sebelum mulai

- Versi PHP (minimal syarat CI4)
- Ekstensi: `intl`, `mbstring`, `curl`, `openssl`, `gd`
- `max_execution_time`, `upload_max_filesize`, `post_max_size`
- Dukungan `.htaccess`
- Kuota inode (folder `vendor/` dan foto galeri banyak file)
- SSL otomatis (AutoSSL atau Let's Encrypt) untuk tiap subdomain
- FTP/FTPS aktif (untuk deploy dari GitHub Actions)
- Layanan luar bisa memanggil alamat di hosting (tidak diblokir firewall), diuji sekali sebelum bergantung pada cron
- Penyimpanan hosting tidak dibatasi asal wajar (galeri tanpa batas jumlah foto)

---

## 6. Alur pengembangan

### Pembagian kerja
- Kode ditulis di chat AI. **Laptop hanya jadi gerbang**: cukup punya Git, tanpa PHP, Node, atau Composer.
- Build dan deploy dikerjakan **GitHub Actions**. Import SQL ke phpMyAdmin dilakukan **manual**.

### Alur satu patch
1. AI mengirim **zip patch**.
2. Ekstrak dan timpa ke folder repo di laptop.
3. Commit lalu push ke GitHub (commit sebelum menimpa agar bisa kembali kalau patch bermasalah).
4. GitHub Actions menjalankan `composer install`, build kedua aplikasi frontend, pengecekan sintaks PHP, dan tes; lalu upload ke hosting.
5. Bila patch menyertakan SQL, import lewat phpMyAdmin. **Urutan: import SQL dulu, baru push kode.**

### Isi patch
- Hanya file yang berubah atau baru, dengan **path persis sama** dengan proyek.
- Catatan singkat berisi: file yang harus **dihapus manual** (zip tidak bisa menghapus), perintah yang perlu dijalankan, dan SQL baru.
- Perubahan database selalu berupa **file SQL bernomor** (`001_...sql`, `002_...sql`). File lama tidak diubah, hanya ditambah.

### Yang dikirim ke AI tiap tahap
- Kirim: kode sumber (`app/`, `frontend/` sumber, `public/` tanpa hasil build), `composer.json`, `package.json`, file konfigurasi (vite, tailwind), folder SQL, dan ketiga dokumen spesifikasi ini.
- Jangan kirim: `node_modules`, `vendor`, `dist`, `writable/`, `.git`, dan **`.env`** (kirim `.env.example`).
- Mulai chat baru tiap tahap besar. Satu patch = satu fitur kecil.
- Kalau ada error, kirim pesan error lengkap atau log merah dari tab Actions.

### GitHub Actions
- Deploy lewat **FTP/FTPS** (misalnya `FTP-Deploy-Action`), hanya mengunggah file yang berubah.
- Kredensial FTP di **GitHub Secrets**.
- Upload hanya berjalan jika build dan pengecekan lolos, jadi kode rusak tidak sampai ke hosting.
- **Tes otomatis untuk logika alokasi pembayaran** (bagian paling rawan salah hitung).
- Satu repo bisa dideploy ke banyak RT, masing-masing dengan secret sendiri.
- Disarankan dua branch (`dev` dan `main`) ke domain development dan production.

### File `.env`
Dibuat **manual sekali** di hosting lewat File Manager, tidak masuk repo. Isinya password database, kunci enkripsi NIK, token cron, dan kunci push (VAPID). **Kunci enkripsi harus dicadangkan di tempat aman**, karena jika hilang atau berubah semua NIK tersimpan tidak bisa dibuka lagi.

### Migrasi database
Import `.sql` bernomor lewat phpMyAdmin. Struktur migrasi disiapkan agar kelak bisa ada halaman "Perbarui database" khusus akun developer (opsional, tidak dikerjakan sekarang).

---

## 7. Cron eksternal (cron-job.org)

Hosting tidak punya menu cron. Semua tugas **terjadwal** dipicu **cron-job.org** yang membuka satu alamat khusus di aplikasi sesuai jadwal.

### Mekanisme
- Satu alamat tugas (contoh `pengurus.rtdua.my.id/api/tugas-harian`) dengan parameter berbeda per job.
- **Token rahasia dikirim lewat header**, disimpan di `.env`. Tanpa token, alamat itu menolak.
- Zona waktu job: **WIB**; aplikasi memakai `Asia/Jakarta`.
- Notifikasi kegagalan job lewat email ke developer.
- Setiap RT baru butuh job-nya sendiri (alamat berbeda).
- Batasan layanan gratis (jumlah job, interval minimum) dicek saat mendaftar.

### Tiga job
1. **Harian, 00.05 WIB:**
   - Tanggal 1: membuat tagihan kas bulan baru, membuat akun dan tagihan untuk keluarga yang terdaftar bulan lalu, membuat malam ronda bulan itu dari jadwal tetap dan khusus
   - Menghapus foto absen dan bukti transfer yang melewati masa simpan, serta notifikasi lama
2. **Sore, 17.00 WIB:** pengingat "malam ini giliran ronda keluargamu".
3. **Tiap 5 menit:** mengirim ulang push yang gagal dan menghapus langganan push yang sudah mati.

Penghitungan denda ronda bulan lalu dan penguncian absensi harus berjalan **setelah ronda terakhir bulan lalu selesai** (usulan: tanggal 1 setelah pukul 12.00). Pemicunya ditentukan saat build (bagian 13).

### Aturan tugas
- **Idempoten**: dipanggil dua kali tidak boleh membuat tagihan atau denda ganda (dicatat di tabel `tugas_log`).
- **Ringan dan dibagi**: pekerjaan berat dipecah per bagian karena layanan cron punya batas waktu tunggu.
- **Kejadian realtime tidak lewat cron.** Pengumuman baru, permintaan konfirmasi, dan pembayaran dikonfirmasi dikirim seketika oleh aksinya. Cron hanya untuk yang terjadwal dan percobaan ulang.
- **Pemicu cadangan saat akses** dipertahankan untuk tagihan bulanan: bila cron mati, tagihan bulan baru tetap terbit saat ada yang membuka aplikasi.
- Alternatif cadangan lain: workflow GitHub Actions terjadwal (waktunya bisa meleset).

---

## 8. Notifikasi dan push

### Prinsip
- **Semua notifikasi di aplikasi ikut dikirim sebagai push.** Satu tabel `notifikasi`: daftar di aplikasi dan push dibuat dari baris yang sama, jadi isinya pasti sama.
- Notifikasi **mengikuti akun**: akun warga menerima notifikasi keluarga, akun pengurus menerima notifikasi pengurus. Dua aplikasi, dua langganan push terpisah.

### Pengiriman
- Dikirim **setelah respons ke pengguna selesai**, jadi tidak terasa lambat.
- Dikirim serentak per kelompok kecil (sekitar 25 perangkat sekali kirim) memakai antrean `web-push`.
- Untuk sekitar 50 KK, ini cukup tanpa antrean berat. Kejadian massal dikirim sebagai **satu notifikasi per keluarga**.
- Kegagalan tidak mengganggu proses utama. Percobaan ulang oleh job cron tiap 5 menit. Langganan kedaluwarsa dihapus otomatis.
- Satu akun boleh punya beberapa perangkat.

### Izin dan batasan
- Izin notifikasi diminta lewat **tombol yang diketuk pengguna** (browser memblokir permintaan otomatis).
- **iPhone** hanya menerima push bila aplikasi sudah **dipasang ke layar utama** (iOS 16.4 ke atas). Android dan desktop langsung bisa.
- Isi push **singkat dan tanpa data sensitif** (tanpa NIK).
- Mengetuk push membuka halaman tujuan (route) dan menandai notifikasi terbaca.

### Pembaruan di dalam aplikasi
Aplikasi memeriksa jumlah permintaan menunggu dan notifikasi baru secara berkala saat terbuka dan saat kembali ke aplikasi (`visibilitychange`).

---

## 9. Upload dan penyimpanan file

| Jenis | Pemrosesan | Penyimpanan | Masa simpan |
|---|---|---|---|
| Bukti transfer | kompres di browser (sisi terpanjang sekitar 1600px, sekitar 300 KB), server validasi tipe JPG/PNG/WebP dan ukuran, nama file diacak | `writable/`, di luar folder publik | **90 hari** setelah diproses |
| Foto absen ronda | kamera langsung, overlay tanggal dan jam digambar ke kanvas lalu disimpan satu JPEG (sekitar 200 KB) | `writable/` | **30 hari** (catatan hadir tetap) |
| Foto galeri | kompres di browser (sisi terpanjang sekitar 2000px), thumbnail dibuat di browser, **diunggah satu per satu dengan indikator progres** | `writable/` | tanpa batas, tanpa batas jumlah |
| Lampiran pengumuman | satu file PDF atau gambar | `writable/` | selama pengumuman ada |
| Banner Program RT, logo, pas foto | kompres di browser | `writable/` | selama dipakai |
| Notifikasi (data) | | database | **90 hari** |

- Semua file **hanya dilayani lewat endpoint yang mengecek login dan role**, bukan lewat alamat terbuka.
- Bukti transfer hanya bisa dibuka pengurus dan keluarga pengirimnya.
- Galeri hanya bisa dibuka warga dan pengurus yang sudah login.
- Unduhan galeri menghasilkan **versi terkompres**, bukan file asli kamera.
- Karena foto diunggah satu per satu, batas ukuran dan waktu PHP di hosting entry level tidak terlampaui, dan foto yang gagal bisa diulang tanpa mengulang semuanya.

---

## 10. Keamanan dan privasi (teknis)

- **NIK**: dienkripsi di database (CI4 Encryption), tidak bisa dicari, tersembunyi default di layar pengurus. Tabel menyimpan juga **hash NIK (HMAC)** untuk cek NIK ganda tanpa mendekripsi.
- Endpoint `/api/portal/*` **tidak pernah mengirim** kolom NIK, telepon, atau data keluarga lain.
- Isi artikel Program RT disimpan sebagai HTML dan **wajib dibersihkan (sanitize) di server**.
- **Audit log append-only**: tidak ada route untuk mengubah atau menghapusnya, termasuk untuk ketua. Mencatat siapa, kapan, aksi, objek, serta nilai sebelum dan sesudah.
- Validasi input, query terparameter (query builder CI4), security header (CSP, HSTS).
- **Jam server adalah acuan** untuk absen ronda dan semua pencatatan waktu.
- Perubahan lewat phpMyAdmin **tidak tercatat** di audit log. Pemakaiannya hanya untuk hal teknis (import SQL, pemulihan darurat).

---

## 11. Multi-RT (dijual ke RT lain)

Setiap RT mendapat hosting, domain, database, dan `.env` sendiri dengan **kode yang sama persis**. Syaratnya:
- Semua yang khusus RT disimpan di **database** dan diatur lewat Pengaturan aplikasi: nama aplikasi, ikon aplikasi, nama RT, nama perumahan, logo RT, logo desa, isi kop, dan daftar blok.
- `manifest` PWA dibuat dari pengaturan itu, jadi nama dan ikon ikut berubah per RT.
- Nominal kas dan denda serta jam ronda juga di database (Pengaturan warga).
- **Kunci enkripsi NIK unik per RT.** Satu `.env` tidak boleh dipakai bersama.
- **Halaman setup awal** (sekali pakai, otomatis terkunci) menggantikan SQL manual untuk membuat akun ketua dan mengisi pengaturan awal.
- Menyiapkan RT baru: buat dua subdomain dan database, upload, import skema, buka halaman setup, pasang tiga job cron, isi secret deploy.
- Aplikasi tidak boleh memuat nama blok, nama perumahan, atau nominal yang ditulis langsung di kode.

---

## 12. Model data (usulan, boleh disesuaikan saat build)

**Pengaturan dan master**
- `pengaturan`: nama_app, ikon_app, nama_rt, nama_perumahan, logo_rt, logo_desa, isi kop, akses_warga (bool), nominal_kas, denda_ronda, jam_ronda_default (mulai dan selesai).
- `blok`: nama, aktif. Daftar dinamis, contoh RT pertama: AB1, AB2, AB11, AB12.

**Keluarga dan warga**
- `keluarga`: blok_id, **nomor** (angka), **akhiran** (opsional, contoh `a`), alamat (opsional), telepon (opsional, milik keluarga), status `aktif|pindah`, tanggal_pindah, catatan_pindah, mulai_periode (bulan pertama tagihan). Kombinasi blok, nomor, akhiran unik di antara keluarga aktif (dicek di aplikasi).
- `warga` (anggota): keluarga_id, nama, hubungan (terhadap kepala keluarga rumah itu; hanya satu "Kepala keluarga" per keluarga), no_kk (opsional, di level anggota), nik_enc, nik_hash, jenis_kelamin, tempat_lahir, **tanggal_lahir (tanggal sungguhan)**, agama, pekerjaan, foto (opsional), status `aktif|meninggal|keluar`, tanggal_status, alasan.

**Akun**
- `users`: role `ketua|pengurus|warga`, username unik, password_hash, keluarga_id (untuk warga), warga_id (untuk pengurus, unik), jabatan (teks bebas), nomor_hp, tampil_di_bantuan, urutan_struktur, is_developer, aktif, harus_ganti_kredensial.

**Keuangan**
- `iuran_khusus`: nama, periode, nominal, dibatalkan.
- `iuran_tagihan`: keluarga_id, periode `YYYY-MM`, jenis `kas|denda_ronda|khusus`, iuran_khusus_id (nullable), ronda_malam_id (nullable), nominal, dibatalkan, dibatalkan_oleh, dibatalkan_pada, alasan. **Tanpa kolom terbayar dan status**: keduanya dihitung.
- `pembayaran`: keluarga_id, tanggal_bayar, metode `tunai|transfer`, nominal, catatan, dicatat_oleh, dicatat_pada, permintaan_id (nullable), dibatalkan, dibatalkan_oleh, dibatalkan_pada, alasan_batal.
- `pembayaran_permintaan`: keluarga_id, diajukan_oleh, nominal_diajukan, nama_pengirim, bank_pengirim, bukti_file, diajukan_pada, status `menunggu|dikonfirmasi|ditolak`, diproses_oleh, diproses_pada, nominal_dikonfirmasi, alasan_tolak, pembayaran_id, bukti_dihapus_pada.
- `kas_transaksi`: tipe `masuk|keluar`, nominal, kategori, metode, keterangan, tanggal, keluarga_id (opsional), pembayaran_id (untuk kas otomatis dari iuran), dibatalkan (dengan siapa, kapan, alasan), dicatat_oleh, dicatat_pada.

Total tagihan keluarga = jumlah tagihan (tidak dibatalkan) dikurangi jumlah pembayaran (tidak dibatalkan). Rincian per tagihan **dihitung ulang** tiap dibuka memakai urutan prioritas, sehingga perubahan nominal dan pembatalan langsung terhitung.

**Ronda**
- `ronda_jadwal_tetap` (+ daftar keluarga per hari): hari, jam_mulai, jam_selesai (nullable, jatuh ke default).
- `ronda_jadwal_khusus` (+ daftar keluarga): tanggal, jam, keterangan.
- `ronda_malam`: tanggal, jam_mulai, jam_selesai, sumber `tetap|khusus|manual`. `ronda_malam_keluarga`: malam_id, keluarga_id.
- `ronda_absen`: malam_id, keluarga_id, waktu_server, waktu_klien, foto, sumber `warga|manual`, dicatat_oleh (untuk manual), dibatalkan (dengan siapa, kapan).
- `ronda_kunci_bulan`: periode, dikunci_pada.

**Konten**
- `pengumuman`: judul, isi, lampiran_file, lampiran_tipe, pin (maksimal 2 aktif), diterbitkan_pada (diperbarui saat diedit), dibuat_oleh, diubah_oleh.
- `program_rt`: judul, banner, isi_html, status `direncanakan|berjalan|selesai`.
- `galeri_album`: judul, tanggal_kegiatan. `galeri_foto`: album_id, file, thumb, lebar, tinggi, diunggah_oleh.

**Sistem**
- `notifikasi`: user_id, jenis, judul, isi, tautan, dibaca_pada, dibuat_pada.
- `push_langganan`: user_id, endpoint, kunci, perangkat, aktif, jumlah_gagal.
- `audit_log`: waktu, user_id, **sumber** `pengguna|sistem_otomatis|developer`, **tampil** (bool), aksi, objek, objek_id, sebelum (JSON), sesudah (JSON), ip.
- `tugas_log`: jenis, periode, selesai_pada (idempotensi cron).
- `login_percobaan` (rate limit).

---

## 13. Belum diputuskan (diputuskan saat tahap build atau diskusi lanjutan)

1. **Backup**: jadwal ekspor database (usulan: berkala, lewat cron-job.org ke tempat di luar hosting) dan cadangan kunci enkripsi NIK. Belum dirancang.
2. **Waktu eksekusi** penghitungan denda ronda dan penguncian absensi (bagian 7).
3. **Username login pengurus** (format identitas untuk login dengan password).
4. **Library** untuk ekspor PDF dan XLSX.
5. **Layanan baca KK** untuk fitur Scan KK (layanan AI cloud atau OCR di browser), termasuk keputusan soal mengirim foto KK ke luar. Dikerjakan paling akhir.
6. Pemicu cadangan tagihan saat akses tetap dipakai (usulan yang tidak dibantah). Bila ingin murni cron, bilang saja.
