import { USE_MOCK } from '../config/dataSource.js'
import { api } from '../api/http.js'

const mockList = [
  {
    id: 1,
    waktu: '2026-10-06 10:00:00',
    pelaku: 'Budi',
    jabatan: 'Ketua RT',
    aksi: 'tambah_keluarga',
    objek: 'keluarga',
    objek_id: '3',
    sesudah: { nama: 'Ahmad', alamat: 'A-1' },
  },
  {
    id: 2,
    waktu: '2026-10-05 15:20:00',
    pelaku: 'Sistem',
    aksi: 'reset_pin_warga',
    objek: 'users',
    objek_id: '5',
  },
]

export async function listAktivitas(params = {}) {
  if (USE_MOCK.aktivitas) {
    let data = mockList
    const b = params.bulan != null ? Number(params.bulan) : 0
    const t = params.tahun != null ? String(params.tahun) : ''
    if (b >= 1 && b <= 12 && t) {
      const prefix = `${t}-${String(b).padStart(2, '0')}`
      data = mockList.filter((x) => String(x.waktu || '').startsWith(prefix))
    }
    return { ok: true, data }
  }
  try {
    const q = new URLSearchParams()
    if (params.bulan != null && params.bulan !== '') q.set('bulan', String(params.bulan))
    if (params.tahun != null && params.tahun !== '') q.set('tahun', String(params.tahun))
    if (params.limit != null) q.set('limit', String(params.limit))
    const qs = q.toString()
    const res = await api('/aktivitas' + (qs ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function detailAktivitas(id) {
  if (USE_MOCK.aktivitas) {
    const row = mockList.find((x) => x.id === Number(id))
    return row ? { ok: true, data: row } : { ok: false, error: 'Tidak ditemukan' }
  }
  try {
    const res = await api('/aktivitas/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
