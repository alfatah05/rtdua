-- Nama tampilan akun (terutama ketua yang tidak tertaut warga)
ALTER TABLE users
  ADD COLUMN nama VARCHAR(128) NULL AFTER username;
