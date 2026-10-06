/**
 * Saklar sumber data per modul.
 * true  = pakai mock (Stage 1–9 / cadangan)
 * false = panggil API real (diaktifkan bertahap di Stage 10+)
 *
 * Saat Stage 10: set modul yang sudah dihubungkan ke false.
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
