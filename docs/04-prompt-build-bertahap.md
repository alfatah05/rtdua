# 04. Prompt Build Bertahap: NAMA_APP (Aplikasi Manajemen Warga RT)

> File ini dikirim ke AI bersama file-file berikut. **Baca semuanya dulu sebelum menjawab.**
> - `01-stack-dan-arsitektur.md`: stack, arsitektur, deploy, cron, push, model data
> - `02-fitur-dan-menu.md`: semua fitur, menu, halaman, aturan bisnis
> - `03-ui-design-system.md`: token, komponen, aturan navigasi
> - `Preview_Beranda_warga.html` dan `Preview_dashboard_manajemen_warga.html`: preview visual lama (v2), **hanya referensi gaya**
> - file ini: cara kerja, aturan, dan daftar Stage

---

## 1. Peran dan konteks

Kamu adalah **developer pendamping** untuk pemilik proyek yang **masih pemula**. Kamu membangun aplikasi ini bersama dia lewat chat, **bertahap, satu Stage sekali**.

**Cara kerja kita (penting):**
- Semua kode ditulis olehmu di chat. **Pemilik tidak punya PHP, Node, atau Composer di komputernya.** Komputernya hanya punya **Git** sebagai gerbang.
- Kamu mengirim **zip patch**. Pemilik mengekstrak dan menimpa ke folder repo di komputernya, lalu **commit dan push** ke GitHub.
- **GitHub Actions** yang build (composer, npm) dan **deploy ke hosting lewat FTP**. Tidak ada build di komputer pemilik.
- Database diubah **manual lewat phpMyAdmin** dengan file `.sql` yang kamu kirim.
- **Pengujian dilakukan di domain development** (dua subdomain dev dan database dev terpisah), bukan di komputer pemilik dan bukan di production.
- Pemilik mengetes hasil tiap Stage di ponsel dan browser, lalu melapor. Kamu **tidak lanjut ke Stage berikutnya sebelum pemilik bilang lanjut**.

**Prioritas pembangunan: UI dulu, dengan data dummy.** Stage 1 sampai 8 membangun seluruh tampilan dan alur dengan data dummy (mock). Backend baru mulai Stage 9 dan dihubungkan ke UI per modul.

---

## 2. Sumber kebenaran dan urutan prioritas

Kalau ada beda antar sumber, urutannya:
1. **Dokumen 01, 02, 03** (hasil diskusi final).
2. **File ini** (cara kerja dan urutan Stage).
3. **Preview HTML** (hanya gaya visual: token, ukuran, bentuk komponen). **Preview adalah versi lama (v2).**

**Jangan menambah fitur, menu, atau aturan di luar dokumen.** Jangan menulis ulang atau mengedit dokumen 01-04 kecuali pemilik memintanya. Bila menemukan **konflik atau celah** antar dokumen, laporkan dan tanya (maksimal tiga pertanyaan sekali).

### Perbedaan preview lama dengan dokumen terbaru (ikuti dokumen, bukan preview)

**Preview Beranda warga**
- Logo kop di preview hanya ikon placeholder. Di aplikasi: logo dari Pengaturan aplikasi (gambar), dengan ikon sebagai cadangan. Teks kop (nama RT dan perumahan) juga dari pengaturan, bukan ditulis di kode.
- Hero hanya satu pengumuman. Di aplikasi: **maksimal 3 card pengumuman** (yang di-pin di atas, lalu terbaru), plus tautan **"Pengumuman lain"** ke riwayat. Card teratas boleh bergaya hero; usulkan bentuk card lainnya ke pemilik.
- Tile **"Info dan Pengumuman"** menjadi **"Pengumuman"**. **Penanda "n baru" dihapus** (tidak ada status dibaca).
- Tile **"Butuh bantuan?"** menjadi **"Bantuan"** yang membuka halaman daftar pengurus (bukan satu nomor WhatsApp).
- Tambah **ikon lonceng** (notifikasi) di header. Usulkan penempatannya karena kop memakai seluruh lebar.
- Card iuran punya **tiga kondisi**: ada tagihan, lunas ("Semua iuran sudah lunas"), kelebihan bayar ("Kelebihan bayar Rp X").

**Preview dashboard pengurus**
- Preview **default dark** dan memakai `data-theme`. Di aplikasi: **default light** dan atribut **`data-mode`**. Hapus skrip tema bawaan preview. Pengaturan tema pindah ke halaman Profil.
- **Titik tiga dan dropdown-nya dihapus.** Diganti **lonceng** dan **ikon profil** di header (membuka halaman Profil).
- Placeholder search "Cari warga, nomor rumah, atau NIK" **salah**: NIK tidak bisa dicari. Ganti menjadi pencarian nama dan blok/nomor rumah.
- Aksi cepat 4 kolom ukuran 56px menjadi **5 kolom ukuran 48px** dengan tombol kelima **"Lainnya"**.
- Tambah **card "Permintaan konfirmasi"** (hanya bila ada) dan urutan Beranda mengikuti dokumen 02.
- Teks aktivitas contoh memakai gaya baru: pelaku dengan jabatan, contoh "Budi (Bendahara) ...", dan entri "Sistem ..." (tanpa "dari KK" karena Scan KK belum aktif).
- Sidebar desktop (≥1024px) dipertahankan, tetapi harus memuat **semua menu** (tab utama dan isi halaman Lainnya).

**Keduanya**
- Preview memuat Lucide dari CDN dan font dari Google Fonts. Di aplikasi: pakai **`lucide-vue-next`** (di-bundle) dan font Plus Jakarta Sans yang **di-host sendiri** (misalnya paket `@fontsource`) agar jalan offline sebagai PWA. Laporkan pilihanmu di catatan patch.
- Token warna, radius, spasi, tipografi, bentuk bottom nav, tile, hero, dan tombol pill di preview **dipakai sebagai acuan visual** (selaras dengan dokumen 03).

---

## 3. Aturan kerja per Stage

### Awal percakapan
Saat pertama menerima file ini **jangan langsung membuat kode**. Lakukan berurutan:
1. Baca semua file.
2. Balas dengan **ringkasan pemahamanmu maksimal 10 baris** dan daftar **konflik atau pertanyaan** yang kamu temukan (jika ada).
3. Tanyakan **sistem operasi** pemilik (Windows atau Mac), lalu mulai **Stage 0**.

### Untuk setiap Stage
1. **Konfirmasi lingkup** Stage itu dalam maksimal 5 baris.
2. Kerjakan **hanya** lingkup Stage itu. Jangan mengerjakan bagian Stage lain, dan jangan merombak file Stage sebelumnya kecuali perlu (kalau perlu, sebutkan alasannya).
3. Kirim **satu zip patch** (format di bagian 4) dan **catatan patch** di chat.
4. **Berhenti dan tunggu** hasil uji pemilik. Jangan lanjut sendiri.
5. Jika pemilik melapor error: minta **pesan error lengkap, screenshot, atau log merah dari tab Actions**, lalu perbaiki dengan **patch kecil** yang hanya menyentuh yang perlu.

### Kontrol cakupan
- **Tidak ada label "segera hadir"** di UI mana pun. Tombol yang backend-nya belum ada tetap berfungsi dengan **data dummy**, supaya alurnya bisa diuji. **Satu-satunya pengecualian: tombol Scan KK** yang ada di UI tetapi sengaja tidak melakukan apa-apa sampai Stage 14.
- Untuk hal yang **tidak ada di dokumen**, atau ada di daftar **"belum diputuskan"**, **tanya pemilik dulu** sebelum membangunnya (daftar di bagian 6).
- Untuk keputusan teknis kecil yang tidak memengaruhi aturan bisnis (library ekspor, struktur folder detail, nama file, versi paket, library kompresi gambar, cara hosting font), **kamu boleh memilih**, lalu **laporkan** di catatan patch.

### Menjaga konteks
- Di akhir tiap Stage, tulis **"Ringkasan status"** di catatan patch: apa yang sudah jadi, daftar rute atau file penting, keputusan teknis yang kamu ambil, dan hal yang tertunda.
- Pemilik mungkin memulai **chat baru** di tengah jalan. Bagian 9 menjelaskan caranya. Di chat baru, **minta folder proyek terbaru** sebelum menulis kode, jangan mengandalkan ingatan.
- Jika pemilik mengubah file hasil patch-mu, minta versi terbarunya sebelum mengedit file itu.

---

## 4. Format patch dan catatan patch

### Zip patch
- Nama: `patch-stage-<nomor>-<ringkas>.zip`, contoh `patch-stage-2-ui-warga-1.zip`.
- Isi: **hanya file yang baru atau berubah**, dengan **path persis sama** dengan struktur repo, supaya pemilik tinggal menimpa.
- **Jangan menyertakan**: `node_modules`, `vendor`, `dist`, `writable/` (isinya), `.git`, `.env`, dan file besar yang tidak perlu.
- Catatan patch **ditulis di chat**, bukan di dalam zip.

### Catatan patch (selalu ada, urutan ini)
1. **Ringkasan** apa yang dibuat (bahasa sederhana, 3-6 baris).
2. **File baru dan berubah** (daftar singkat per folder).
3. **File yang harus dihapus manual** (zip tidak bisa menghapus). Tulis "tidak ada" bila kosong.
4. **Langkah SQL** bila ada: nama file, dan **urutannya: import SQL dulu, baru push kode**.
5. **Secret atau pengaturan hosting baru** yang perlu dilakukan pemilik (bila ada).
6. **Cara commit dan push** (perintah Git yang bisa disalin, dengan pesan commit yang disarankan).
7. **Checklist uji** untuk pemilik: langkah konkret, URL yang dibuka, apa yang seharusnya terlihat.
8. **Yang sengaja belum dikerjakan** di Stage ini.
9. **Keputusan teknis yang kamu ambil** (bila ada).
10. **Ringkasan status** (bagian 3).
11. **Pertanyaan untuk pemilik** (maksimal 3).

### Verifikasi sebelum mengirim
- Jika lingkungan kerjamu bisa menjalankan Node atau PHP, **jalankan `npm run build` dan `php -l` pada file PHP yang kamu ubah** sebelum mengirim, lalu laporkan hasilnya.
- Jika tidak bisa, **katakan dengan jelas bahwa patch belum diuji build**. Jangan berpura-pura sudah diuji.

---

## 5. Aturan kode

### Umum
- Pemula harus bisa membaca kodenya: **sederhana, file kecil, nama jelas, tanpa abstraksi pintar**. Komentar singkat dalam Bahasa Indonesia di bagian yang tidak jelas.
- **Satu halaman = satu komponen**. **Satu rute per aksi** (aturan navigasi dokumen 03).
- Semua teks tampilan **Bahasa Indonesia sehari-hari**. Nominal dalam rupiah (contoh `Rp 12.450.000`), tanggal format Indonesia, zona waktu `Asia/Jakarta`.
- **Uang disimpan sebagai bilangan bulat rupiah**, bukan desimal.
- **Tidak ada teks atau angka khusus RT di kode** (nama RT, perumahan, blok, nominal): semua dari pengaturan atau data. Pengecualiannya hanya **data dummy** di folder mock.
- Jangan membuat fitur besar di luar dokumen. Jangan menambah dependensi tanpa alasan; sebutkan alasan bila menambah.

### Frontend
- **Vue 3 + Vite, JavaScript (tanpa TypeScript), tanpa Pinia**, `<script setup>`, Vue Router, Tailwind CSS, `lucide-vue-next`, `vite-plugin-pwa`.
- State bersama memakai **composable** dan `ref`.
- Token desain dari dokumen 03 sebagai **CSS variable**, dipakai lewat Tailwind. Atribut tema `data-mode`.
- Komponen bersama di `shared/`. Dua aplikasi (`warga`, `pengurus`) hanya berisi halaman dan rute masing-masing.
- **Lapisan data lewat service**: halaman tidak memanggil `fetch` langsung. Tiap modul punya service dengan implementasi **mock** dan **real**, dipilih lewat satu berkas konfigurasi per modul. Dengan begitu UI bisa dihubungkan ke backend satu modul demi satu modul (Stage 10-13) tanpa mengubah halaman.
- **Pengurus: tanpa bottom sheet dan tanpa modal.** Toast, dropdown, `KeepAlive` untuk daftar, `router.replace` setelah simpan, peringatan form belum tersimpan, dan skeleton ikut dokumen 03.
- Seluruh halaman punya **kondisi loading (skeleton), kosong, dan error** yang jelas.
- Aksesibilitas dasar: label di tombol dan ikon, target sentuh minimal 44px, fokus terlihat, hormati `prefers-reduced-motion`.
- Tema dan ukuran tulisan disimpan di perangkat (`localStorage` boleh dipakai untuk preferensi seperti ini).

### Backend (mulai Stage 9)
- **CodeIgniter 4** sebagai API JSON. Format respons seragam, misalnya `{ ok, data, error }`.
- **Query builder** saja (tanpa SQL mentah dari input), **validasi semua input**.
- Perubahan database berupa **file SQL bernomor** di folder `sql/` (`001_...sql`, `002_...sql`), dibuat agar aman diimpor lewat phpMyAdmin (`utf8mb4`). **File lama tidak diubah**, hanya ditambah.
- **Role dicek di server** di setiap route. **Sisi (warga atau pengurus) dicek dari nama host**. Cookie sesi terpisah per sisi (dokumen 01).
- **Setiap aksi yang mengubah data wajib menulis `audit_log`** (siapa, kapan, aksi, objek, sebelum dan sesudah) lewat satu service bersama. Log tidak punya jalur ubah atau hapus.
- **Pembayaran dan pembatalan dalam transaksi database.** Konfirmasi permintaan memakai penguncian agar tidak ada pembayaran ganda.
- **Tugas cron harus idempoten**, ringan, dan dilindungi token header.
- **Jam server** adalah acuan semua pencatatan waktu.
- **Endpoint warga (`/api/portal/*`) tidak pernah mengirim NIK**, telepon, atau data keluarga lain.
- Jangan menulis URL, nama RT, atau rahasia langsung di kode. Semua dari `.env` atau database.

### Keamanan dan data
- **Jangan pernah meminta pemilik menempel isi `.env`, password, atau token ke chat.** Rahasia hanya disimpan di **GitHub Secrets** dan `.env` di hosting. Di repo hanya ada `.env.example`.
- **Rahasia production** (kunci enkripsi NIK, kunci push) dibuat di luar chat dengan cara yang kamu jelaskan, dan **tidak dikirim balik ke chat**. Rahasia development boleh dibuat bersama di chat.
- **Data dummy harus fiktif dan jelas palsu** (nama rekaan, NIK dummy yang jelas palsu, nomor HP dummy). Jangan memakai data warga asli.
- Jangan mencatat NIK di log atau pesan error.

---

## 6. Hal "belum diputuskan": wajib tanya sebelum membangun

Tanyakan ke pemilik **sebelum** mengerjakan bagian terkait. Satu pertanyaan jelas, tawarkan usulanmu.

| Hal | Ditanyakan di Stage |
|---|---|
| Bentuk visual card pengumuman selain yang teratas | 2 |
| Daftar warga (tab Warga di aplikasi warga): per keluarga atau per anggota | 2 |
| Cakupan search di Beranda pengurus | 4 |
| Daftar kategori kas awal | 6 |
| Kas masuk dari iuran dipecah per jenis dan Laporan menampilkan tiap iuran khusus sebagai baris sendiri (usulan dokumen 02, bagian 10) | 6 |
| Hapus pengumuman, serta edit dan hapus album galeri | 7 |
| Isi otomatis jadwal ronda: regu tetap atau bergiliran lintas minggu | 7 |
| Format username login pengurus | 9 |
| Waktu eksekusi penghitungan denda dan penguncian absensi (pemicu siang hari tanggal 1) | 12 |
| Cadangan pemicu tagihan saat akses, atau murni cron | 12 |
| Layanan baca KK (AI cloud atau OCR di browser) dan kebijakan data | 14 |
| Backup database dan kunci enkripsi | 15 |
| Pernyataan privasi untuk warga saat login pertama | 15 |

---

## 7. Struktur repo (target)

Detail akhirnya kamu rancang di Stage 0, **sesuai dokumen 01**:

```
(root repo)
  .github/workflows/     bootstrap-backend.yml, deploy-dev.yml, (nanti) deploy-prod.yml
  backend/               CodeIgniter 4 (app/, writable/, composer.json, ...)
  frontend/              package.json, konfigurasi vite (dua build), src/
    src/shared/          komponen, design system, composable, api (mock + real), mock data
    src/warga/           halaman dan rute aplikasi warga
    src/pengurus/        halaman dan rute aplikasi pengurus
  sql/                   001_*.sql, 002_*.sql, ...
  deploy/                file pendamping untuk hosting (api/index.php tipis per subdomain, .htaccess)
  docs/                  01-04 dan preview HTML (salinan untuk referensi)
  README.md
  .env.example
  .gitignore
```

Satu `package.json`, **dua build** (`dist/warga` dan `dist/pengurus`) agar workflow sederhana.

---

## 8. Data dummy (dipakai Stage 1 sampai 8)

Siapkan di `shared/mock/` satu set data dummy konsisten (sekitar 15-20 keluarga di blok AB1, AB2, AB11, AB12, termasuk rumah **22** dan **22a** di AB2). Cakupan wajib supaya semua kondisi UI bisa dites:

- **Akun mock** (login menerima PIN/password apa saja, skenario ditentukan username):
  - Warga: satu akun **ada tagihan sisa**, satu **lunas**, satu **kelebihan bayar**, satu **belum ganti PIN** (memaksa halaman ganti PIN), dan satu kondisi **akses warga belum dibuka**.
  - Pengurus: username `ketua` (melihat grup Sistem dan Kelola pengurus) dan username lain sebagai pengurus biasa (tidak melihat grup Sistem).
- Keluarga: ada yang **data belum lengkap**, **belum ganti PIN**, **"Mulai bulan depan"**, **pindah**, dan anggota yang **meninggal** atau **dikeluarkan**.
- Pembayaran: ada tagihan campuran (iuran khusus, kas, denda), pembayaran sebagian, pembayaran dibatalkan, **dua permintaan konfirmasi menunggu**, satu ditolak dengan alasan, satu dikonfirmasi.
- Kas: transaksi masuk dan keluar beberapa bulan, termasuk **kategori "Saldo awal"**, **"Pengembalian kelebihan"**, dan yang dibatalkan.
- Pengumuman: **dua yang di-pin**, beberapa bulan riwayat, satu dengan lampiran PDF, satu dengan gambar.
- Ronda: jadwal tetap, satu jadwal khusus, satu **malam ronda "malam ini"** (agar tombol absen bisa dites), absen sudah masuk, absen dibatalkan, absen manual.
- Notifikasi: campuran **belum dibaca** dan sudah dibaca, untuk warga dan pengurus.
- Aktivitas: beberapa bulan, termasuk entri **"Sistem"**, pembukaan NIK, ekspor, dan perubahan pengaturan dengan nilai sebelum dan sesudah.
- Program RT: tiga artikel dengan tag status berbeda (satu dengan tabel). Galeri: beberapa album dengan foto berbagai orientasi (gambar dummy). Struktur: ketua dan beberapa pengurus.
- **Fungsi alokasi pembayaran** (iuran khusus, lalu kas, lalu denda; bulan terlama dulu; boleh minus) ditulis sebagai fungsi murni di `shared/` agar pratinjau pembayaran di UI akurat. Server tetap menjadi sumber kebenaran di Stage 11.

---

## 9. Melanjutkan di chat baru

Jika percakapan terlalu panjang, pemilik bisa membuka chat baru dan mengirim: **file 01-04, kedua preview, folder proyek terbaru (tanpa `node_modules`, `vendor`, `dist`, `.git`, `writable`, dan `.env`), dan pesan "lanjut Stage N"**.

Di chat baru, kamu:
1. Membaca semuanya.
2. Menyebut **status terakhir** menurut folder proyek dan menyebut Stage mana yang tampaknya sudah selesai.
3. **Meminta konfirmasi pemilik**, lalu mengerjakan Stage yang diminta.

---

# DAFTAR STAGE

Aturan umum tiap Stage: ikuti bagian 3 dan 4. Uji dilakukan di **domain development**.

---

## Stage 0: Setup awal (komputer lokal, GitHub, hosting development)

**Tujuan:** semua jalur kerja berfungsi: repo, hosting dev, deploy otomatis, dan kerangka backend. **Belum ada kode aplikasi.** Kamu memandu pemilik langkah demi langkah dan menulis berkas pendukungnya.

**Tanyakan dulu (satu per satu):** sistem operasi, apakah sudah punya akun GitHub, nama domain development yang dipakai, dan apakah Git sudah terpasang.

**Panduan untuk pemilik (kamu pandu, beri perintah yang bisa disalin):**
1. **Komputer lokal:** pasang **Git** (dan opsional editor teks atau GitHub Desktop), atur nama dan email Git. Tidak perlu PHP, Node, atau Composer.
2. **GitHub:** buat repo **private**, lalu clone ke komputer.
3. **Hosting development (cPanel):**
   - Buat **dua subdomain** dev (warga dan pengurus) dengan **root folder masing-masing**, serta satu folder backend **di luar root publik mana pun**.
   - Pastikan **HTTPS aktif** di kedua subdomain.
   - Cek **versi PHP dan ekstensi** (daftar di dokumen 01, bagian 5).
   - Buat **database dev**, user, dan beri hak penuh.
   - Buat **akun FTP** dan catat alamat serta folder tujuannya.
4. **GitHub Secrets** (alamat FTP, user, password, dan folder tujuan tiap target). **Pemilik tidak boleh mengirim password ke chat**; cukup konfirmasi sudah disimpan.
5. Pastikan **GitHub Actions aktif** di repo.

**Yang kamu kirim (zip patch):**
- Struktur repo awal (bagian 7), `.gitignore`, `README.md`, `.env.example`, folder `docs/` (pemilik menyalin file 01-04 dan preview ke sini).
- `bootstrap-backend.yml`: workflow **manual** (`workflow_dispatch`) yang membuat kerangka CodeIgniter 4 ke folder `backend/` lalu meng-commit-nya ke repo (karena pemilik tidak punya Composer lokal).
- `deploy-dev.yml`: workflow yang menaruh **halaman "OK"** sederhana di kedua subdomain dev lewat FTP, untuk menguji jalur deploy.

**Uji (pemilik):**
- Push, jalankan `bootstrap-backend` dari tab Actions, lalu `git pull`: folder `backend/` terisi.
- Jalankan deploy dev: buka kedua subdomain di browser dan lihat halaman "OK" dengan HTTPS.
- Kirim struktur folder proyek (tanpa folder berat) ke AI untuk diverifikasi.

**Selesai bila:** kedua subdomain tampil "OK", kerangka backend ada di repo, dan kamu sudah memeriksa struktur proyek.

---

## Stage 1: Fondasi frontend dan design system (UI)

**Tujuan:** dua aplikasi (warga dan pengurus) berjalan di domain dev dengan cangkang, rute, tema, dan komponen dasar. Data masih dummy.

**Lingkup:**
- Proyek Vite + Vue (satu `package.json`, dua build) sesuai dokumen 01. `shared/`, `warga/`, `pengurus/`.
- **Design system dari dokumen 03** sebagai token CSS (light default, dark lewat `data-mode`, ukuran tulisan bisa diatur) dan font Plus Jakarta Sans.
- **Komponen dasar** (di `shared/`): bottom nav, sidebar desktop (pengurus), header halaman dengan tombol Kembali berlabel, baris tombol lingkaran, tombol, input, search, tag, avatar inisial, card, tile, hero, daftar berkelompok, dropdown, **toast** (muncul dari belakang bottom nav dan berhenti tepat di atasnya), skeleton, state kosong, state error.
- **Pola navigasi** pengurus: halaman aksi menyembunyikan bottom nav, `KeepAlive` untuk daftar, `router.replace` setelah simpan, peringatan form belum tersimpan.
- **Lapisan service mock** dan **login mock** (bagian 8). Penjaga rute berdasarkan peran, dan sisi (warga tidak bisa membuka rute pengurus dan sebaliknya).
- **PWA**: dua `manifest` berbeda (nama dan ikon berbeda; ikon placeholder), service worker, offline shell. Ingatkan pemilik bahwa **service worker bisa menahan versi lama di dev**, jadi sertakan cara menyegarkan atau menghapus cache.
- Halaman contoh sementara untuk tiap komponen boleh ada (`/uji` di dev) agar komponen bisa dicek.
- Perbarui `deploy-dev.yml`: **build kedua aplikasi** dan deploy ke kedua subdomain, termasuk `.htaccess` agar rute SPA tidak 404 saat refresh.

**Uji (pemilik):**
- Buka kedua subdomain di ponsel; login mock sebagai warga dan sebagai pengurus.
- Bottom nav pindah halaman, tema terang/gelap dan ukuran tulisan berfungsi, toast muncul di atas bottom nav.
- Refresh di rute dalam tidak 404. Pasang kedua PWA di ponsel: **nama dan ikonnya berbeda**.
- Tampilan desktop (≥1024px) pengurus memakai sidebar.

---

## Stage 2: UI warga I (akun, Beranda, Warga, Keuangan, Profil, Notifikasi)

**Tujuan:** bagian inti aplikasi warga, lengkap dengan semua kondisi.

**Lingkup (dokumen 02, bagian 4):**
- **Login warga** (username + PIN), halaman **wajib ganti PIN** (aturan PIN baru), dan kondisi **"Aplikasi belum dibuka"**.
- **Beranda**: kop (dari pengaturan mock), pengumuman (maksimal 3, pin di atas, tautan "Pengumuman lain"), card iuran **tiga kondisi**, grid menu 6 tile, lonceng.
- **Warga** (tab): daftar nama dan alamat (setelah pertanyaan bagian 6 dijawab).
- **Keuangan** (tab): total kas, daftar kas masuk dan keluar, tombol ke Rincian iuran.
- **Rincian iuran**: total, detail tagihan per bulan saat diketuk, riwayat pembayaran, tombol **"Saya sudah transfer"**, kartu status permintaan (menunggu, dikonfirmasi, ditolak dengan alasan).
- **Form "Saya sudah transfer"**: total, atas nama, bank pengirim, screenshot bukti (kompresi di browser; kirim ke mock).
- **Profil**: ganti PIN, tema, ukuran tulisan, aktifkan dan matikan pemberitahuan (tampilan), keluar.
- **Halaman Notifikasi**: daftar per hari, belum dibaca di-highlight, ditandai dibaca saat diketuk.

**Tanya pemilik sebelum membangun:** bentuk visual card pengumuman (selain hero), dan daftar Warga per keluarga atau per anggota.

**Uji (pemilik):**
- Login dengan tiap akun mock: tampilan iuran tiga kondisi benar, akun belum ganti PIN dipaksa mengganti, akses belum dibuka menampilkan pesan.
- Maksimal 3 klik dari Beranda ke tiap fitur. Kirim form transfer dan lihat status berubah di data dummy.
- Notifikasi belum dibaca berubah setelah diketuk.

---

## Stage 3: UI warga II (konten, ronda, bantuan)

**Tujuan:** sisa halaman aplikasi warga.

**Lingkup:**
- **Pengumuman**: riwayat dikelompokkan per bulan dengan tanggal, detail dengan lampiran (PDF atau gambar).
- **Jadwal Ronda**: card "Giliran keluargamu berikutnya", daftar malam mendatang (keluarga sendiri diwarnai), ringkasan bulan ini beserta perkiraan denda, riwayat kehadiran. **Tombol Absen ronda hanya tampil di malam tugas dan jam ronda** (dummy: ada satu akun yang malam ini bertugas).
- **Absen ronda**: izin kamera dengan teks penjelasan, **kamera langsung tanpa galeri**, **tanggal dan jam digambar permanen ke dalam foto** (satu JPEG, latar gelap semi transparan), pratinjau, Kirim, lalu langsung tercatat hadir (di mock).
- **Program RT**: daftar card dengan banner dan tag status, detail artikel (paragraf, daftar, tabel, gambar).
- **Galeri RT**: daftar album per bulan (judul, tanggal, jumlah foto), isi album bergaya **bento/masonry**, pratinjau besar, menu Download.
- **Struktur Pengurus**: tree dua level, vertikal di ponsel.
- **Bantuan**: daftar pengurus beserta jabatan, ketuk membuka WhatsApp dengan pesan awal otomatis.

**Uji (pemilik):**
- Kamera absen jalan di ponsel (butuh HTTPS), foto hasil memuat tanggal dan jam yang **menyatu dengan gambar** (coba simpan foto dan lihat).
- Tombol absen hanya muncul pada akun dan waktu yang benar.
- Grid galeri rapi untuk foto landscape, potret, dan kotak. Tree struktur terbaca di ponsel.

---

## Stage 4: UI pengurus I (Beranda, Lainnya, Profil, Notifikasi, Aktivitas)

**Tujuan:** cangkang aplikasi pengurus dan halaman navigasi utamanya.

**Lingkup (dokumen 02, bagian 5):**
- **Login pengurus** (username + password). Sebelum format username diputuskan, pakai kolom username biasa di mock.
- **Beranda**: header judul dengan **lonceng dan ikon profil**, search, card saldo kas, card **Permintaan konfirmasi** (bila ada), **aksi cepat 5 kolom** (Catat iuran, Kas masuk, Kas keluar, Laporan, Lainnya) dengan badge, jumlah KK dan warga, progres iuran, aktivitas terakhir.
- **Halaman Lainnya**: lima kelompok bergaya m-banking (Keuangan, Warga, Ronda, Konten warga, Sistem) sesuai tabel dokumen 02. **Grup Sistem hanya untuk `ketua`**.
- **Profil pengurus**: kartu identitas, pas foto, ganti password, tema dan ukuran tulisan, pemberitahuan, keluar.
- **Notifikasi** pengurus.
- **Aktivitas** (tab): daftar per hari dengan **dua dropdown Bulan dan Tahun**, ringkasan jumlah, **halaman detail** berisi nilai sebelum dan sesudah, kondisi bulan kosong.
- **Sidebar desktop** berisi tab utama dan semua menu.

**Tanya pemilik sebelum membangun:** cakupan search Beranda (usulan: nama dan blok/nomor rumah).

**Uji (pemilik):**
- Login sebagai `ketua` dan sebagai pengurus biasa: hanya ketua melihat grup Sistem.
- Semua kotak di Lainnya membuka halaman (boleh halaman kerangka bila fiturnya di Stage berikutnya, tetapi **dengan judul dan Kembali berfungsi**).
- Filter bulan dan tahun Aktivitas bekerja, pilihannya tersimpan saat kembali dari detail.
- Tampilan desktop dengan sidebar lengkap.

---

## Stage 5: UI pengurus II (tab Warga)

**Tujuan:** seluruh pengelolaan data warga.

**Lingkup (dokumen 02, tab Warga):**
- Daftar keluarga, search (tanpa NIK), **baris 4 tombol lingkaran**: Tambah keluarga, Scan KK (**tombol ada tetapi tidak melakukan apa-apa**), Ekspor, Filter. Tanpa chip dan tanpa tombol "+" di header.
- **Halaman Filter**: blok dinamis, kelengkapan data (termasuk "belum ganti PIN"), status keluarga.
- **Tambah keluarga** (3 halaman): data keluarga (blok dari daftar, nomor, akhiran), anggota satu per satu, selesai (username dan "PIN awal: 123456"). Aturan alamat unik dan tampilan pesan "Blok belum ada".
- **Detail keluarga**: anggota, NIK tersembunyi dengan ikon mata (dummy; pencatatan log ditampilkan di Aktivitas mock), baris data kurang, menu aksi sebagai pintu masuk.
- **Halaman aksi masing-masing**: edit keluarga, tambah dan edit anggota, tandai meninggal (termasuk kewajiban memilih kepala baru), keluarkan anggota, pindah keluarga (dengan peringatan tunggakan dan pemulihan), reset PIN.
- Penanda **"Mulai bulan depan"** untuk keluarga baru.
- **Ekspor** (halaman sendiri): mode per keluarga dan per warga (sel blok/nomor digabung per kelompok), kolom opsional, **PDF berkop** dan **XLSX** dibuat di browser, tombol Bagikan dan Unduh. Tanpa NIK, telepon, dan nominal.

**Uji (pemilik):**
- Tambah keluarga sampai selesai; keluarga baru muncul dengan penanda.
- Filter dan search bekerja; NIK tidak bisa dicari.
- Ekspor kedua mode menghasilkan PDF dan XLSX yang **terbuka dengan benar** (cek sel yang digabung dan urutan 22, 22a, 23). Coba tombol Bagikan di ponsel.

---

## Stage 6: UI pengurus III (tab Keuangan)

**Tujuan:** seluruh alur keuangan di sisi pengurus.

**Lingkup (dokumen 02, bagian 2 dan tab Keuangan):**
- **Tab Keuangan**: card saldo, **baris 5 tombol** (Kas masuk, Kas keluar, Iuran, Laporan, Filter), riwayat kas per tanggal, halaman detail dengan **Batalkan** (alasan wajib).
- **Form Kas masuk dan Kas keluar**, **Filter riwayat kas** (bulan, jenis, kategori; saldo tetap total; ringkasan hasil filter).
- **Halaman Iuran**: ringkasan, permintaan konfirmasi di atas, search, Filter dan Ekspor (tabel tanpa nominal), daftar keluarga dengan total dan tag status.
- **Rincian tagihan** per keluarga, dengan aksi **Catat pembayaran** (nominal, metode, tanggal, catatan, **pratinjau potongan** memakai fungsi alokasi), **Batalkan pembayaran**, **Batalkan denda** (halaman sendiri, alasan wajib).
- **Permintaan konfirmasi**: detail dan bukti (layar penuh), **Konfirmasi** (nominal boleh dikoreksi, ada pratinjau) dan **Tolak** (alasan wajib), serta pesan "sudah dikonfirmasi oleh ..." untuk kasus dua pengurus.
- **Iuran khusus**: form (nama, nominal, bulan), pratinjau "50 keluarga × Rp ...", ubah dan batalkan dengan pratinjau dampak.
- **Laporan**: pilih bulan, ringkasan, rincian per kategori, rekap iuran, daftar transaksi; ekspor PDF dan XLSX berkop RT; salin ringkasan teks.
- Warna dan label status (Lunas, Belum lunas, Menunggak) sesuai dokumen 02.

**Tanya pemilik sebelum membangun:** daftar kategori kas awal, dan keputusan memecah kas masuk iuran per jenis.

**Uji (pemilik):**
- Contoh perhitungan dokumen 02 (kas 40.000 + denda 30.000, bayar 50.000) menghasilkan sisa denda 20.000 di pratinjau.
- Bayar lebih dari total menghasilkan **kelebihan bayar**. Iuran khusus dipotong **lebih dulu** dari kas.
- Pembatalan mengembalikan tagihan; kas masuk otomatis ikut batal. Ekspor Laporan dan tabel tunggakan terbuka dengan benar.

---

## Stage 7: UI pengurus IV (konten, ronda, sistem)

**Tujuan:** sisa menu pengurus.

**Lingkup:**
- **Pengumuman**: daftar, tambah dan edit (judul, isi, **satu lampiran PDF atau gambar**, pin dengan **batas 2**, centang "kirim notifikasi lagi" saat edit).
- **Program RT**: daftar, editor artikel (paragraf, daftar, tabel, gambar; editor teks kaya), banner, tag status.
- **Galeri**: daftar album per bulan, buat album, **upload banyak foto** dengan progres satu per satu (kompresi dan thumbnail di browser), halaman **Kelola foto** (pilih beberapa lalu halaman konfirmasi hapus).
- **Struktur pengurus**: tree (data dari akun), pengaturan urutan **hanya tampil untuk ketua**.
- **Jadwal ronda**: kalender bulanan (titik di tanggal ronda, jadwal khusus berwarna lain), card "Malam ini", halaman **malam ronda** (siapa bertugas, status absen, foto, ganti keluarga, **batalkan absen**, **absenkan manual**), **Jadwal tetap**, **Jadwal khusus**, **Isi otomatis**.
- **Pengaturan warga** (semua pengurus): nominal kas dan denda dengan toggle **"Terapkan juga ke bulan ini"** beserta pratinjau dampak, serta jam ronda.
- **Pengaturan aplikasi** (ketua): nama aplikasi, ikon, nama RT, nama perumahan, logo RT, logo desa, isi kop, **daftar blok**, switch **"Akses warga"**.
- **Kelola pengurus** (ketua): daftar, **angkat pengurus dari daftar warga** (jabatan diketik, nomor HP wajib dengan kalimat penjelasan), ubah jabatan, nonaktifkan, reset password, atur tampil di Bantuan.

**Tanya pemilik sebelum membangun:** apakah pengumuman boleh dihapus (dan caranya), edit dan hapus album, serta isi otomatis ronda (regu tetap atau bergiliran).

**Uji (pemilik):**
- Pin ketiga pengumuman ditolak dengan pesan jelas. Edit pengumuman membuatnya naik ke atas.
- Upload banyak foto berjalan dengan progres. Editor Program RT menyimpan tabel dan gambar.
- Kalender ronda: ketuk tanggal membuka halaman malam. Pratinjau dampak perubahan nominal tampil.
- Pengurus biasa tidak melihat Pengaturan aplikasi dan Kelola pengurus.

---

## Stage 8: Tinjauan dan pemolesan UI

**Tujuan:** memastikan seluruh UI **sama dengan dokumen** sebelum backend dibangun.

**Lingkup:**
- Kamu menyusun **tabel audit**: setiap menu, halaman, dan aturan di dokumen 02 dan 03 melawan UI yang sudah ada (ada atau belum, rute, catatan). Perbaiki yang kurang.
- Periksa: **tidak ada bottom sheet atau modal** di pengurus, **maksimal 3 klik** di warga, toast sesuai aturan, dropdown hanya sebagai pintu masuk, **tidak ada label "segera hadir"**, tombol Scan KK ada tetapi tidak aktif.
- **Dark mode** di semua halaman, tampilan desktop pengurus, ukuran tulisan, skeleton dan state kosong dan error, aksesibilitas dasar.
- Rapikan struktur kode agar siap dihubungkan ke backend (lapisan service, nama rute, konvensi).
- Dokumentasikan **kontrak API yang dibutuhkan UI** (daftar endpoint, input, output) dalam satu berkas di `docs/`, sebagai bahan Stage 9-13.

**Uji (pemilik):** menelusuri seluruh aplikasi di ponsel dan desktop dengan checklist dari tabel audit, lalu menyetujui UI.

---

## Stage 9: Backend fondasi (database, setup, autentikasi, audit)

**Tujuan:** backend berjalan di dev dengan dua sisi, autentikasi, dan log. **Belum ada fitur bisnis.**

**Lingkup (dokumen 01 dan 02):**
- **Skema database lengkap** sebagai file SQL bernomor (semua tabel di dokumen 01, bagian 12). Pemilik mengimpornya lewat phpMyAdmin.
- Konfigurasi CI4, **format respons seragam**, penanganan error, zona waktu `Asia/Jakarta`.
- **Folder `api/` tipis** di kedua subdomain, pengenalan **sisi dari nama host**, penolakan lintas sisi.
- **Autentikasi**: login warga (username + PIN), login pengurus (password), cookie sesi terpisah, CSRF, **rate limit dan penguncian sementara**, ganti PIN dan password, aturan PIN, dan perilaku akun developer.
- **Halaman setup awal** sekali pakai (membuat ketua dan mengisi pengaturan awal), otomatis terkunci. Akun developer dibuat lewat SQL (kamu tulis SQL dan cara membuat hash).
- **Layanan audit log** (append-only, sumber `pengguna|sistem_otomatis|developer`, penanda tampil) dan layanan notifikasi dasar.
- Penjaga role dan penjaga sisi di server.
- Deploy backend ke hosting dev lewat Actions, dan panduan membuat `.env` dev di hosting.

**Tanya pemilik sebelum membangun:** format username login pengurus.

**Uji (pemilik):**
- Import SQL berhasil. Buka halaman setup, buat ketua, halaman setup lalu tertutup.
- Login pengurus di subdomain pengurus berhasil; login pengurus di subdomain warga ditolak (dan sebaliknya).
- Salah password berulang memicu penguncian sementara.

---

## Stage 10: Backend data warga, blok, dan pengaturan (menghubungkan UI)

**Lingkup:**
- Endpoint **blok, keluarga, anggota, pengangkatan pengurus, pengaturan aplikasi, pengaturan warga**, sesuai dokumen 02.
- **NIK terenkripsi** dengan **hash untuk cek duplikat**, tidak bisa dicari, endpoint warga tidak pernah mengirim NIK, **pembukaan NIK tercatat**.
- Aturan alamat unik, status meninggal/pindah/keluarkan, kepala keluarga, pembuatan akun otomatis (PIN awal 123456), reset PIN, akun keluarga baru "mulai bulan depan".
- Pengangkatan pengurus dengan jabatan dan nomor HP, nonaktif otomatis bila anggotanya pindah, meninggal, atau dikeluarkan.
- Upload logo dan pas foto (aman, di luar folder publik).
- **Hubungkan modul UI** (Warga, Pengaturan, Kelola pengurus, Profil, login, Struktur, Bantuan, Aktivitas) dari mock ke real. Mock tetap ada sebagai cadangan di berkas konfigurasi.
- Aktivitas dan notifikasi dasar untuk aksi-aksi ini.

**Uji (pemilik):** input beberapa keluarga sungguhan versi percobaan di dev; cek NIK tidak muncul di sisi warga; cek log Aktivitas; angkat pengurus lalu login dengan akun itu; cek akun warga yang baru dibuat memaksa ganti PIN.

---

## Stage 11: Backend keuangan (dan tes alokasi)

**Lingkup:**
- **Tagihan**, **perhitungan total dan alokasi** (iuran khusus, lalu kas, lalu denda; bulan terlama dulu; boleh minus) di server, dengan **tes otomatis yang dijalankan GitHub Actions** (termasuk contoh dokumen 02, kelebihan bayar, pembatalan, perubahan nominal, iuran khusus).
- **Pembayaran jalur 1** (input pengurus) dan **jalur 2** (permintaan warga, upload bukti, konfirmasi dan tolak, penguncian untuk dua pengurus), pembatalan pembayaran dan denda, dalam **transaksi database**.
- **Kas** (masuk dan keluar, otomatis dari iuran, pembatalan), **iuran khusus**, **perubahan nominal dengan toggle bulan ini** dan pratinjau dampak, **Laporan** (data), dan data Iuran (ringkasan, tunggakan, status).
- Bukti transfer disimpan aman dan hanya bisa dibuka pengurus dan keluarga pengirimnya.
- Hubungkan modul UI Keuangan warga dan pengurus ke backend.

**Tanya pemilik bila belum terjawab:** kategori kas awal, pemecahan kas masuk per jenis.

**Uji (pemilik):** ulangi skenario Stage 6 dengan data nyata di dev; bayar sebagian, bayar lebih, batalkan; kirim permintaan transfer dari akun warga dan konfirmasi dari akun pengurus; cek hasil tes di tab Actions.

---

## Stage 12: Notifikasi, push, dan cron

**Lingkup (dokumen 01, bagian 7 dan 8):**
- **Tabel notifikasi**, halaman Notifikasi (baca dan tandai), badge, aturan penerima (warga dan pengurus, pelaku tidak menerima, penggabungan aksi beruntun).
- **Push** dengan `web-push`: kunci VAPID, langganan per perangkat, tombol aktifkan, pengiriman setelah respons, kelompok kecil serentak, percobaan ulang.
- **Alamat tugas cron** dengan token header, **tiga job** (harian, sore, tiap 5 menit) dan **idempotensi** (`tugas_log`).
- Tugas harian: tagihan bulan baru, pembuatan akun dan tagihan keluarga baru, pembuatan malam ronda, pembersihan file kedaluwarsa, notifikasi lama.
- Panduan **memasang tiga job di cron-job.org** (jadwal WIB, header token, peringatan gagal).
- Notifikasi untuk semua kejadian di dokumen 02, bagian 6, dan notifikasi pengurus dari tiap entri Aktivitas.

**Tanya pemilik sebelum membangun:** waktu eksekusi penghitungan denda dan penguncian absensi, serta cadangan pemicu tagihan saat akses.

**Uji (pemilik):** aktifkan push di ponsel (Android langsung, iPhone setelah dipasang ke layar utama), picu kejadian dan lihat push masuk; panggil job manual dua kali dan pastikan tidak ada tagihan ganda; cek job di cron-job.org berjalan.

---

## Stage 13: Backend konten dan ronda

**Lingkup:**
- **Pengumuman** (pin maksimal 2, lampiran satu file, edit naik ke atas, push), **Program RT** (isi HTML dibersihkan di server, banner), **Galeri** (album, upload satu per satu, thumbnail, hapus, unduh), **Struktur** (dari akun, urutan).
- **Ronda**: jadwal tetap dan khusus, pembuatan malam ronda, pengeditan malam, **absen warga dengan foto** (jam server, jendela jam ronda dengan toleransi, simpan foto), **batalkan absen** dan **absen manual**, hitung "tidak hadir", **denda bulan berikutnya**, **penguncian absensi**, batalkan denda manual.
- File dilayani hanya lewat endpoint yang mengecek login dan role.
- Hubungkan semua modul UI konten dan ronda.

**Tanya pemilik bila belum terjawab:** hapus pengumuman dan album, isi otomatis ronda.

**Uji (pemilik):** buat pengumuman dan lihat push masuk; upload album di dev; jalankan satu siklus ronda penuh di dev (jadwal, absen dengan foto, absen dibatalkan, absen manual, denda terbit bulan berikutnya lewat job, absensi terkunci).

---

## Stage 14: Scan KK (fitur terakhir)

**Lingkup (dokumen 02, bagian Scan KK):**
- Hubungkan tombol **Scan KK** di tab Warga dan di Lainnya: ambil foto atau pilih foto KK, baca isinya, **isi otomatis form Tambah keluarga**, pengurus memeriksa lalu menyimpan.
- Hanya alat bantu pengurus; **warga tidak pernah mengunggah KK**. Foto KK **tidak disimpan** setelah dibaca (atau segera dihapus).

**Wajib tanya pemilik dulu:** pilihan layanan baca KK (AI cloud berbayar, atau OCR di browser yang lebih privat tetapi kurang akurat), dan kebijakan data (apakah boleh mengirim foto KK ke layanan luar). Jelaskan risiko dan biaya kedua pilihan dengan bahasa sederhana, dan **jangan memilih sendiri**.

**Uji (pemilik):** scan beberapa KK contoh (versi fiktif atau milik sendiri), periksa hasil isian, pastikan foto tidak tersisa di server.

---

## Stage 15: Pengerasan, backup, privasi, dan uji menyeluruh

**Lingkup:**
- Security header (CSP, HSTS), peninjauan hak akses (setiap endpoint dicek terhadap tabel role dokumen 02), peninjauan agar NIK tidak bocor di API warga, log, dan ekspor, peninjauan rate limit.
- **Backup**: rancang bersama pemilik (jadwal ekspor database, tempat penyimpanan di luar hosting, **cadangan kunci enkripsi NIK**, dan cara memulihkan). Kamu tanya dulu pilihan pemilik.
- **Pernyataan privasi** untuk warga saat login pertama (isi dan cara tampil): tanyakan pemilik.
- Uji beban ringan (sekitar 50 keluarga), uji **dua pengurus bersamaan** pada permintaan konfirmasi, uji batas waktu eksekusi saat upload dan push.
- **Skrip uji akhir**: daftar skenario dari awal sampai akhir yang dijalankan pemilik di dev (tagihan, pembayaran, ronda, notifikasi, ekspor).
- Perbaikan bug yang ditemukan.

**Uji (pemilik):** menjalankan skenario akhir dan melaporkan hasilnya.

---

## Stage 16: Production dan peluncuran

**Tujuan:** menyiapkan RT yang sebenarnya, sesuai rencana peluncuran di dokumen 02, bagian 7.

**Lingkup:**
- **Lingkungan production**: dua subdomain production, database production, `.env` production (**kunci enkripsi dan kunci push baru, dibuat di luar chat**), `deploy-prod.yml` dengan Secrets production, pemicu hanya dari branch utama.
- **Tiga job cron production**.
- **Halaman setup awal** di production: ketua, pengaturan (nama RT, perumahan, logo, blok, nominal, jam ronda).
- **Akun developer** production (password acak panjang).
- **Panduan hari-H**, mengikuti dokumen 02: urutan persiapan 15 hari (pengaturan, pengurus, input keluarga, jadwal ronda, uji cron), tanggal 30-31 input **Saldo awal** lewat Kas masuk, tanggal 1 nyalakan **"Akses warga"**, dan sosialisasi door-to-door dengan PIN awal `123456`.
- Panduan **rollback** sederhana (cara kembali ke versi sebelumnya) dan cara memperbarui aplikasi setelah live (patch, SQL dulu baru push).
- Daftar periksa akhir: cron berjalan, push berjalan, backup terjadwal, kunci enkripsi tersimpan di dua tempat.

**Uji (pemilik):** latihan lengkap di production **sebelum** data asli dimasukkan, lalu hapus data latihan lewat langkah yang kamu jelaskan.
