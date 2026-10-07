import { api } from '../api/http.js'

export async function listNotifikasi({ limit = 50, offset = 0 } = {}) {
  try {
    const q = new URLSearchParams({ limit: String(limit), offset: String(offset) })
    const res = await api('/notifikasi?' + q.toString())
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function badgeNotifikasi() {
  try {
    const res = await api('/notifikasi/badge')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message, data: { belum_dibaca: 0 } }
  }
}

export async function bacaNotifikasi(id) {
  try {
    await api('/notifikasi/' + id + '/baca', { method: 'POST', body: {} })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function bacaSemuaNotifikasi() {
  try {
    const res = await api('/notifikasi/baca-semua', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function getVapidPublic() {
  try {
    const res = await api('/push/vapid-public')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function subscribePush(subscription, perangkat) {
  try {
    const res = await api('/push/subscribe', {
      method: 'POST',
      body: { subscription, perangkat },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function unsubscribePush(endpoint) {
  try {
    await api('/push/unsubscribe', {
      method: 'POST',
      body: endpoint ? { endpoint } : {},
    })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
