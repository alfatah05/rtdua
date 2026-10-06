import { USE_MOCK } from '../config/dataSource.js'
import { api } from '../api/http.js'

const mockList = [
  { id: 1, waktu: '2026-10-06 10:00:00', pelaku: 'Budi (Ketua RT)', aksi: 'tambah_keluarga', objek: 'keluarga', objek_id: '3' },
  { id: 2, waktu: '2026-10-05 15:20:00', pelaku: 'Sistem', aksi: 'reset_pin', objek: 'users', objek_id: '5' },
]

export async function listAktivitas(params = {}) {
  if (USE_MOCK.aktivitas) {
    return { ok: true, data: mockList }
  }
  try {
    const qs = new URLSearchParams(params).toString()
    const res = await api('/aktivitas' + (qs ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
