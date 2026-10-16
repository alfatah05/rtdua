-- Multi-pos kas (jalankan di phpMyAdmin dev dulu)
-- Aman diulang: IF NOT EXISTS / cek kolom sederhana

CREATE TABLE IF NOT EXISTS kas_pos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(80) NOT NULL,
  jatah_bulanan INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Target isi dari iuran per bulan (Rp)',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  urutan SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_aktif_urutan (aktif, urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kolom kas_pos_id (abaikan error jika sudah ada)
ALTER TABLE kas_transaksi
  ADD COLUMN kas_pos_id INT UNSIGNED NULL DEFAULT NULL AFTER kategori,
  ADD KEY idx_kas_pos_id (kas_pos_id);

-- Seed default bila kosong
INSERT INTO kas_pos (nama, jatah_bulanan, aktif, urutan, created_at, updated_at)
SELECT 'Kas RT', 0, 1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM kas_pos LIMIT 1);

-- Transaksi lama tanpa pos → tempel ke pos pertama aktif
UPDATE kas_transaksi kt
INNER JOIN (
  SELECT id FROM kas_pos WHERE aktif = 1 ORDER BY urutan ASC, id ASC LIMIT 1
) p ON 1=1
SET kt.kas_pos_id = p.id
WHERE kt.kas_pos_id IS NULL;
