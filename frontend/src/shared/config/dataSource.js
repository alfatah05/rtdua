/**
 * Saklar sumber data per modul.
 * true  = mock | false = API real
 *
 * Stage 10: auth/pengaturan/warga/aktivitas → real
 * Stage 11: keuangan → real
 * Stage 12: notifikasi → real
 * Stage 13: konten + ronda → real
 */
export const USE_MOCK = {
  auth: false,
  pengaturan: false,
  warga: false,
  keuangan: false,
  ronda: false,
  konten: false,
  notifikasi: false,
  aktivitas: false,
}
