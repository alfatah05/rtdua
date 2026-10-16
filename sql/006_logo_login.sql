-- Logo yang dipakai di halaman login: rt | desa
-- Import lewat phpMyAdmin (sekali saja). Aman dijalankan ulang.

SET NAMES utf8mb4;

-- Tambah kolom jika belum ada
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pengaturan'
    AND COLUMN_NAME = 'logo_login'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE pengaturan ADD COLUMN logo_login ENUM(''rt'',''desa'') NOT NULL DEFAULT ''rt'' AFTER logo_desa',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
