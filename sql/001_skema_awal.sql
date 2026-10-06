-- rtdua Stage 9: skema awal
-- Import lewat phpMyAdmin (utf8mb4). File lama tidak diubah.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- Pengaturan (satu baris)
CREATE TABLE IF NOT EXISTS pengaturan (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  nama_app VARCHAR(64) NOT NULL DEFAULT 'rtdua',
  ikon_app VARCHAR(255) NULL,
  nama_rt VARCHAR(128) NOT NULL DEFAULT '',
  nama_perumahan VARCHAR(255) NOT NULL DEFAULT '',
  logo_rt VARCHAR(255) NULL,
  logo_desa VARCHAR(255) NULL,
  kop_teks TEXT NULL,
  akses_warga TINYINT(1) NOT NULL DEFAULT 0,
  nominal_kas INT UNSIGNED NOT NULL DEFAULT 40000,
  denda_ronda INT UNSIGNED NOT NULL DEFAULT 10000,
  jam_ronda_mulai TIME NOT NULL DEFAULT '21:00:00',
  jam_ronda_selesai TIME NOT NULL DEFAULT '00:00:00',
  setup_selesai TINYINT(1) NOT NULL DEFAULT 0,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pengaturan (id, nama_app, setup_selesai) VALUES (1, 'rtdua', 0)
  ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS blok (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(32) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_blok_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS keluarga (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  blok_id INT UNSIGNED NOT NULL,
  nomor VARCHAR(16) NOT NULL,
  akhiran VARCHAR(8) NOT NULL DEFAULT '',
  alamat VARCHAR(255) NULL,
  telepon VARCHAR(32) NULL,
  status ENUM('aktif','pindah') NOT NULL DEFAULT 'aktif',
  tanggal_pindah DATE NULL,
  catatan_pindah TEXT NULL,
  mulai_periode CHAR(7) NULL COMMENT 'YYYY-MM bulan pertama tagihan',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_keluarga_blok (blok_id),
  KEY idx_keluarga_status (status),
  CONSTRAINT fk_keluarga_blok FOREIGN KEY (blok_id) REFERENCES blok(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warga (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  keluarga_id INT UNSIGNED NOT NULL,
  nama VARCHAR(128) NOT NULL,
  hubungan VARCHAR(64) NOT NULL DEFAULT 'Anggota',
  no_kk VARCHAR(32) NULL,
  nik_enc TEXT NULL,
  nik_hash CHAR(64) NULL,
  jenis_kelamin ENUM('L','P') NULL,
  tempat_lahir VARCHAR(64) NULL,
  tanggal_lahir DATE NULL,
  agama VARCHAR(32) NULL,
  pekerjaan VARCHAR(64) NULL,
  foto VARCHAR(255) NULL,
  status ENUM('aktif','meninggal','keluar') NOT NULL DEFAULT 'aktif',
  tanggal_status DATE NULL,
  alasan VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_warga_keluarga (keluarga_id),
  KEY idx_warga_nik_hash (nik_hash),
  CONSTRAINT fk_warga_keluarga FOREIGN KEY (keluarga_id) REFERENCES keluarga(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role ENUM('ketua','pengurus','warga') NOT NULL,
  username VARCHAR(64) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  keluarga_id INT UNSIGNED NULL COMMENT 'akun warga',
  warga_id INT UNSIGNED NULL COMMENT 'akun pengurus (individu)',
  jabatan VARCHAR(64) NULL,
  nomor_hp VARCHAR(32) NULL,
  tampil_di_bantuan TINYINT(1) NOT NULL DEFAULT 0,
  urutan_struktur INT NOT NULL DEFAULT 0,
  is_developer TINYINT(1) NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  harus_ganti_kredensial TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_warga_id (warga_id),
  KEY idx_users_keluarga (keluarga_id),
  KEY idx_users_role (role),
  CONSTRAINT fk_users_keluarga FOREIGN KEY (keluarga_id) REFERENCES keluarga(id),
  CONSTRAINT fk_users_warga FOREIGN KEY (warga_id) REFERENCES warga(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS iuran_khusus (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(128) NOT NULL,
  periode CHAR(7) NOT NULL,
  nominal INT UNSIGNED NOT NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS iuran_tagihan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  keluarga_id INT UNSIGNED NOT NULL,
  periode CHAR(7) NOT NULL,
  jenis ENUM('kas','denda_ronda','khusus') NOT NULL,
  iuran_khusus_id INT UNSIGNED NULL,
  ronda_malam_id INT UNSIGNED NULL,
  nominal INT UNSIGNED NOT NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  dibatalkan_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  alasan VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tagihan_keluarga (keluarga_id, periode),
  CONSTRAINT fk_tagihan_keluarga FOREIGN KEY (keluarga_id) REFERENCES keluarga(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pembayaran (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  keluarga_id INT UNSIGNED NOT NULL,
  tanggal_bayar DATE NOT NULL,
  metode ENUM('tunai','transfer') NOT NULL,
  nominal INT UNSIGNED NOT NULL,
  catatan VARCHAR(255) NULL,
  dicatat_oleh INT UNSIGNED NULL,
  dicatat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  permintaan_id INT UNSIGNED NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  dibatalkan_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  alasan_batal VARCHAR(255) NULL,
  KEY idx_bayar_keluarga (keluarga_id),
  CONSTRAINT fk_bayar_keluarga FOREIGN KEY (keluarga_id) REFERENCES keluarga(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pembayaran_permintaan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  keluarga_id INT UNSIGNED NOT NULL,
  diajukan_oleh INT UNSIGNED NULL,
  nominal_diajukan INT UNSIGNED NOT NULL,
  nama_pengirim VARCHAR(128) NULL,
  bank_pengirim VARCHAR(64) NULL,
  bukti_file VARCHAR(255) NULL,
  diajukan_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('menunggu','dikonfirmasi','ditolak') NOT NULL DEFAULT 'menunggu',
  diproses_oleh INT UNSIGNED NULL,
  diproses_pada DATETIME NULL,
  nominal_dikonfirmasi INT UNSIGNED NULL,
  alasan_tolak VARCHAR(255) NULL,
  pembayaran_id INT UNSIGNED NULL,
  bukti_dihapus_pada DATETIME NULL,
  KEY idx_permintaan_status (status),
  CONSTRAINT fk_permintaan_keluarga FOREIGN KEY (keluarga_id) REFERENCES keluarga(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kas_transaksi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipe ENUM('masuk','keluar') NOT NULL,
  nominal INT UNSIGNED NOT NULL,
  kategori VARCHAR(64) NOT NULL,
  metode ENUM('tunai','transfer') NOT NULL DEFAULT 'tunai',
  keterangan VARCHAR(255) NULL,
  tanggal DATE NOT NULL,
  keluarga_id INT UNSIGNED NULL,
  pembayaran_id INT UNSIGNED NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  dibatalkan_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  alasan_batal VARCHAR(255) NULL,
  dicatat_oleh INT UNSIGNED NULL,
  dicatat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_kas_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_jadwal_tetap (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  hari TINYINT UNSIGNED NOT NULL COMMENT '0=Minggu .. 6=Sabtu',
  jam_mulai TIME NULL,
  jam_selesai TIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_jadwal_tetap_keluarga (
  jadwal_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (jadwal_id, keluarga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_jadwal_khusus (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  jam_mulai TIME NULL,
  jam_selesai TIME NULL,
  keterangan VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_jadwal_khusus_keluarga (
  jadwal_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (jadwal_id, keluarga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_malam (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  sumber ENUM('tetap','khusus','manual') NOT NULL DEFAULT 'tetap',
  UNIQUE KEY uq_malam_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_malam_keluarga (
  malam_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (malam_id, keluarga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_absen (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  malam_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  waktu_server DATETIME NOT NULL,
  waktu_klien DATETIME NULL,
  foto VARCHAR(255) NULL,
  sumber ENUM('warga','manual') NOT NULL DEFAULT 'warga',
  dicatat_oleh INT UNSIGNED NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  dibatalkan_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  KEY idx_absen_malam (malam_id, keluarga_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ronda_kunci_bulan (
  periode CHAR(7) NOT NULL PRIMARY KEY,
  dikunci_pada DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengumuman (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  isi TEXT NOT NULL,
  lampiran_file VARCHAR(255) NULL,
  lampiran_tipe VARCHAR(32) NULL,
  pin TINYINT(1) NOT NULL DEFAULT 0,
  diterbitkan_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dibuat_oleh INT UNSIGNED NULL,
  diubah_oleh INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS program_rt (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  banner VARCHAR(255) NULL,
  isi_html MEDIUMTEXT NULL,
  status ENUM('direncanakan','berjalan','selesai') NOT NULL DEFAULT 'direncanakan',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS galeri_album (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  tanggal_kegiatan DATE NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS galeri_foto (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  album_id INT UNSIGNED NOT NULL,
  file VARCHAR(255) NOT NULL,
  thumb VARCHAR(255) NULL,
  lebar INT UNSIGNED NULL,
  tinggi INT UNSIGNED NULL,
  diunggah_oleh INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_foto_album (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifikasi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  jenis VARCHAR(64) NOT NULL DEFAULT 'umum',
  judul VARCHAR(255) NOT NULL,
  isi TEXT NULL,
  tautan VARCHAR(255) NULL,
  dibaca_pada DATETIME NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_user (user_id, dibuat_pada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS push_langganan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  endpoint TEXT NOT NULL,
  kunci TEXT NULL,
  perangkat VARCHAR(128) NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  jumlah_gagal INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_push_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT UNSIGNED NULL,
  sumber ENUM('pengguna','sistem_otomatis','developer') NOT NULL DEFAULT 'pengguna',
  tampil TINYINT(1) NOT NULL DEFAULT 1,
  aksi VARCHAR(64) NOT NULL,
  objek VARCHAR(64) NULL,
  objek_id VARCHAR(64) NULL,
  sebelum JSON NULL,
  sesudah JSON NULL,
  ip VARCHAR(45) NULL,
  KEY idx_audit_waktu (waktu),
  KEY idx_audit_tampil (tampil, waktu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tugas_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  jenis VARCHAR(64) NOT NULL,
  periode VARCHAR(32) NOT NULL,
  selesai_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tugas (jenis, periode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_percobaan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  identitas VARCHAR(128) NOT NULL,
  sisi ENUM('warga','pengurus') NOT NULL,
  ip VARCHAR(45) NULL,
  berhasil TINYINT(1) NOT NULL DEFAULT 0,
  dicoba_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_id (identitas, sisi, dicoba_pada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
