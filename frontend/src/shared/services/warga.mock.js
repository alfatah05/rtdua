import { mockKeluarga } from '../mock/keluarga.js'

export async function listKeluarga({ status = 'aktif', q = '' } = {}) {
  let list = mockKeluarga.filter((k) => (status ? k.status === status : true))
  if (q) {
    const s = q.toLowerCase()
    list = list.filter(
      (k) =>
        k.kepala.toLowerCase().includes(s) ||
        (k.blok + '-' + k.nomor + (k.akhiran || '')).toLowerCase().includes(s)
    )
  }
  return {
    ok: true,
    data: list.map((k) => ({
      id: k.id,
      nama: k.kepala,
      alamat: k.blok + '-' + k.nomor + (k.akhiran || ''),
      jumlah_anggota: k.anggota.filter((a) => a.status === 'aktif').length,
      data_belum_lengkap: k.data_belum_lengkap,
      belum_ganti_pin: k.belum_ganti_pin,
      mulai_bulan_depan: k.mulai_bulan_depan,
      status: k.status,
    })),
  }
}

export async function getKeluarga(id) {
  const k = mockKeluarga.find((x) => x.id === Number(id))
  if (!k) return { ok: false, error: 'Keluarga tidak ditemukan' }
  return { ok: true, data: k }
}
