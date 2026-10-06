import { api } from '../api/http.js'

export async function getPengaturan() {
  try {
    const res = await api('/pengaturan')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getBlok() {
  try {
    const res = await api('/blok')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
