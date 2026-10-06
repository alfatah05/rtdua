import { api } from '../api/http.js'

export async function listKeluarga(params = {}) {
  try {
    const qs = new URLSearchParams(params).toString()
    const res = await api('/warga' + (qs ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getKeluarga(id) {
  try {
    const res = await api('/warga/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
