-- Fitur Reward / Undian (multi-event). Tabel juga di-ensure otomatis oleh RewardService.
SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE TABLE IF NOT EXISTS reward_event (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  isi_html MEDIUMTEXT NULL,
  status ENUM('aktif','arsip') NOT NULL DEFAULT 'aktif',
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_reward_event_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reward_batch (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  diundi_pada DATETIME NOT NULL,
  diundi_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  dibatalkan_oleh INT UNSIGNED NULL,
  KEY idx_reward_batch_event (event_id),
  CONSTRAINT fk_reward_batch_event FOREIGN KEY (event_id) REFERENCES reward_event(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reward_pemenang (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  batch_id INT UNSIGNED NOT NULL,
  event_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  status ENUM('tersedia','diklaim','hangus') NOT NULL DEFAULT 'tersedia',
  expired_at DATETIME NOT NULL,
  diklaim_pada DATETIME NULL,
  diklaim_oleh INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_reward_pemenang_event (event_id),
  KEY idx_reward_pemenang_batch (batch_id),
  KEY idx_reward_pemenang_keluarga (keluarga_id),
  KEY idx_reward_pemenang_status (status, expired_at),
  CONSTRAINT fk_reward_pemenang_batch FOREIGN KEY (batch_id) REFERENCES reward_batch(id) ON DELETE CASCADE,
  CONSTRAINT fk_reward_pemenang_event FOREIGN KEY (event_id) REFERENCES reward_event(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reward_event_pernah (
  event_id INT UNSIGNED NOT NULL,
  keluarga_id INT UNSIGNED NOT NULL,
  batch_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id, keluarga_id),
  KEY idx_reward_pernah_batch (batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
