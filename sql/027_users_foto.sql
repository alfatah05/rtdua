-- Foto profil akun (ketua mandiri & cadangan pengurus)
ALTER TABLE users
  ADD COLUMN foto VARCHAR(255) NULL AFTER nama;
