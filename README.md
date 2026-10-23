# Patch: Aktifkan PWA Push Notifikasi

## Yang diperbaiki
1. **Register service worker** di `main.js` (warga + pengurus) via `virtual:pwa-register`
2. **Tombol "Aktifkan push"** di halaman Notifikasi (warga & pengurus)
3. **usePush** memakai SW yang sudah terdaftar + sync subscription
4. **sw-push.js** handler showNotification + klik buka `/notifikasi/...`
5. Payload push: URL default `/notifikasi/{id}`

Backend `NotifikasiService::kirim()` sudah mengirim push saat notifikasi dibuat (pembayaran, pengumuman, ronda, denda, dll).

## Wajib di server (.env backend)
```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:admin@domainmu.com
```
Generate sekali (Node):
```
npx web-push generate-vapid-keys
```
Simpan private key aman. Public key yang sama dipakai browser.

## Setelah push
Jalankan **Deploy Dev** (frontend rebuild + composer sudah include minishlink/web-push).

## Cara uji
1. Buka app (lebih baik mode terpasang / Add to Home Screen di HP)
2. Login → menu Notifikasi
3. Ketuk **Aktifkan push** → izinkan
4. Picu notifikasi (mis. pengumuman baru / konfirmasi bayar)
5. Push harus muncul di sistem HP/browser

## Cron retry
Endpoint `tugas/5menit` sudah memanggil `PushService::ulangGagal` untuk pending yang gagal.
