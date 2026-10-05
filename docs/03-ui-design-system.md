# 03. UI dan Design System

Dokumen ini membahas **tampilan, komponen, dan aturan navigasi**. Fitur dan isi menu ada di `02-fitur-dan-menu.md`. Teknologi ada di `01-stack-dan-arsitektur.md`.

Token dan komponen dasar berasal dari spesifikasi sebelumnya. Aturan navigasi pengurus, toast, dropdown, dan baris tombol lingkaran adalah hasil diskusi terbaru.

---

## 1. Prinsip

- Clean dan simpel, terasa seperti aplikasi mobile yang dikenal (YouTube, WhatsApp, Netflix, m-banking).
- Hijau hanya untuk highlight dan state aktif. Pemisah memakai **gap**, bukan garis.
- **Tema default light**, dark lewat toggle di Profil. Atribut tema memakai `data-mode` (bukan `data-theme`).
- **Tidak ada label "segera hadir"** di UI mana pun.
- **Pengurus**: boleh lebih padat, aksi banyak dan rumit. **Warga**: sederhana, usia pengguna beragam.
- Dua aplikasi (warga dan pengurus) memakai design system yang sama dari folder `shared/`.

---

## 2. Token warna

| Token | Light (default) | Dark |
|---|---|---|
| `--bg` | `#FFFFFF` | `#111314` |
| `--card` | `#FFFFFF` | `#1A1D1F` |
| `--card2` | `#E9EDEA` | `#25292C` |
| `--text` | `#101614` | `#FFFFFF` |
| `--mut` | `rgba(16,22,20,.6)` | `rgba(255,255,255,.62)` |
| `--g` (isian, teks putih di atasnya) | `#0A8F44` | `#10A24F` |
| `--gh` (highlight/aktif/fokus) | `#0A8F44` | `#3DDC84` |
| `--line` (border card) | `rgba(0,0,0,.09)` | transparan (pengurus) / `rgba(255,255,255,.1)` (warga) |
| `--search` | `#F1F3F2` | `#1A1D1F` |
| `--ok` (tag lunas) | `rgba(10,143,68,.14)` | `rgba(61,220,132,.22)` |
| tag belum | kuning pastel `rgba(214,150,30,.16)` | `rgba(232,190,110,.2)` |
| `--gd` / `--gm` (pill nav aktif / ikonnya) | `#CDEBD8` / `#0B6B34` | `#12392A` / `#D5F7E1` |
| `--sh` (shadow card) | `0 1px 2px rgba(16,22,20,.04), 0 4px 12px rgba(16,22,20,.04)` | `0 2px 8px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.03)` |

- **Hero (card saldo kas dan pengumuman):**
  - Light: `radial-gradient(110% 100% at 100% 0%, rgba(255,255,255,.28), transparent 55%), linear-gradient(145deg,#22B863,#0F9D4E 55%,#0B8442)`, teks putih.
  - Dark: `radial-gradient(120% 110% at 100% 0%, rgba(61,220,132,.26), transparent 55%), linear-gradient(150deg,#134230,#0D211A 55%,#14181A)`.
  - Lingkaran dekoratif samar di pojok.
- Hijau tidak dipakai untuk semua ikon, card, atau teks. Teks kop dan judul biasa berwarna `--text`.
- Warna aksen lain hanya **pastel semi transparan** (alpha 18% light, 24% dark).
- **Badge notifikasi dan belum dibaca**: latar hijau pastel dan titik kecil.

### Warna ikon menu warga
Latar pastel per menu, ikon sewarna tetapi lebih tua (light) atau lebih terang (dark):

| Menu | Warna |
|---|---|
| Pengumuman / Info | kuning `#F59E0B` |
| Ronda | biru `#3B82F6` |
| Program RT | ungu `#A855F7` |
| Galeri | pink `#EC4899` |
| Struktur | indigo `#6366F1` |
| Bantuan | hijau WhatsApp `#22C55E` |
| Keuangan | hijau `#10B981` |
| Warga | cyan `#06B6D4` |
| Profil | tosca `#14B8A6` |

### Warna kelompok di halaman Lainnya (pengurus)
Satu warna ikon per kelompok agar mudah dikenali: **Keuangan hijau, Warga cyan, Ronda biru, Konten ungu, Sistem abu-abu**.

---

## 3. Bentuk, spasi, tipografi

- **Radius**: small **12px** (field, card kecil), regular **20px** (card), max **9999px** (search, tag, tombol, chip, avatar, ikon bulat). Radius dalam lebih kecil dari luar.
- Border tipis semi transparan di light mode; shadow halus pada card yang bisa diklik. Tanpa blur.
- **Gap**: item list 8px, antarbagian 16-22px. **Padding**: card 18px, halaman 16px.
- **Font Plus Jakarta Sans**: h1 24/800, h2 17/700, angka besar 32/800, isi 15/500, kecil 13/500, tag 12/700, label nav 12 (aktif 700). Sentence case.
- **Target sentuh** minimal 44px. Tombol pill tinggi minimal 44px. Ikon bulat menu 44px.
- Hormati safe-area dan `viewport-fit=cover`.
- **Gerak** hanya opacity dan transform, hormati `prefers-reduced-motion`.
- Ukuran tulisan bisa diatur pengguna di Profil (warga dan pengurus).

---

## 4. Komponen

### Bottom nav (kedua aplikasi)
- **4 kolom** sama lebar tanpa jarak di tepi. Ikon 20px, label 12px.
- Aktif: **pill memanjang 60×30 hanya di ikon** (`--gd`, ikon `--gm`); label hanya menjadi tebal.
- Latar sama dengan halaman dan **solid** (bukan transparan), tanpa garis. Latar solid diperlukan agar toast bisa muncul dari belakangnya.
- Warga: Beranda, Warga, Keuangan, Profil. Pengurus: Beranda, Warga, Keuangan, Aktivitas.
- Desktop pengurus (≥1024px): sidebar kiri yang menampilkan tab utama dan semua menu; item aktif berlatar pill penuh.

### Header
- Aplikasi warga: lonceng notifikasi.
- Aplikasi pengurus: **lonceng** dan **ikon profil** (menggantikan titik tiga).
- Halaman dalam: tombol **Kembali berlabel** dan judul.

### Baris tombol lingkaran (quick action)
Pola utama aplikasi pengurus (Beranda, tab Warga, tab Keuangan):
- Lingkaran **48px** dengan ikon dan label di bawahnya (maksimal dua baris, rata tengah). Lima kolom di layar sekitar 360px tidak muat dengan 56px; 48px masih di atas batas sentuh 44px.
- **Beranda: 5 kolom** (Catat iuran, Kas masuk, Kas keluar, Laporan, Lainnya). **Tab Warga: 4 kolom** (Tambah keluarga, Scan KK, Ekspor, Filter). **Tab Keuangan: 5 kolom** (Kas masuk, Kas keluar, Iuran, Laporan, Filter).
- Tombol **Lainnya** memakai ikon grid (empat kotak) berwarna netral agar beda dengan aksi lain.
- **Badge angka** di tombol bila ada yang perlu perhatian (permintaan menunggu, filter aktif, absen malam ini).
- **Tidak memakai chip dan tidak memakai pill tab**: konten utama langsung tampil, aksi lewat tombol lingkaran.

### Halaman Lainnya
Grid **4 kolom**, ikon dan label, dikelompokkan dengan judul kecil per kelompok (gaya m-banking). Tanpa bottom nav.

### Search
Pill, latar `--search`, fokus outline `--gh`.

### Input dan tombol
- Input: latar terisi, tanpa border, fokus garis hijau tipis.
- Tombol pill: utama `--g` dengan teks putih; sekunder `--card2`; bahaya merah pastel.
- Pilihan isian form (metode, hubungan, kategori) di ponsel memakai **pilihan bawaan perangkat**.

### Tag dan status
Pill, teks `--text`, latar pastel. Contoh: Lunas (`--ok`), Belum lunas (kuning pastel), Menunggak, status Program RT (Direncanakan, Berjalan, Selesai), status permintaan (Menunggu, Dikonfirmasi, Ditolak).

### Card yang bisa diklik
Shadow halus, chevron berwarna, efek tekan (skala 0,98).

### Daftar
- Avatar inisial untuk keluarga dan pengurus.
- Dot kecil tanpa label untuk "data belum lengkap".
- Daftar dikelompokkan (per tanggal, per hari, per bulan) dengan judul kelompok.
- Loading memakai **skeleton**. Kosong: ajak melakukan aksi. Error: jelaskan cara memperbaiki.

### Kop (warga)
Seperti kop surat: logo desa kiri, logo kepengurusan kanan, tengah nama RT dan nama perumahan, teks hitam, garis tipis 1px di bawah. Halaman dalam memakai kop ringkas. Kop yang sama dipakai di PDF ekspor dan laporan.

### Kartu pengumuman
Maksimal 3 di Beranda warga: yang di-pin di atas, lalu terbaru, dengan gaya hero untuk yang teratas (detail visual ditentukan saat build). Tiap card di riwayat memuat tanggal.

### Galeri
Album: card dengan judul, tanggal, jumlah foto. Isi album: **grid bergaya Pinterest/bento**, ukuran bebas (landscape, potret, kotak) tersusun otomatis. Ketuk foto membuka **pratinjau besar** dengan menu Download.

### Struktur pengurus
**Tree dua level**: Ketua di puncak, pengurus sejajar di bawahnya. Tiap node: pas foto, nama, jabatan. Ponsel: vertikal bertingkat dengan garis penghubung. Desktop: melebar.

### Program RT
Card dengan banner (di atas atau sebagai latar), judul, dan tag status. Detail seperti artikel (paragraf, daftar, tabel, gambar).

### Kamera absen
Kamera langsung tanpa pilihan galeri. Overlay tanggal dan jam di pojok gambar dengan **latar gelap semi transparan**. Layar pratinjau berisi tombol Kirim dan ulangi foto.

---

## 5. Aturan navigasi dan tindakan

### Aplikasi pengurus (ketua dan pengurus)
Karena aksinya banyak dan rumit:
- **Semua aksi, sesederhana apa pun, punya halaman sendiri** dengan navigasi SPA. **Tidak ada bottom sheet atau modal.** Contoh rute:
  - `/warga` → `/warga/12` → `/warga/12/edit`
  - `/keuangan/iuran` → `/keuangan/iuran/12` → `/keuangan/iuran/12/bayar` → pratinjau
  - `/keuangan/iuran/permintaan/5` → `/konfirmasi` atau `/tolak`
  - `/keuangan/pembayaran/88/batalkan`
- Pembatalan juga halaman sendiri berisi ringkasan, kolom alasan, dan tombol Batalkan.
- Foto bukti dan foto absen dibuka sebagai halaman layar penuh.
- **Halaman aksi dan form menyembunyikan bottom nav** (seperti WhatsApp). Di desktop, sidebar tetap tampil.
- **Setelah simpan**, kembali ke halaman asal dengan `router.replace` (agar tombol back tidak membuka form yang sudah terkirim), lalu tampil toast.
- **Daftar menyimpan posisi scroll dan filter** saat kembali dari detail (`KeepAlive`).
- **Alur multi-langkah** (nominal lalu pratinjau lalu simpan) menyimpan isian di state bersama, bukan di URL.
- **Form yang belum disimpan** memakai peringatan bawaan browser saat ditinggalkan, tanpa dialog buatan sendiri.
- Setiap halaman punya URL sendiri sehingga **push notifikasi membuka halaman yang tepat**.
- Gestur kembali Android/iPhone dan tombol back browser berjalan natural.

### Aplikasi warga
- Alur sederhana, **maksimal 3 level** dari Beranda ke fitur apa pun.
- Tombol "Kembali" berlabel di halaman dalam.
- Form "Saya sudah transfer" berupa halaman biasa.
- Teks, tombol, dan ikon memakai ukuran design system yang sama; label teks selalu di samping ikon; tanpa gerakan tersembunyi (geser, tekan lama); bahasa sehari-hari.

---

## 6. Toast

- Muncul dari **belakang bottom nav**, naik, lalu berhenti **tepat di atas bottom nav** (tidak menutupnya).
- Pada halaman **tanpa bottom nav** (form dan aksi), toast muncul di bawah layar dengan jarak aman dari tepi (safe-area).
- Hanya **satu toast** sekali tampil, hilang sendiri sekitar 3 detik, bisa ditutup dengan ketukan, dan tidak menutup tombol penting. Tidak terlalu mengganggu.
- Dipakai hanya untuk **umpan balik singkat** ("Pembayaran tersimpan"). **Error form tampil di halaman**, dekat kolom yang salah, bukan di toast.
- Dengan `prefers-reduced-motion`, toast muncul dengan fade saja.

---

## 7. Dropdown

- **Boleh** untuk menu navigasi, pilihan filter (bulan, tahun, blok), dan pilihan isian form, karena bukan aksi penting.
- Dropdown boleh menjadi **pintu masuk** ke aksi, tetapi tidak menjalankan aksi itu. Contoh: menu aksi di detail keluarga berisi Edit, Tandai meninggal, Keluarkan, Pindah, Reset PIN, dan tiap pilihan membuka **halaman sendiri**.
- Gaya: latar putih (light) atau `#25292C` (dark), radius 20px, border tipis di light.
- Filter Aktivitas memakai dua dropdown (Bulan dan Tahun) langsung di halaman.

---

## 8. Terasa native

- Bottom nav, skeleton loading, safe-area, PWA bisa dipasang (dua aplikasi dengan nama dan ikon berbeda), offline shell, cache data terakhir saat sinyal jelek.
- Gerak halus (opacity dan transform), efek tekan pada card.
- Push notification dan badge angka.

---

## 9. Belum diputuskan

Bentuk visual detail untuk kartu pengumuman teratas (hero atau card biasa), ilustrasi pada kondisi kosong, dan ukuran font tiap tingkat "ukuran tulisan" ditentukan saat tahap build.
