# 02. Fitur dan Menu

Dokumen ini membahas **fitur, menu, halaman, dan aturan bisnis** untuk semua level. Stack dan arsitektur ada di `01-stack-dan-arsitektur.md`. Tampilan dan komponen ada di `03-ui-design-system.md`.

**Cakupan rilis pertama = semua fitur di dokumen ini.** Aplikasi baru dipublikasikan setelah semuanya selesai, jadi **tidak ada label "segera hadir"** di UI. Pengerjaannya bertahap (bagian 14).

---

## 1. Gambaran dan peran

- Aplikasi manajemen warga untuk satu RT dengan dua aplikasi PWA: **aplikasi warga** dan **aplikasi pengurus**.
- **Satu keluarga (satu rumah) = satu akun warga.**
- **Pengurus dan ketua adalah individu** yang ditautkan ke data anggota warga. Mereka tetap punya akun warga (keluarganya) di aplikasi warga. Dua akun, dua aplikasi, dua login terpisah.
- **Tanpa payment gateway.** Warga membayar tunai atau transfer ke pengurus, lalu pembayaran dikonfirmasi di aplikasi.
- **Data warga hanya diisi oleh pengurus.** Warga tidak mengisi data apa pun; satu-satunya form milik warga adalah permintaan konfirmasi pembayaran ("Saya sudah transfer").

### Tiga level akses

| Level | Cakupan |
|---|---|
| **Ketua** | Semua kemampuan pengurus, ditambah **Pengaturan aplikasi** dan **Kelola pengurus** |
| **Pengurus** | Semua yang lain di sisi pengurus: data warga (termasuk NIK), pembayaran, kas, laporan, iuran khusus, ronda, konten warga, Pengaturan warga, Aktivitas |
| **Warga** | Hanya sisi warga; membaca, kecuali satu aksi (Saya sudah transfer) dan absen ronda |

- **Jabatan** (Bendahara, Sekretaris, dan seterusnya) adalah **label teks bebas** yang diketik ketua. Jabatan tidak memengaruhi hak akses.
- Hierarki hanya dua: **Ketua** dan **Pengurus** (setara satu sama lain, masing-masing punya jabatan).
- Pembatasan dicek **di server**, bukan hanya disembunyikan di menu.

### Akun developer
- Role `ketua` dengan penanda `is_developer`, setara ketua.
- **Disembunyikan**: tidak tampil di Kelola pengurus, tidak bisa dinonaktifkan dari aplikasi, tidak tampil di Bantuan, tidak punya jabatan, tidak menerima notifikasi pengurus.
- Dipakai **hanya untuk pemeliharaan** (misalnya reset password). Testing dilakukan di lingkungan development.
- Aksi yang **mengubah data** tampil di Aktivitas atas nama **"Sistem"** dan ikut dikirim sebagai push ke pengurus. Contoh: "Sistem mereset password Budi".
- Aksi yang **hanya melihat** (termasuk membuka NIK) **tidak tampil** di aplikasi. Di database tetap tercatat lengkap dengan penanda `developer`, jadi ketua bisa memeriksanya lewat phpMyAdmin.
- Ketua dan pengurus tahu akun ini ada. Ketua punya akses dan pengetahuan tentang phpMyAdmin.
- Password akun ini harus acak dan panjang.

---

## 2. Aturan inti: tagihan dan pembayaran

### Jenis tagihan
1. **Kas** (iuran pokok bulanan): nominal diatur di Pengaturan warga, sama untuk semua keluarga.
2. **Denda ronda**: Rp 10.000 per ketidakhadiran ronda (nominal diatur di Pengaturan warga).
3. **Iuran khusus**: iuran insidental (contoh: 17 Agustus, kerja bakti), **rata untuk semua keluarga aktif**, hanya untuk bulan yang dipilih.

Tidak ada keluarga yang dibebaskan atau bernominal berbeda. Tagihan menempel ke **keluarga**, bukan alamat, jadi rumah kosong tidak jadi masalah.

### Total tagihan
> total tagihan = jumlah semua tagihan − jumlah semua pembayaran (yang tidak dibatalkan)

- Hasil **positif** = yang harus dibayar.
- Hasil **minus** = **kelebihan bayar**, ditampilkan sebagai "Kelebihan bayar Rp X". Kelebihan otomatis memotong tagihan bulan berikutnya begitu terbit.
- Warga hanya melihat **satu angka total**. Rincian per tagihan baru tampil bila warga membuka detail.

### Urutan potong (prioritas)
1. **Iuran khusus**
2. **Kas**
3. **Denda ronda**

Di dalam tiap jenis, **bulan terlama didahulukan**.
- Rincian per tagihan **dihitung ulang tiap kali dibuka**, jadi komposisi boleh bergeser (misalnya kas lunas bisa muncul sisa lagi saat ada iuran khusus). Ini disepakati karena warga hanya melihat total.
- Pembayaran boleh **sebagian (mencicil)** dan boleh **lebih dari total**.

**Contoh:** tagihan kas Rp 40.000 + denda Rp 30.000 = Rp 70.000. Warga bayar Rp 50.000: kas lunas, denda terpotong Rp 10.000, sisa denda Rp 20.000. Card iuran warga menampilkan Rp 20.000.

### Status iuran keluarga
- **Lunas**: total nol atau minus
- **Belum lunas**: ada sisa
- **Menunggak**: ada sisa dari bulan sebelum bulan berjalan

### Perubahan nominal (Pengaturan warga)
- Default: perubahan nominal kas atau denda **hanya berlaku untuk tagihan baru** (bulan depan).
- Ada toggle **"Terapkan juga ke bulan ini"**. Bila dinyalakan, hanya tagihan **bulan berjalan** yang berubah (bulan lalu tidak disentuh).
- Sebelum disimpan tampil **pratinjau dampak**, misalnya "42 keluarga terdampak, 5 di antaranya jadi kelebihan bayar".
- Aktivitas mencatat nominal lama, nominal baru, pelaku, dan apakah toggle dinyalakan.
- Denda yang dibuat memakai nominal **saat denda itu dibuat**.

### Dua jalur pembayaran
Pembayaran bisa dicatat atau dikonfirmasi oleh **semua pengurus dan ketua**. Selalu tersimpan **siapa dan kapan**.

**Jalur 1: pengurus input langsung** (contoh: bayar tunai)
1. Pilih keluarga, lihat total tagihan dan rincian.
2. Isi nominal diterima, metode (tunai atau transfer), tanggal, catatan.
3. Muncul **pratinjau potongan** (tagihan mana terpotong berapa).
4. Simpan.

**Jalur 2: warga mengajukan permintaan konfirmasi** (contoh: transfer)
1. Warga mengisi form **"Saya sudah transfer"**: total transfer, atas nama, bank pengirim, screenshot bukti.
2. Permintaan berstatus **menunggu**. Badge pengurus bertambah, push dikirim ke pengurus.
3. **Siapa saja pengurus** membuka permintaan, memeriksa bukti, lalu:
   - **Konfirmasi**: nominal sudah terisi dari permintaan tetapi **boleh dikoreksi**; tampil pratinjau potongan; setelah simpan, pembayaran (metode transfer) dibuat dan tercatat **siapa dan kapan** mengonfirmasi.
   - **Tolak**: wajib mengisi alasan singkat. Tidak ada tagihan yang berubah.
4. Warga melihat hasilnya (dikonfirmasi atau ditolak beserta alasan) dan boleh mengajukan lagi setelah ditolak.
5. Maksimal **satu permintaan berstatus menunggu per keluarga** (mencegah ganda).
6. Bila dua pengurus membuka permintaan yang sama, **yang menyimpan lebih dulu yang berlaku**. Yang kedua mendapat pesan "Sudah dikonfirmasi oleh [nama] pada [waktu]". Tidak ada pembayaran ganda.
7. Pengurus yang memproses permintaan dari keluarganya sendiri tetap tercatat di Aktivitas (tidak diblokir).

### Pembatalan
- **Batalkan pembayaran**: halaman sendiri berisi ringkasan, **alasan wajib**, lalu konfirmasi. Tagihan kembali ke kondisi sebelumnya. Kas masuk otomatisnya ikut batal. Warga diberi tahu. Semua pengurus dan ketua boleh.
- **Batalkan denda**: alasan wajib, warga diberi tahu, tercatat di Aktivitas. Semua pengurus dan ketua boleh.
- **Batalkan transaksi kas**: alasan wajib. Transaksi dibatalkan (tercatat), **bukan dihapus**.

### Kas masuk dari iuran
- Saat pembayaran iuran dicatat atau dikonfirmasi, **kas masuk dibuat otomatis**.
- Kas masuk otomatis **tidak bisa diubah manual**; hanya ikut batal bila pembayarannya dibatalkan.
- Kas masuk lain (donasi, sewa balai, saldo awal) dicatat manual lewat Kas masuk.

### Pengembalian kelebihan bayar
Dicatat sebagai **kas keluar** dengan kategori **"Pengembalian kelebihan"**, dengan keluarga terkait ikut tersimpan, supaya saldo kas tetap cocok.

---

## 3. Siklus bulanan (otomatis lewat cron)

Pada **tanggal 1**:
1. Tagihan kas bulan baru terbit untuk semua keluarga aktif.
2. **Malam ronda bulan itu dibuat** dari jadwal tetap dan jadwal khusus.
3. **Keluarga yang terdaftar bulan lalu** mendapat akun (username dan PIN `123456`), tagihan pertama, dan masuk jadwal ronda.
4. Warga mendapat satu notifikasi "Tagihan bulan baru terbit".
5. Setelah ronda terakhir bulan lalu selesai: **denda ronda bulan lalu** dihitung (tiap keluarga bertugas yang tidak punya catatan absen mendapat denda), masuk ke tagihan bulan baru, dan **absensi bulan lalu terkunci**. Warga mendapat **satu notifikasi** per keluarga, misalnya "Denda ronda bulan lalu: 2 kali, Rp 20.000".

**Keluarga baru di tengah bulan:** data langsung tersimpan dan tampil di daftar Warga serta terhitung di jumlah KK, tetapi **akun, tagihan, dan jadwal ronda baru dibuat tanggal 1 bulan berikutnya**. Di daftar tampil penanda halus "Mulai bulan depan". Keluarga baru **tidak ikut** iuran khusus yang sudah terbit dan tidak muncul di tunggakan sebelum itu.

---

## 4. Aplikasi warga

### Navigasi
Bottom nav **4 tab**: **Beranda, Warga, Keuangan, Profil**. Di header: **ikon lonceng** (notifikasi).

### Beranda (dari atas)
1. **Kop** seperti kop surat: logo desa di kiri, logo kepengurusan di kanan, di tengah nama RT dan di bawahnya nama perumahan, garis tipis di bawah. Isinya dari Pengaturan aplikasi. Teks awal dari spesifikasi sebelumnya: "Rukun Warga 002" dan "Perum. Pesona Gading Cibitung 2". Tanpa nama desa. Halaman dalam memakai kop ringkas.
2. **Pengumuman**: maksimal **3 card**. Yang di-pin tampil paling atas, lalu yang terbaru di bawahnya. Tautan **"Pengumuman lain"** membuka riwayat.
3. **Card iuran**:
   - Ada tagihan: label "Iuran yang belum dibayar" dan nominal total
   - Lunas: "Semua iuran sudah lunas" (bukan "Rp 0")
   - Minus: "Kelebihan bayar Rp X"
   - Teks "Ketuk untuk melihat rincian" dan chevron; membuka Rincian iuran
4. **Grid menu 2 kolom**: Pengumuman, Jadwal Ronda, Program RT, Galeri RT, Struktur Pengurus, Bantuan. (Menu yang sudah ada di bottom nav tidak diulang.)

### Warga (tab)
Daftar **nama dan alamat saja**. Tanpa NIK, telepon, dan iuran. Keluarga pindah dan anggota yang sudah tidak aktif tidak tampil.

### Keuangan (tab)
- **Total kas RT** dan daftar **kas masuk dan keluar** (transparansi).
- Tombol/card menuju **Rincian iuran** milik keluarga sendiri.

### Rincian iuran
- **Total tagihan** (atau kelebihan bayar).
- Ketuk untuk melihat **detail tagihan per bulan** (kas, iuran khusus, denda ronda per tanggal) dengan sisa tiap item.
- **Riwayat pembayaran**.
- Tombol besar **"Saya sudah transfer"**.
- Bila ada permintaan berstatus menunggu: kartu status "Menunggu konfirmasi pengurus". Setelah diproses: status **Dikonfirmasi** (tanggal) atau **Ditolak** (alasan).
- Jalur: Beranda, card iuran, Rincian iuran, tombol = 3 klik.

### Form "Saya sudah transfer" (halaman sendiri)
Total transfer (angka, tanpa batas atas), atas nama, bank pengirim, screenshot bukti (kompres di browser).

### Jadwal Ronda (warga)
- **Card "Giliran keluargamu berikutnya"**: tanggal, jam, status. Tombol **Absen ronda** muncul di sini hanya pada malam tugas dan jam ronda.
- Daftar **malam mendatang** per tanggal beserta keluarga yang bertugas; keluarga sendiri diberi warna.
- **Ringkasan bulan ini**: berapa kali hadir, tidak hadir n kali, dan **perkiraan denda** (denda sendiri terbit bulan berikutnya).
- **Riwayat kehadiran keluarga sendiri**.
- Tampilan berupa **daftar, bukan kalender**.

### Absen ronda
1. Tombol **"Absen ronda"** hanya tampil **di malam tugas keluarga itu dan selama jam ronda**; selebihnya disembunyikan. Pengecekan memakai **jam server** dengan toleransi sekitar 30 menit di awal dan akhir.
2. Kamera langsung terbuka (kamera langsung, **tanpa pilihan galeri**). Izin kamera diminta saat pertama mengetuk tombol, dengan teks penjelasan.
3. Setelah foto, **tanggal dan jam otomatis ditulis permanen di dalam gambar** (satu file JPEG, bukan lapisan terpisah), dengan latar gelap semi transparan agar terbaca di foto malam.
4. **Pratinjau**, lalu **Kirim**. Absen **langsung tercatat sebagai Hadir** tanpa menunggu persetujuan.
5. Server tetap mencatat jam servernya sendiri tanpa menolak apa pun. Tidak ada GPS atau jendela waktu ketat lainnya.
6. Absen biasa **tidak mengirim push** ke pengurus.

### Pengumuman (warga)
- Riwayat dikelompokkan **per bulan**, tiap card menampilkan **tanggal**.
- Detail: judul, isi, lampiran (PDF atau gambar).

### Program RT (warga)
Daftar card (judul, banner di atas atau sebagai latar card, tag status), detail seperti artikel. **Hanya melihat.**

### Galeri RT (warga)
- Daftar album **dikelompokkan per bulan**; card: judul kegiatan, tanggal, jumlah foto.
- Album: **grid bergaya Pinterest/bento** (landscape, potret, dan kotak tersusun otomatis).
- Ketuk foto: **pratinjau besar** dengan menu **Download**.
- Hanya melihat.

### Struktur Pengurus (warga)
Tree dua level (lihat bagian 5, Struktur pengurus). Tampil di ponsel secara vertikal.

### Bantuan
- Daftar **semua pengurus beserta jabatannya** (ketua juga masuk). Developer tidak tampil.
- Ketuk salah satu untuk membuka **WhatsApp** dengan **pesan awal otomatis** yang menyebut nama warga dan alamat rumahnya.
- Pengurus yang disembunyikan ketua dari Bantuan tidak tampil.
- Nomor HP tidak ditampilkan sebagai teks, hanya dipakai di tautan.
- Dari Beranda ke WhatsApp dua ketukan.

### Notifikasi (lonceng)
Halaman sendiri berisi daftar: terbaru di atas, dikelompokkan per hari, dimuat bertahap. Belum dibaca **di-highlight** (latar hijau pastel dan titik kecil). Mengetuk notifikasi membuka halaman tujuan dan menandainya sudah dibaca. Lonceng menampilkan badge jumlah yang belum dibaca.

### Profil (warga)
Ganti PIN, tema terang/gelap, ukuran tulisan, aktifkan pemberitahuan (dan matikan di perangkat ini), keluar.

### Login dan PIN
- Username `blok-nomor` plus akhiran bila ada, contoh `AB2-22a`.
- **PIN awal `123456`**, wajib diganti saat login pertama: warga tidak bisa membuka halaman lain sebelum PIN diganti.
- PIN baru **tidak boleh `123456` atau pola mudah** (`111111`, `654321`, dan sejenisnya).
- **Reset PIN oleh pengurus mengembalikan akun ke `123456`** dan memaksa ganti lagi.
- Rate limit dan penguncian sementara setelah beberapa kali salah.
- Sebelum switch **"Akses warga"** dinyalakan, login warga ditolak dengan pesan "Aplikasi belum dibuka".

### Aturan UX warga
- Teks, tombol, dan ikon memakai ukuran design system yang sama; **label teks selalu ada di samping ikon**.
- **Tanpa gerakan tersembunyi** (geser, tekan lama).
- Bahasa sehari-hari.
- **Maksimal 3 level navigasi** dari Beranda ke fitur apa pun.
- Tombol "Kembali" berlabel di halaman dalam.
- Aturan "semua aksi di halaman sendiri" berlaku di aplikasi pengurus; di aplikasi warga alurnya sederhana (form transfer tetap berupa halaman).

### Yang sengaja tidak ada untuk warga
Mengisi atau mengubah data warga, melihat NIK, telepon, atau iuran keluarga lain, chat, komentar, pengaturan rumit.

---

## 5. Aplikasi pengurus (ketua dan pengurus)

### Navigasi
- Bottom nav **4 tab**: **Beranda, Warga, Keuangan, Aktivitas**.
- Header: **ikon lonceng** (notifikasi) dan **ikon profil** (membuka halaman Profil). Tidak ada titik tiga.
- Desktop (≥1024px): bottom nav menjadi **sidebar kiri** yang langsung menampilkan tab utama dan semua menu, jadi halaman Lainnya tidak diperlukan di desktop.
- **Tab Warga, Keuangan, dan Beranda** memakai pola sama: konten utama di atas, **baris tombol lingkaran** di bawah search atau kartu utama.
- **Semua aksi, sesederhana apa pun, punya halaman sendiri** dengan navigasi SPA. Tidak ada bottom sheet. Detail aturan UI di dokumen 03.

### Beranda
Dari atas:
1. **Search**
2. **Card saldo kas** (hero)
3. **Card "Permintaan konfirmasi"** (hanya tampil bila ada; jumlah yang menunggu; ketuk membuka daftar)
4. **Aksi cepat, 5 kolom**: **Catat iuran, Kas masuk, Kas keluar, Laporan, Lainnya**
5. **Jumlah KK** dan **jumlah warga**, serta **card progres iuran** (persentase; ketuk membuka halaman Iuran)
6. **Aktivitas terakhir**

- **Catat iuran** membuka pemilihan keluarga lalu halaman Catat pembayaran (jalur 1).
- **Lainnya** punya badge bila ada yang perlu perhatian di dalamnya (permintaan konfirmasi menunggu, absen ronda malam ini).

### Halaman Lainnya (halaman sendiri, tanpa bottom nav)
Grid 4 kolom, ikon dengan label, dikelompokkan seperti m-banking:

| Kelompok | Isi |
|---|---|
| **Keuangan** | Catat iuran, Kas masuk, Kas keluar, Iuran, Laporan, Permintaan konfirmasi, **Iuran khusus**, **Tarif iuran dan denda** (Pengaturan warga) |
| **Warga** | Tambah keluarga, Scan KK, Ekspor data warga |
| **Ronda** | Jadwal ronda, **Jam ronda** (Pengaturan warga) |
| **Konten warga** | Pengumuman, Program RT, Galeri, Struktur pengurus |
| **Sistem** (hanya ketua) | Pengaturan aplikasi, Kelola pengurus |

- Di akun pengurus, kelompok **Sistem tidak tampil sama sekali** dan server menolak aksesnya.
- Badge tampil di kotak yang relevan.
- **Menu boleh muncul di lebih dari satu tempat** (contoh: Scan KK ada di tab Warga dan di Lainnya).

### Tab Warga
Mengelola buku induk keluarga.

**Daftar keluarga** (di bawah search, di atas daftar ada baris tombol lingkaran)
- Tiap baris: avatar inisial, nama kepala keluarga, blok dan nomor rumah, jumlah anggota di kanan, **dot kecil** bila data belum lengkap.
- Search hanya untuk **nama dan blok-nomor** (NIK tidak bisa dicari).
- **Baris tombol (4 kolom)**: **Tambah keluarga, Scan KK, Ekspor, Filter**. Tidak ada tombol "+" di header dan tidak ada chip.

**Filter** (halaman sendiri)
- **Blok**: dinamis, hanya blok yang punya keluarga aktif.
- **Kelengkapan data**: semua, data belum lengkap, **belum ganti PIN**.
- **Status keluarga**: aktif atau pindah.
- Tombol Terapkan dan Reset. Badge angka di tombol bila ada filter aktif. Pilihan tersimpan saat kembali dari detail.
- "Data belum lengkap" berarti telepon keluarga kosong, atau ada anggota dengan NIK atau tanggal lahir kosong.

**Tambah keluarga** (3 halaman berurutan)
1. **Data keluarga**: blok (dropdown dari daftar), nomor rumah (angka), akhiran (opsional, contoh `a`), alamat dan telepon (opsional).
2. **Anggota**: ditambah satu per satu. Wajib: **nama dan hubungan**. Opsional: NIK, no. KK, jenis kelamin, tempat dan tanggal lahir, agama, pekerjaan.
3. **Selesai**: akun warga dibuat otomatis. Tampil username dan "PIN awal: 123456".

Aturan data:
- **Satu rumah = satu keluarga = satu akun = satu kepala keluarga.** Kepala keluarga ditentukan pengurus berdasarkan hierarki di rumah, bukan dari KK. Kepala keluarga adalah anggota dengan hubungan "Kepala keluarga" (hanya satu).
- Satu rumah bisa berisi **beberapa KK**: no. KK disimpan di level anggota, dan hubungan anggota diisi terhadap kepala keluarga rumah itu.
- Kombinasi **blok + nomor + akhiran** harus **unik di antara keluarga aktif** ("22" dan "22a" adalah dua rumah berbeda). Urutan benar: 22, 22a, 23.
- **Blok dinamis**: diambil dari daftar yang dikelola ketua. Bila blok belum ada, form menampilkan "Blok belum ada, minta ketua menambahkannya di Pengaturan aplikasi".
- **NIK ganda** dicek saat simpan (NIK kosong tidak dianggap duplikat; anggota pindah, meninggal, atau dikeluarkan tidak dihitung).
- Tanggal lahir disimpan sebagai tanggal sungguhan.

**Detail keluarga** (halaman)
- Anggota (nama, hubungan, tanggal lahir atau umur, pekerjaan), alamat, telepon, baris yang menyebut data apa yang kurang.
- **NIK tersembunyi** per anggota; ketuk ikon mata untuk membuka. Pembukaan NIK **tercatat di Aktivitas**.
- Menu aksi (dropdown sebagai pintu masuk; **tiap pilihan membuka halaman sendiri**, bukan langsung eksekusi): edit keluarga, tambah anggota, edit anggota, tandai meninggal, keluarkan anggota, pindah keluarga, reset PIN.

**Status**
- **Meninggal**: per anggota. Anggota tampil redup, tidak dihitung di jumlah warga, riwayat tetap. Bila yang meninggal kepala keluarga, pengurus **wajib memilih kepala baru** dulu. Bila semua anggota sudah meninggal, sistem menawarkan menonaktifkan keluarga.
- **Pindah**: aksi di level **keluarga** (halaman berisi tanggal pindah dan catatan). Semua anggota nonaktif, akun login dimatikan, username dilepas, keluarga hilang dari daftar warga. Bila masih ada tunggakan, tampil peringatan tapi tetap boleh disimpan. Dapat **dipulihkan** lewat satu halaman konfirmasi. Tidak ada aksi "Arsipkan".
- **Keluarkan anggota**: pilih satu atau beberapa anggota, isi tanggal dan alasan; mereka nonaktif **tanpa mengubah status keluarga** (contoh: anak menikah lalu pindah sendiri). Bila anggota itu adalah pengurus, akunnya otomatis nonaktif.
- Tidak ada penghapusan data; semuanya status.

**Ekspor** (halaman sendiri; dari tab Warga atau Lainnya)
- Format **PDF dan XLSX**, dibuat di browser. PDF memakai **kop RT**, judul, tanggal dibuat, dan keterangan filter.
- Setelah jadi: **Bagikan** (pilihan share ponsel, termasuk WhatsApp; di desktop menjadi unduh) dan **Unduh**.
- **Cakupan sesuai filter aktif** dan mengikuti dari mana ekspor dibuka.
- **Mode Per keluarga** (satu baris = satu keluarga): kolom default **No, Nama kepala keluarga, Blok dan nomor rumah**. Opsional: jumlah anggota.
- **Mode Per warga** (satu baris = satu anggota, dikelompokkan per keluarga): kolom default **No, Nama, Blok/No. rumah**; blok dan nomor rumah **digabung (merge) dalam satu sel** untuk satu kelompok keluarga. Kepala keluarga di baris pertama kelompok. Urutan: blok lalu nomor rumah. Opsional: hubungan, jenis kelamin, umur (tahun), pekerjaan.
- **Tidak pernah memuat** NIK, nomor telepon, nominal iuran, atau tanggal lahir lengkap.
- Anggota atau keluarga pindah dan meninggal tidak ikut, kecuali filter status diubah. Filter bekerja di level keluarga.
- Setiap ekspor tercatat di Aktivitas **tanpa isi datanya**.
- Dari halaman Iuran, ekspor menghasilkan tabel keluarga sesuai filter aktif (contoh: belum lunas bulan ini) tanpa kolom nominal; judul dan keterangan PDF menyebut filternya. Cocok untuk dibagikan ke grup WhatsApp.

**Scan KK**
- **Tombol sudah disediakan di UI, tetapi untuk sementara tidak melakukan apa-apa** (tanpa label "segera hadir"). Dikerjakan **paling akhir** dan wajib tersambung sebelum rilis.
- Alur akhirnya: kamera atau pilih foto KK, hasil baca masuk ke form Tambah keluarga dengan isian otomatis, pengurus memeriksa lalu menyimpan.
- Fitur ini hanya alat bantu pengurus. Warga tidak pernah mengisi atau mengunggah KK.

### Tab Keuangan (langsung menampilkan kas)
1. **Card saldo kas** (saldo keseluruhan).
2. **Baris tombol 5 kolom**: **Kas masuk, Kas keluar, Iuran, Laporan, Filter**. Badge angka di Iuran bila ada permintaan konfirmasi menunggu.
3. **Riwayat kas**: dikelompokkan per tanggal; tiap item: masuk atau keluar, kategori, nominal. Ketuk untuk detail dan aksi **Batalkan**.

**Filter riwayat kas** (halaman sendiri): bulan, jenis (masuk atau keluar), kategori. Saat filter aktif, card saldo tetap saldo keseluruhan; di atas daftar ada baris ringkasan (contoh "Menampilkan Oktober 2026, kas keluar") beserta total hasil filter. Badge angka di tombol. Pilihan tersimpan.

**Kas masuk dan Kas keluar** (halaman form): nominal, kategori, metode, keterangan, tanggal, keluarga (opsional). Kategori khusus yang sudah dibahas: **Saldo awal** (label biasa untuk total kas serah terima), **Pengembalian kelebihan**, dan kategori otomatis dari iuran.

### Halaman Iuran (dari tombol Iuran)
- **Ringkasan**: persentase keluarga lunas, total terkumpul, total tunggakan.
- **Permintaan konfirmasi** dari warga di atas daftar: tiap item menampilkan keluarga, nominal, nama pengirim, bank, waktu. Ketuk membuka detail dan bukti, lalu **Konfirmasi** atau **Tolak**.
- **Search**, lalu tombol lingkaran **Filter** (bulan, blok, status) dan **Ekspor**.
- **Daftar keluarga**: blok dan rumah, kepala keluarga, **total tagihan sekarang** (sisa atau "Kelebihan Rp X"), tag status.
- **Rincian tagihan** (halaman per keluarga): total, tagihan per bulan dengan sisanya, riwayat pembayaran, dan aksi (masing-masing halaman sendiri): **Catat pembayaran**, **Batalkan pembayaran**, **Batalkan denda**.

### Halaman Iuran khusus
- Form: **nama, nominal per keluarga, bulan**.
- **Pratinjau** sebelum simpan, contoh "50 keluarga × Rp 20.000 = Rp 1.000.000".
- Setelah simpan, tagihan langsung masuk ke total semua keluarga aktif dan warga mendapat satu notifikasi.
- Prioritas potong di atas kas.
- Pengurus bisa **mengubah nominal** atau **membatalkan** iuran khusus (halaman sendiri, pratinjau dampak, alasan wajib, tercatat di Aktivitas).
- Keluarga yang baru terdaftar tidak ikut iuran khusus yang sudah terbit.

### Halaman Laporan
- Pilih bulan. Isi: saldo awal, total masuk, total keluar, saldo akhir, **rincian per kategori**, **rekap iuran**, dan **daftar transaksi bulan itu**.
- Tombol: **ekspor PDF**, **ekspor XLSX** (berkop RT), dan **salin ringkasan teks** untuk grup WhatsApp.
- Ekspor riwayat kas hanya lewat Laporan.

### Tab Aktivitas
- **Hanya daftar** aktivitas pengurus, terbaru di atas, dikelompokkan **per hari**. Tiap baris: pelaku (dengan jabatan, contoh "Budi (Bendahara)"), aksi, jam.
- **Filter bulan dan tahun** berupa dua dropdown di atas daftar (bukan halaman filter, bukan tombol). Default bulan berjalan; pilihan tahun hanya yang punya aktivitas; baris ringkasan (contoh "Oktober 2026, 48 aktivitas"); pilihan tersimpan saat kembali; bulan kosong menampilkan pesan jelas. Data dimuat per bulan.
- Ketuk baris membuka detail (halaman sendiri) berisi **nilai sebelum dan sesudah**. Tidak ada tombol aksi.
- **Hanya bisa dilihat ketua dan pengurus.** Warga tidak punya akses, endpoint juga menolak.
- **Tidak bisa diubah atau dihapus** lewat aplikasi, siapa pun pelakunya, termasuk ketua.
- Yang dicatat:
  - Data warga: tambah, ubah, tandai meninggal, keluarkan, pindah, pulihkan, reset PIN, **membuka NIK**
  - Pembayaran: input, konfirmasi, tolak permintaan, pembatalan, pembatalan denda
  - Kas: tambah, batalkan
  - Iuran khusus: buat, ubah, batalkan
  - Pengaturan (aplikasi dan warga): nilai lama dan baru, toggle "terapkan bulan ini"
  - Konten: pengumuman, program, galeri, ronda
  - Ronda: batalkan absen, absen manual, perubahan jadwal
  - Akun pengurus: angkat, ubah, nonaktifkan, reset (hanya ketua)
  - **Ekspor** (tanpa isi data)
  - Login pengurus yang gagal berulang kali
  - Aksi otomatis aplikasi dan aksi developer yang mengubah data, atas nama **"Sistem"**
- Di database tiap entri punya penanda sumber (`pengguna`, `sistem_otomatis`, `developer`).

### Notifikasi pengurus
- **Setiap entri Aktivitas otomatis menjadi notifikasi** untuk semua ketua dan pengurus, dan ikut dikirim sebagai push.
- **Pelaku tidak menerima notifikasi dari aksinya sendiri.**
- Isi push singkat dan menyebut pelaku; untuk pembukaan NIK hanya "membuka data sensitif keluarga X", tanpa nomor NIK.
- Aksi beruntun **digabung** jadi satu notifikasi (contoh: mengedit banyak anggota atau input data massal).
- Login gagal berulang tetap masuk notifikasi.
- Permintaan konfirmasi baru dari warga langsung masuk push.
- Pengurus bisa mematikan push per perangkat dari Profil; notifikasi di dalam aplikasi tetap ada.
- Halaman Notifikasi pengurus sama polanya dengan warga.

### Profil (pengurus dan ketua; halaman sendiri)
Hanya hal pribadi, bukan pengaturan RT. Profil ketua dan pengurus identik.
- Kartu identitas (avatar inisial, nama, label peran; hanya tampil)
- **Pas foto** (dipakai di Struktur pengurus, tersimpan sebagai data anggota)
- **Ganti password**
- **Tampilan**: tema terang/gelap, ukuran tulisan
- **Pemberitahuan**: aktifkan push, switch matikan di perangkat ini
- **Keluar**

### Pengaturan aplikasi (khusus ketua dan developer)
Identitas dan konfigurasi dasar RT:
- Nama aplikasi dan ikon aplikasi (PWA)
- Nama RT, nama perumahan, logo RT, logo desa, isi kop
- **Daftar blok** (tambah, ganti nama)
- Switch **"Akses warga"** (mati sampai tanggal peluncuran)

### Pengaturan warga (ketua dan pengurus)
- **Nominal kas** dan **denda ronda**, beserta toggle "Terapkan juga ke bulan ini" dan pratinjau dampak
- **Jam ronda** (default)

Nomor WhatsApp bantuan **tidak ada**; Bantuan memakai nomor HP pengurus.

### Kelola pengurus (khusus ketua)
- Awalnya hanya ada akun ketua dan akun warga. Ketua **memilih anggota dari daftar warga lalu mengangkatnya menjadi pengurus**.
- Saat mengangkat: **jabatan diketik manual**, **nomor HP wajib** (isian awal bisa diambil dari telepon keluarga), tampil kalimat bahwa nomor akan dipakai warga menghubungi lewat WhatsApp.
- Akun pengurus tertaut ke satu anggota (satu anggota maksimal satu akun pengurus), login memakai password.
- Aksi: ubah jabatan, nonaktifkan, **reset password** (password sementara, wajib diganti saat login berikutnya), atur **tampil atau sembunyi di Bantuan**, atur **urutan** di Struktur pengurus.
- Bila anggota yang ditautkan pindah, meninggal, atau dikeluarkan, **akun pengurusnya otomatis nonaktif**.
- Akun developer tidak tampil.

### Konten warga

**Pengumuman**
- Isi: judul, teks, **satu lampiran (PDF atau gambar)**.
- **Pin** maksimal 2. Percobaan pin ketiga menampilkan pesan "lepas salah satu dulu".
- Tampil di Beranda warga maksimal 3 card: yang di-pin paling atas, lalu terbaru. Pengumuman baru muncul di bawah yang di-pin.
- **Edit kapan pun**: setelah diedit, pengumuman dianggap baru dan tampil di atas lagi (tanggal terbit diperbarui, yang lama hilang), **tanpa penanda "diedit"**.
- Tidak ada masa tayang. Riwayat lewat "Pengumuman lain", dikelompokkan per bulan.
- Tidak ada status "sudah dibaca".
- **Push ke semua warga** saat pengumuman baru. Edit **tidak** mengirim push ulang kecuali pengurus mencentang "kirim notifikasi lagi" (default mati).

**Program RT**
- Isinya visi dan misi ketua: program yang akan dan sedang dijalankan (contoh: pengadaan CCTV, digitalisasi RT termasuk aplikasi ini).
- Data: **judul, banner** (di atas artikel atau latar card), **isi bergaya artikel** (editor teks kaya: paragraf, daftar, tabel, gambar; anggaran ditulis di dalam artikel), dan **tag status**: Direncanakan, Berjalan, Selesai.
- Tanpa tanggal mulai dan selesai. Tanpa push. Warga hanya melihat.

**Galeri**
- **Album per kegiatan**: judul dan tanggal kegiatan.
- Daftar dikelompokkan **per bulan**; card: judul, tanggal, jumlah foto.
- Isi album: grid bergaya Pinterest/bento; ketuk foto membuka pratinjau besar dengan menu Download.
- **Upload banyak sekaligus**, tanpa caption, **tanpa batas jumlah foto**.
- Hapus foto lewat halaman **Kelola foto** (pilih beberapa foto, lalu halaman konfirmasi).
- Boleh dilihat semua warga (dokumentasi); tanpa push.

**Struktur pengurus**
- **Berdasarkan data akun pengurus** (tanpa entri manual): pas foto, nama, jabatan.
- **Tree dua level**: Ketua di puncak, pengurus lain **sejajar** di bawahnya.
- Urutan diatur ketua (kontrol urutan hanya tampil di akun ketua; pengurus hanya melihat).
- Di ponsel digambar **vertikal**, di desktop melebar.

### Jadwal ronda (pengurus)
**Satu unit: "malam ronda"** = satu tanggal dengan daftar keluarga bertugas dan jam.

**Dua jenis jadwal**
- **Jadwal tetap**: pengurus memilih hari (semua hari, hanya Sabtu, dan seterusnya sesuai kebijakan RT), jam, dan keluarga per hari.
- **Jadwal khusus**: pengurus menentukan tanggal dan keluarganya sendiri (contoh: malam Tahun Baru). Jumlahnya bebas. Bila tanggalnya sama dengan jadwal tetap, jadwal khusus **menggantikan** malam itu.
- Jam boleh berbeda per jadwal; bila kosong memakai jam default dari Pengaturan warga.

**Pembuatan malam ronda**
- Malam ronda satu bulan dibuat tanggal 1 bersamaan dengan tagihan (cron). Untuk bulan peluncuran, malam ronda disiapkan sebelum tanggal 1.
- Tiap malam bisa diedit sendiri (tukar jadwal, keluarga berhalangan) tanpa mengubah pola tetapnya.
- Perubahan jadwal tetap hanya berlaku untuk malam yang **belum lewat**.
- Satu malam boleh diisi lebih dari satu keluarga.
- Jadwal yang melewati tengah malam dihitung ke **tanggal mulainya**.

**Tampilan pengurus**
- **Kalender bulanan**: titik di tanggal yang ada ronda; jadwal khusus berwarna lain.
- Baris tombol: **Jadwal tetap, Jadwal khusus, Isi otomatis** (membagi keluarga berurutan menurut blok dan nomor rumah).
- Card **"Malam ini"**: siapa yang sudah dan belum absen.
- Ketuk tanggal membuka **halaman malam itu**: siapa bertugas, status absen, jam, foto, ganti keluarga, **batalkan absen**, **absenkan manual**.

**Absensi dan denda**
- Pengurus bisa **melihat semua absen yang dikirim**, **membatalkan absen** (status kembali "Belum absen", warga mendapat notifikasi), atau **mengabsenkan manual** tanpa foto (warga diberi tahu). Keduanya tercatat di Aktivitas dan masuk push pengurus.
- **Edit absensi hanya bisa di bulan yang sama.** Setelah denda bulan itu terbit, absensi **terkunci**.
- "Tidak hadir" **tidak disimpan** sebagai data: dihitung otomatis (keluarga bertugas, malam sudah lewat, tidak ada catatan absen). **Pengurus tidak perlu menandai apa pun.**
- Denda terbit **bulan berikutnya**, bukan realtime.
- Setelah denda terbit, pengurus tetap bisa **membatalkan satu denda manual** dari rincian tagihan (alasan wajib, warga diberi tahu).
- Pengingat sore 17.00 "malam ini giliran ronda keluargamu".

---

## 6. Notifikasi untuk warga

Prinsip: **setiap perubahan yang melibatkan warga memberi notifikasi** ke warga, dan semua notifikasi di aplikasi **ikut dikirim sebagai push**.

- Permintaan transfer dikonfirmasi atau ditolak (beserta alasan)
- Pembayaran tunai dicatat pengurus
- Pembayaran dibatalkan atau dikoreksi
- Tagihan bulan baru terbit
- Denda ronda bulan lalu (satu notifikasi ringkas per keluarga)
- Denda dibatalkan
- Iuran khusus baru, diubah, atau dibatalkan
- Perubahan nominal yang diterapkan ke bulan ini
- Muncul kelebihan bayar
- Pengumuman baru
- Jadwal ronda baru atau berubah; pengingat ronda sore hari
- Absen ronda dibatalkan atau diabsenkan manual oleh pengurus
- PIN direset

Kejadian massal dikirim **satu notifikasi per keluarga**. Isi singkat, tanpa data sensitif.

---

## 7. Peluncuran dan persiapan

- Aplikasi dipublikasikan **tanggal 30-31**, setelah pelantikan RT baru (pertengahan bulan). Ada sekitar **15 hari persiapan** yang hanya dipakai pengurus; **warga mendapat akses tanggal 1**.
- Data keuangan dimulai bersih: buku diselesaikan dulu sampai semua tagihan **clear**, lalu **total kas serah terima dimasukkan lewat Kas masuk** dengan kategori **"Saldo awal"**. Tidak ada fitur khusus untuk data awal.
- Tagihan pertama terbit tanggal 1 lewat cron. Denda ronda baru terbit di bulan sesudahnya (denda periode sebelumnya sudah selesai di buku).
- Switch **"Akses warga"** di Pengaturan aplikasi dinyalakan tanggal 1.
- Tanggal 1 ada **sosialisasi door-to-door**: pengurus memandu warga login dengan PIN `123456` dan menggantinya.

**Urutan persiapan**
1. Isi Pengaturan aplikasi (blok, kop, logo, nama RT)
2. Angkat pengurus, lengkapi jabatan dan nomor HP
3. Input semua keluarga dan anggota
4. Atur jadwal ronda tetap (dan Pengaturan warga: nominal, jam ronda)
5. Pasang dan uji tiga job cron
6. Tanggal 30-31: input saldo awal, pastikan semuanya sesuai buku
7. Tanggal 1: nyalakan "Akses warga", tagihan pertama terbit

---

## 8. Di luar rilis pertama (pengembangan lanjutan)

- Denah RT otomatis (blok dan nomor rumah berurutan, klik rumah membuka detail keluarga)
- Surat pengantar
- Agenda dan notulen rapat
- Kategori kas lebih rinci
- **Event** yang melibatkan data warga (contoh: Posyandu, dengan daftar keluarga yang punya balita difilter dan dijadikan tabel). Syarat data: tanggal lahir sudah tersimpan sebagai tanggal sungguhan.

---

## 9. Urutan pembangunan (bertahap, usulan)

Dipublikasikan setelah semuanya selesai.
1. Fondasi: proyek, dua aplikasi, login, role, layout, PWA dasar
2. Data keluarga dan anggota (form manual, NIK terenkripsi, blok dinamis)
3. Tagihan dan pembayaran jalur 1 (potong otomatis, kelebihan bayar)
4. Jalur 2: form "Saya sudah transfer", bukti, konfirmasi pengurus
5. Notifikasi (daftar dan push) dan job cron
6. Kas, saldo, laporan, ekspor PDF dan XLSX, iuran khusus
7. Pengumuman, jadwal ronda, absen foto, denda otomatis, struktur pengurus, Bantuan
8. Program RT dan galeri
9. Dark mode, pemolesan, uji coba, deploy
10. **Scan KK** (paling akhir)

---

## 10. Usulan yang belum dikonfirmasi eksplisit

Dibuat mengikuti arah diskusi tetapi belum dijawab satu per satu. Ubah bila tidak sesuai.

1. Kas masuk otomatis dari iuran **selalu otomatis tanpa opsi mematikan**.
2. Kas masuk iuran **dipecah per jenis** sesuai alokasi (contoh "Iuran kas" dan "Iuran khusus: 17 Agustus"), dan Laporan menampilkan tiap iuran khusus sebagai barisnya sendiri.
3. Status iuran tiga tingkat: Lunas, Belum lunas, Menunggak.
4. Daftar **kategori kas** selain yang disebut di atas belum dibahas; ditentukan saat build.
5. **Hapus pengumuman**, **edit dan hapus album galeri**, dan **cakupan search di Beranda pengurus** belum dibahas.
6. Tab **Warga di aplikasi warga**: apakah daftar per keluarga atau per anggota belum dibahas (usulan: per keluarga, kepala keluarga dan blok-nomor).
7. Isi otomatis jadwal ronda: apakah regu tetap atau **bergiliran lintas minggu** belum dijawab.
8. **Pernyataan privasi** untuk warga saat pertama login belum dibahas.
9. **Backup** belum dibahas (lihat dokumen 01, bagian 13).
