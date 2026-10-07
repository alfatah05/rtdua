import { api } from '../api/http.js'

export async function listKeluarga(params = {}) {
  try {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') qs.set(k, String(v))
    })
    const res = await api('/warga' + (qs.toString() ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getKeluarga(id, { bukaNik = false } = {}) {
  try {
    const res = await api('/warga/' + id + (bukaNik ? '?buka_nik=1' : ''))
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

export async function updateAnggota(id, payload) {
  try {
    const res = await api('/anggota/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
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
