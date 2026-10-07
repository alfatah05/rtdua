/**
 * Saklar sumber data per modul.
 * true  = mock | false = API real
 *
 * Setelah backend Stage 10 deploy + setup + login API jalan:
 * set auth/pengaturan/warga/aktivitas ke false.
 */
export const USE_MOCK = {
  auth: false,
  pengaturan: false,
  warga: false,
  keuangan: true,
  ronda: true,
  konten: true,
  notifikasi: true,
  aktivitas: false,
}
