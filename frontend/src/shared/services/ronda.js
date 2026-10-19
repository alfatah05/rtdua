import { api } from '../api/http.js'
import { USE_MOCK } from '../config/dataSource.js'

export async function kalenderRonda(bulan) {
  if (USE_MOCK.ronda) return { ok: true, data: [] }
  try {
    const q = bulan ? ('?bulan=' + encodeURIComponent(bulan)) : ''
    const res = await api('/ronda/kalender' + q)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function malamIni() {
  try {
    const res = await api('/ronda/malam-ini')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function malamTerdekat(keluargaId) {
  try {
    const q = keluargaId ? ('?keluargaId=' + encodeURIComponent(keluargaId)) : ''
    const res = await api('/ronda/terdekat' + q)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function detailMalam(tanggal) {
  try {
    const res = await api('/ronda/malam/' + encodeURIComponent(tanggal))
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

export async function generateBulan(payload) {
  try {
    const res = await api('/ronda/generate', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function absenManual(payload) {
  try {
    const res = await api('/ronda/absen-manual', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function absenWarga(payload) {
  try {
    const res = await api('/ronda/absen', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function gantiKeluargaMalam(malamId, payload) {
  try {
    const res = await api('/ronda/malam/' + malamId + '/ganti-keluarga', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalkanAbsen(id) {
  try {
    const res = await api('/ronda/absen/' + id + '/batal', { method: 'POST' })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function terbitkanDenda(payload) {
  try {
    const res = await api('/ronda/terbitkan-denda', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** List card template (minggu × hari). */
export async function listTemplateCards(opts = {}) {
  try {
    const res = await api('/ronda/template', opts)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** Buat/update card template dari hari + periode. */
export async function buatTemplateCards(payload) {
  try {
    const res = await api('/ronda/template', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** Simpan keluarga di satu card template. */
export async function simpanTemplateKeluarga(cardId, keluargaIds) {
  try {
    const res = await api('/ronda/template/' + cardId + '/keluarga', {
      method: 'POST',
      body: { keluarga_ids: keluargaIds },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

/** Ubah nama group card template (tidak mengubah daftar keluarga). */
export async function ubahNamaGroupCard(cardId, namaGroup) {
  try {
    const res = await api('/ronda/template/' + cardId + '/nama', {
      method: 'PUT',
      body: { nama_group: namaGroup },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
