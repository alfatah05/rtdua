# Akun developer

Akun developer = role `ketua` + `is_developer = 1`.

## Buat hash password

Di server (atau PHP lokal):

```php
<?php
echo password_hash('PasswordDeveloperKamu', PASSWORD_DEFAULT);
```

## Insert SQL (sesuaikan id warga/keluarga yang valid, atau buat dulu)

```sql
-- Contoh: setelah setup, anggap warga_id ketua = 1
INSERT INTO users (role, username, password_hash, warga_id, jabatan, is_developer, aktif, harus_ganti_kredensial)
VALUES (
  'ketua',
  'developer',
  '$2y$10$GANTI_DENGAN_HASH',
  1,
  'Developer',
  1,
  1,
  0
);
```

Developer bisa login ke **pengurus** meski akses warga ditutup.
