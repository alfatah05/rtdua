import { api } from '../api/http.js'

export async function listKeluarga(params = {}) {
  try {
    const q = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') q.set(k, v)
    })
    const res = await api('/warga?' + q.toString())
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getKeluarga(id, opts = {}) {
  try {
    const q = opts.bukaNik ? '?buka_nik=1' : ''
    const res = await api('/warga/' + id + q)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function createKeluarga(payload) {
  try {
    const res = await api('/warga', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function updateKeluarga(id, payload) {
  try {
    const res = await api('/warga/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function hapusKeluarga(id, payload = {}) {
  try {
    await api('/warga/' + id + '/hapus', { method: 'POST', body: payload })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function updateAnggota(id, payload) {
  try {
    const res = await api('/anggota/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function tambahAnggota(keluargaId, payload) {
  try {
    const res = await api('/warga/' + keluargaId + '/anggota', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function setStatusAnggota(anggotaId, payload) {
  try {
    const res = await api('/anggota/' + anggotaId + '/status', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function hapusAnggota(anggotaId, payload = {}) {
  try {
    await api('/anggota/' + anggotaId + '/hapus', { method: 'POST', body: payload })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function resetPin(id) {
  try {
    await api('/warga/' + id + '/reset-pin', { method: 'POST', body: {} })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function pindahKeluarga(id, payload) {
  try {
    await api('/warga/' + id + '/pindah', { method: 'POST', body: payload })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listPortalWarga() {
  try {
    const res = await api('/portal/warga')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalDetailKeluarga(id) {
  try {
    const res = await api('/portal/warga/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalKeluargaSaya() {
  try {
    const res = await api('/portal/keluarga')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalTambahAnggota(payload) {
  try {
    const res = await api('/portal/keluarga/anggota', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalUpdateAnggota(id, payload) {
  try {
    const res = await api('/portal/keluarga/anggota/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function cariAnggota(q, limit = 40) {
  try {
    const params = new URLSearchParams()
    if (q) params.set('q', q)
    params.set('limit', String(limit))
    const res = await api('/warga/cari-anggota?' + params.toString())
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
