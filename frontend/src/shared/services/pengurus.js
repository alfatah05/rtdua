import { api } from '../api/http.js'

export async function listPengurus() {
  try {
    const res = await api('/pengurus')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
