import { api } from '../api/http.js'

export async function listCatatan({ page, per_page = 50 } = {}) {
  try {
    const q = new URLSearchParams()
    // Backend baca 'hal' (hindari bentrok query 'page' di CI)
    if (page != null) q.set('hal', String(page))
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
    const raw = res.data
    const meta = (raw && raw._meta) || res.meta || {}
    const total_pages = meta.total_pages ?? null
    let row = raw
    if (row && typeof row === 'object' && row._meta) {
      row = { ...row }
      delete row._meta
    }
    return {
      ok: true,
      data: row,
      total_pages,
      total: meta.total ?? null,
    }
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
