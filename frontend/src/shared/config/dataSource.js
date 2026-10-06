/**
 * Saklar sumber data per modul.
 * true  = mock | false = API real
 *
 * Setelah backend Stage 10 deploy + setup + login API jalan:
 * set auth/pengaturan/warga/aktivitas ke false.
 */
export const USE_MOCK = {
  auth: true,
  pengaturan: true,
  warga: true,
  keuangan: true,
  ronda: true,
  konten: true,
  notifikasi: true,
  aktivitas: true,
}
