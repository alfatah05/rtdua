# PWA icons

File yang dipakai:

| File | Keterangan |
|---|---|
| `app-icon.png` | Minimal **192×192**, ideal **512×512**. Dipakai manifest (`any` + `maskable`), favicon, apple-touch-icon, dan notifikasi push. |

**Path di web (setelah build/deploy):** `/icons/app-icon.png`

Catatan:
- Format PNG (boleh transparan).
- Satu file cukup; sizes 192 & 512 di manifest mengarah ke file yang sama.
- Setelah upload, commit & deploy ulang agar PWA installable.
- Hosting **harus** melayani file ini sebagai `image/png` (bukan HTML challenge/interstitial).
