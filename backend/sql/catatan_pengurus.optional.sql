-- OPSIONAL: hanya jika auto-create gagal. Biasanya TIDAK perlu dijalankan manual.
CREATE TABLE IF NOT EXISTS `catatan_pengurus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal` DATE NULL,
  `nama` VARCHAR(191) NULL,
  `alamat` VARCHAR(191) NULL,
  `keperluan` VARCHAR(255) NULL,
  `keterangan` TEXT NULL,
  `dokumentasi` VARCHAR(255) NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
