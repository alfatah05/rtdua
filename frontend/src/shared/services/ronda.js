import { api } from '../api/http.js'
import { USE_MOCK } from '../config/dataSource.js'

export async function kalenderRonda(bulan) {
  if (USE_MOCK.ronda) return { ok: true, data: [] }
  try {
    const res = await api('/ronda/kalender?bulan=' + encodeURIComponent(bulan || ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function malamIni() {
  if (USE_MOCK.ronda) return { ok: true, data: null }
  try {
    const res = await api('/ronda/malam-ini')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function malamTerdekat() {
  if (USE_MOCK.ronda) return { ok: true, data: null }
  try {
    const res = await api('/ronda/terdekat')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function detailMalam(tanggal) {
  if (USE_MOCK.ronda) return { ok: false, error: 'mock' }
  try {
    const res = await api('/ronda/malam/' + tanggal)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listJadwalTetap() {
  try {
    const res = await api('/ronda/jadwal-tetap')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function simpanJadwalTetap(payload) {
  try {
    const res = await api('/ronda/jadwal-tetap', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listJadwalKhusus() {
  try {
    const res = await api('/ronda/jadwal-khusus')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function simpanJadwalKhusus(payload) {
  try {
    const res = await api('/ronda/jadwal-khusus', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function generateRonda(periode) {
  try {
    const res = await api('/ronda/generate', { method: 'POST', body: { periode } })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** Buat malam kosong dari hari + periode (tanpa assign KK). */
export async function buatSlotKosong(payload = {}) {
  try {
    const res = await api('/ronda/buat-slot', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** Bagi KK aktif ke hari tetap (rotasi), generate malam sesuai durasi. */
export async function isiOtomatisRonda(payload = {}) {
  try {
    const res = await api('/ronda/isi-otomatis', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function absenManual(malamId, keluargaId) {
  try {
    await api('/ronda/absen-manual', {
      method: 'POST',
      body: { malam_id: malamId, keluarga_id: keluargaId },
    })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function absenWarga(malamId, fotoPath) {
  try {
    const res = await api('/ronda/absen', {
      method: 'POST',
      body: { malam_id: malamId, foto: fotoPath },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function gantiKeluargaMalam(malamId, keluargaIds) {
  try {
    const res = await api('/ronda/malam/' + malamId + '/ganti-keluarga', {
      method: 'POST',
      body: { keluarga_ids: keluargaIds },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalkanAbsen(absenId) {
  try {
    await api('/ronda/absen/' + absenId + '/batal', { method: 'POST', body: {} })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function terbitkanDendaRonda(payload = {}) {
  try {
    const res = await api('/ronda/terbitkan-denda', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
