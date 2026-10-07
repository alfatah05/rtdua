-- Stage 12: kolom status push pada notifikasi
-- Jalankan di phpMyAdmin (database DEV). Abaikan error "Duplicate column" bila sudah ada.
SET NAMES utf8mb4;
SET time_zone = '+07:00';

ALTER TABLE notifikasi
  ADD COLUMN push_status ENUM('pending','sent','skip') NOT NULL DEFAULT 'pending' AFTER tautan;

ALTER TABLE notifikasi
  ADD COLUMN push_coba TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER push_status;
