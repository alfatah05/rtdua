import { api } from '../api/http.js'

export async function listPengurus() {
  try {
    const res = await api('/pengurus')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function angkatPengurus(payload) {
  try {
    const res = await api('/pengurus', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function updatePengurus(id, payload) {
  try {
    const res = await api('/pengurus/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
