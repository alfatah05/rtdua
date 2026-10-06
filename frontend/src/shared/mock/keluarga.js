/**
 * Data dummy konsisten untuk Stage 1–9.
 * Nama fiktif. NIK dummy jelas palsu (bukan data warga asli).
 */
export const mockKeluarga = [
  {
    id: 1,
    blok: 'AB2', nomor: '22', akhiran: '',
    alamat: 'Blok AB2 No. 22', telepon: '081200000001',
    status: 'aktif', mulai_periode: '2026-01',
    data_belum_lengkap: false, belum_ganti_pin: false, mulai_bulan_depan: false,
    kepala: 'Budi Santoso',
    anggota: [
      { id: 1, nama: 'Budi Santoso', hubungan: 'Kepala keluarga', tanggal_lahir: '1980-05-12', pekerjaan: 'Karyawan', status: 'aktif', nik_dummy: '0000000000000001' },
      { id: 2, nama: 'Siti Aminah', hubungan: 'Istri', tanggal_lahir: '1985-03-20', pekerjaan: 'Ibu rumah tangga', status: 'aktif', nik_dummy: '0000000000000002' },
    ],
  },
  {
    id: 2,
    blok: 'AB2', nomor: '22', akhiran: 'a',
    alamat: 'Blok AB2 No. 22a', telepon: '081200000002',
    status: 'aktif', mulai_periode: '2026-01',
    data_belum_lengkap: false, belum_ganti_pin: false, mulai_bulan_depan: false,
    kepala: 'Rina Marlina',
    anggota: [
      { id: 3, nama: 'Rina Marlina', hubungan: 'Kepala keluarga', tanggal_lahir: '1990-08-01', pekerjaan: 'Wiraswasta', status: 'aktif', nik_dummy: '0000000000000003' },
    ],
  },
  {
    id: 3,
    blok: 'AB1', nomor: '05', akhiran: '',
    alamat: 'Blok AB1 No. 05', telepon: null,
    status: 'aktif', mulai_periode: '2026-01',
    data_belum_lengkap: true, belum_ganti_pin: false, mulai_bulan_depan: false,
    kepala: 'Andi Wijaya',
    anggota: [
      { id: 4, nama: 'Andi Wijaya', hubungan: 'Kepala keluarga', tanggal_lahir: null, pekerjaan: 'Pegawai', status: 'aktif', nik_dummy: null },
    ],
  },
  {
    id: 4,
    blok: 'AB11', nomor: '12', akhiran: '',
    alamat: 'Blok AB11 No. 12', telepon: '081200000004',
    status: 'aktif', mulai_periode: '2026-01',
    data_belum_lengkap: false, belum_ganti_pin: false, mulai_bulan_depan: false,
    kepala: 'Joko Prasetyo',
    anggota: [
      { id: 5, nama: 'Joko Prasetyo', hubungan: 'Kepala keluarga', tanggal_lahir: '1975-11-11', pekerjaan: 'Pedagang', status: 'aktif', nik_dummy: '0000000000000005' },
    ],
  },
  {
    id: 5,
    blok: 'AB12', nomor: '03', akhiran: '',
    alamat: 'Blok AB12 No. 03', telepon: '081200000005',
    status: 'aktif', mulai_periode: '2026-02',
    data_belum_lengkap: false, belum_ganti_pin: true, mulai_bulan_depan: false,
    kepala: 'Dewi Lestari',
    anggota: [
      { id: 6, nama: 'Dewi Lestari', hubungan: 'Kepala keluarga', tanggal_lahir: '1988-01-30', pekerjaan: 'Guru', status: 'aktif', nik_dummy: '0000000000000006' },
    ],
  },
  {
    id: 6,
    blok: 'AB1', nomor: '10', akhiran: '',
    alamat: 'Blok AB1 No. 10', telepon: '081200000006',
    status: 'aktif', mulai_periode: null,
    data_belum_lengkap: false, belum_ganti_pin: false, mulai_bulan_depan: true,
    kepala: 'Hendra Gunawan',
    anggota: [
      { id: 7, nama: 'Hendra Gunawan', hubungan: 'Kepala keluarga', tanggal_lahir: '1992-07-07', pekerjaan: 'Supir', status: 'aktif', nik_dummy: '0000000000000007' },
    ],
  },
  {
    id: 7,
    blok: 'AB2', nomor: '15', akhiran: '',
    alamat: 'Blok AB2 No. 15', telepon: '081200000007',
    status: 'pindah', mulai_periode: '2025-06',
    data_belum_lengkap: false, belum_ganti_pin: false, mulai_bulan_depan: false,
    kepala: 'Agus Salim',
    anggota: [
      { id: 8, nama: 'Agus Salim', hubungan: 'Kepala keluarga', tanggal_lahir: '1970-02-14', pekerjaan: '-', status: 'aktif', nik_dummy: '0000000000000008' },
    ],
  },
]

/** Username warga mock: blok-nomor(+akhiran), contoh AB2-22, AB2-22a */
export function usernameDariKeluarga(k) {
  return (k.blok + '-' + k.nomor + (k.akhiran || '')).toLowerCase()
}
