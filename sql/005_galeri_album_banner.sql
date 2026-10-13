-- Banner cover album galeri (jalankan sekali di phpMyAdmin jika kolom belum ada)
ALTER TABLE galeri_album
  ADD COLUMN banner VARCHAR(255) NULL AFTER judul;
