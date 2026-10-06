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

export async function updateAplikasi(payload) {
  try {
    const res = await api('/pengaturan/aplikasi', { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function updateWarga(payload) {
  try {
    const res = await api('/pengaturan/warga', { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function tambahBlok(nama) {
  try {
    const res = await api('/blok', { method: 'POST', body: { nama } })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
