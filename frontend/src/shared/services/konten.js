import { api } from '../api/http.js'
import { USE_MOCK } from '../config/dataSource.js'

export async function listPengumuman({ beranda = false } = {}) {
  if (USE_MOCK.konten) return { ok: true, data: [] }
  try {
    const res = await api('/pengumuman' + (beranda ? '?beranda=1' : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function detailPengumuman(id) {
  if (USE_MOCK.konten) return { ok: false, error: 'mock' }
  try {
    const res = await api('/pengumuman/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function buatPengumuman(payload) {
  try {
    const res = await api('/pengumuman', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function ubahPengumuman(id, payload) {
  try {
    const res = await api('/pengumuman/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function hapusPengumuman(id) {
  try {
    await api('/pengumuman/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listProgram() {
  if (USE_MOCK.konten) return { ok: true, data: [] }
  try {
    const res = await api('/program')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function detailProgram(id) {
  try {
    const res = await api('/program/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function buatProgram(payload) {
  try {
    const res = await api('/program', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function ubahProgram(id, payload) {
  try {
    const res = await api('/program/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function hapusProgram(id) {
  try {
    await api('/program/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listAlbum() {
  if (USE_MOCK.konten) return { ok: true, data: [] }
  try {
    const res = await api('/galeri')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function detailAlbum(id) {
  try {
    const res = await api('/galeri/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function buatAlbum(payload) {
  try {
    const res = await api('/galeri', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function hapusAlbum(id) {
  try {
    await api('/galeri/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
