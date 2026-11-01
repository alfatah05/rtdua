import { api } from '../api/http.js'

export async function listReward() {
  try {
    const res = await api('/reward')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function detailReward(id) {
  try {
    const res = await api('/reward/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function buatReward(payload) {
  try {
    const res = await api('/reward', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ubahReward(id, payload) {
  try {
    const res = await api('/reward/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function hapusReward(id) {
  try {
    await api('/reward/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function undiReward(id) {
  try {
    const res = await api('/reward/' + id + '/undi', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function resetSiklusReward(id) {
  try {
    const res = await api('/reward/' + id + '/reset-siklus', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalBatchReward(batchId) {
  try {
    const res = await api('/reward/batch/' + batchId + '/batal', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function klaimReward(pemenangId) {
  try {
    const res = await api('/reward/pemenang/' + pemenangId + '/klaim', { method: 'POST', body: {} })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
