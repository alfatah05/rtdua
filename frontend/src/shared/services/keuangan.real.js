import { api } from '../api/http.js'

// ---- Pengurus ----

export async function daftarIuran(params = {}) {
  try {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') qs.set(k, String(v))
    })
    const res = await api('/keuangan/iuran' + (qs.toString() ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ringkasanKeluarga(keluargaId) {
  try {
    const res = await api('/keuangan/keluarga/' + keluargaId)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function pratinjauAlokasi(keluargaId, nominal) {
  try {
    const res = await api('/keuangan/keluarga/' + keluargaId + '/pratinjau', {
      method: 'POST',
      body: { nominal },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function catatPembayaran(payload) {
  try {
    const res = await api('/keuangan/pembayaran', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalkanPembayaran(id, alasan) {
  try {
    const res = await api('/keuangan/pembayaran/' + id + '/batal', {
      method: 'POST',
      body: { alasan },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listPermintaan(status = 'menunggu') {
  try {
    const res = await api('/keuangan/permintaan?status=' + encodeURIComponent(status))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function konfirmasiPermintaan(id, payload = {}) {
  try {
    const res = await api('/keuangan/permintaan/' + id + '/konfirmasi', {
      method: 'POST',
      body: payload,
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function tolakPermintaan(id, alasan) {
  try {
    await api('/keuangan/permintaan/' + id + '/tolak', {
      method: 'POST',
      body: { alasan },
    })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function saldoKas() {
  try {
    const res = await api('/keuangan/kas/saldo')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listKas(params = {}) {
  try {
    const qs = new URLSearchParams()
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== '') qs.set(k, String(v))
    })
    const res = await api('/keuangan/kas' + (qs.toString() ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function tambahKas(payload) {
  try {
    const res = await api('/keuangan/kas', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalkanKas(id, alasan) {
  try {
    const res = await api('/keuangan/kas/' + id + '/batal', {
      method: 'POST',
      body: { alasan },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listIuranKhusus() {
  try {
    const res = await api('/keuangan/iuran-khusus')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function buatIuranKhusus(payload) {
  try {
    const res = await api('/keuangan/iuran-khusus', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function laporan(bulan) {
  try {
    const res = await api('/keuangan/laporan?bulan=' + encodeURIComponent(bulan || ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ubahNominal(payload) {
  try {
    const res = await api('/keuangan/nominal', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function pastikanTagihanKas(periode) {
  try {
    const res = await api('/keuangan/pastikan-tagihan-kas', {
      method: 'POST',
      body: { periode },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

// ---- Portal warga ----

export async function portalRingkasan() {
  try {
    const res = await api('/portal/keuangan')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalAjukanTransfer(payload) {
  try {
    const res = await api('/portal/keuangan/transfer', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalStatusPermintaan() {
  try {
    const res = await api('/portal/keuangan/permintaan')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function portalKas(bulan) {
  try {
    const q = bulan ? ('?bulan=' + encodeURIComponent(bulan)) : ''
    const res = await api('/portal/keuangan/kas' + q)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
