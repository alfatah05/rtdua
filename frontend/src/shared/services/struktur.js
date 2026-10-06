import { USE_MOCK } from '../config/dataSource.js'
import { api } from '../api/http.js'

export async function getStruktur() {
  if (USE_MOCK.pengaturan) {
    return {
      ok: true,
      data: {
        ketua: { id: 1, nama: 'Budi Ketua', jabatan: 'Ketua RT' },
        pengurus: [{ id: 2, nama: 'Ani Pengurus', jabatan: 'Bendahara' }],
      },
    }
  }
  try {
    const res = await api('/struktur')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getBantuan() {
  if (USE_MOCK.pengaturan) {
    return {
      ok: true,
      data: [{ id: 1, nama: 'Budi Ketua', jabatan: 'Ketua RT', nomor_hp: '081200000099' }],
    }
  }
  try {
    const res = await api('/bantuan')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
