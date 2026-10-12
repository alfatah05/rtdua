# Icon PWA — upload manual

Letakkan file berikut di folder ini:

| File | Keterangan |
|---|---|
| `icon-app.png` | Minimal **192×192**, ideal **512×512**. Dipakai manifest (`any` + `maskable`), favicon, apple-touch-icon, dan notifikasi push. |

**Path di web (setelah build/deploy):** `/icons/icon-app.png`

Catatan:
- Format PNG (boleh transparan).
- Satu file cukup; sizes 192 & 512 di manifest mengarah ke file yang sama.
- Setelah upload, commit & deploy ulang agar PWA installable.
