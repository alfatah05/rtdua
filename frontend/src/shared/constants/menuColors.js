/**
 * Warna solid menu/quick-action — satu sumber kebenaran.
 * Diambil dari palette dashboard warga. Jangan invent warna per halaman.
 */
export const C = {
  green: '#22B863',
  purple: '#A855F7',
  amber: '#F59E0B',
  teal: '#14B8A6',
  cyan: '#06B6D4',
  sky: '#0EA5E9',
  blue: '#3B82F6',
  pink: '#EC4899',
  orange: '#F97316',
  red: '#DC2626',
  slate: '#64748B',
}

/** Mapping semantik → warna (pakai di semua page) */
export const MENU = {
  // Keuangan
  catatIuran: C.green,
  kasMasuk: C.green,
  kasKeluar: C.red,
  iuran: C.green,
  laporan: C.purple,
  iuranKhusus: C.amber,
  posKas: C.teal,
  subPos: C.cyan,
  tarifIuran: C.slate,
  rekening: C.blue,
  // Warga
  tambahKeluarga: C.cyan,
  scanKk: C.sky,
  ekspor: C.slate,
  filter: C.slate,
  // Ronda / lain
  ronda: C.blue,
  menu: C.slate,
  // Konten
  pengumuman: C.purple,
  program: C.purple,
  posyandu: C.orange,
  pengajian: C.purple,
  lingkungan: C.green,
  bulutangkis: C.sky,
  gowes: C.green,
  mancing: C.cyan,
  silat: C.red,
  senam: C.amber,
  talim: C.sky,
  phbi: C.purple,
  rw: C.slate,
  ambulance: C.red,
  bazis: C.green,
  reward: C.amber,
  galeri: C.pink,
  struktur: C.purple,
  pengaturan: C.slate,
  kelolaPengurus: C.slate,
}
