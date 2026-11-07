import { api } from '../api/http.js'

export async function listCatatan({ page, per_page = 50 } = {}) {
  try {
    // Path-based agar tidak tergantung query string (beberapa hosting hilangkan QS)
    // page undefined/null → fokus (0)
    const hal = page == null ? 0 : Number(page)
    const q = new URLSearchParams()
    q.set('per_page', String(per_page))
    q.set('hal', String(hal))
    const res = await api('/catatan/hal/' + encodeURIComponent(String(hal)) + '?' + q.toString(), {
      headers: {
        'X-Catatan-Hal': String(hal),
      },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function searchCatatan(q, { limit = 80, offset = 0 } = {}) {
  try {
    // POST body — query string di hosting ini sering tidak sampai ke PHP
    const res = await api('/catatan/search', {
      method: 'POST',
      body: {
        q: String(q || ''),
        limit: Number(limit) || 80,
        offset: Number(offset) || 0,
      },
    })
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
