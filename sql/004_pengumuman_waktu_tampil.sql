-- Waktu tampil di beranda (service juga auto ADD COLUMN saat list/buat)
ALTER TABLE pengumuman
  ADD COLUMN tampil_mulai DATETIME NULL AFTER pin,
  ADD COLUMN tampil_selesai DATETIME NULL AFTER tampil_mulai;
