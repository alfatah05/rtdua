import { api } from '../api/http.js'

export async function listCatatan({ page, per_page = 50 } = {}) {
  try {
    const q = new URLSearchParams()
    if (page != null) q.set('page', String(page))
    q.set('per_page', String(per_page))
    const res = await api('/catatan?' + q.toString())
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function searchCatatan(q, { limit = 80, offset = 0 } = {}) {
  try {
    const qs = new URLSearchParams({
      q: String(q || ''),
      limit: String(limit),
      offset: String(offset),
    })
    const res = await api('/catatan/search?' + qs.toString())
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function createCatatanRow() {
  try {
    const res = await api('/catatan', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function patchCatatanField(id, field, value) {
  try {
    const res = await api('/catatan/' + id, {
      method: 'PATCH',
      body: { field, value },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function deleteCatatanRow(id) {
  try {
    await api('/catatan/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
