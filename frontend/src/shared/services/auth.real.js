import { api } from '../api/http.js'

export async function loginWarga(username, pin) {
  try {
    const res = await api('/auth/login', { method: 'POST', body: { username, pin } })
    return { ok: true, mustChange: !!res.data?.harus_ganti_kredensial, user: res.data?.user }
  } catch (e) {
    return { ok: false, error: e.message || 'Login gagal' }
  }
}

export async function loginPengurus(username, password) {
  try {
    const res = await api('/auth/login', { method: 'POST', body: { username, password } })
    return { ok: true, user: res.data?.user, mustChange: !!res.data?.harus_ganti_kredensial }
  } catch (e) {
    return { ok: false, error: e.message || 'Login gagal' }
  }
}

export async function logout() {
  try { await api('/auth/logout', { method: 'POST' }) } catch {}
  return { ok: true }
}

export async function me() {
  try {
    const res = await api('/auth/me')
    return { ok: true, user: res.data?.user || null }
  } catch {
    return { ok: true, user: null }
  }
}

export async function changeCredential(payload) {
  try {
    await api('/auth/change-credential', { method: 'POST', body: payload })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message || 'Gagal mengganti kredensial' }
  }
}
