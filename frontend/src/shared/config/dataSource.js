/**
 * Saklar sumber data per modul.
 * true  = mock | false = API real
 *
 * Stage 10: auth/pengaturan/warga/aktivitas → real
 * Stage 11: keuangan → real
 * Stage 12: notifikasi → real
 */
export const USE_MOCK = {
  auth: false,
  pengaturan: false,
  warga: false,
  keuangan: false,
  ronda: true,
  konten: true,
  notifikasi: false,
  aktivitas: false,
}
