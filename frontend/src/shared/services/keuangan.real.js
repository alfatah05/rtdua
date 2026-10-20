import { api } from '../api/http.js'

// ---- Pengurus ----

export async function daftarIuran(params = {}) {
  try {
    const q = new URLSearchParams()
    if (params.status) q.set('status', params.status)
    if (params.blok_id) q.set('blok_id', params.blok_id)
    const qs = q.toString()
    const res = await api('/keuangan/iuran' + (qs ? '?' + qs : ''))
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
      body: { alasan: alasan || '' },
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
    const res = await api('/keuangan/permintaan/' + id + '/tolak', {
      method: 'POST',
      body: { alasan: alasan || '' },
    })
    return { ok: true, data: res.data }
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
    const q = new URLSearchParams()
    if (params.bulan) q.set('bulan', params.bulan)
    if (params.tipe) q.set('tipe', params.tipe)
    if (params.kategori) q.set('kategori', params.kategori)
    if (params.kas_pos_id) q.set('kas_pos_id', params.kas_pos_id)
    const qs = q.toString()
    const res = await api('/keuangan/kas' + (qs ? '?' + qs : ''))
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
      body: { alasan: alasan || '' },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listKasPos() {
  try {
    const res = await api('/keuangan/kas-pos')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function buatKasPos(payload) {
  try {
    const res = await api('/keuangan/kas-pos', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ubahKasPos(id, payload) {
  try {
    const res = await api('/keuangan/kas-pos/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function nonaktifKasPos(id) {
  try {
    const res = await api('/keuangan/kas-pos/' + id + '/nonaktif', { method: 'POST' })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function alokasiKasPos(payload) {
  try {
    const res = await api('/keuangan/kas-pos/alokasi', { method: 'POST', body: payload })
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

export async function ubahIuranKhusus(id, payload) {
  try {
    const res = await api('/keuangan/iuran-khusus/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function batalkanIuranKhusus(id, alasan) {
  try {
    const res = await api('/keuangan/iuran-khusus/' + id + '/batal', {
      method: 'POST',
      body: { alasan: alasan || '' },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function buatTagihanKhususKeluarga(keluargaId, payload) {
  try {
    const res = await api('/keuangan/keluarga/' + keluargaId + '/tagihan-khusus', {
      method: 'POST',
      body: payload,
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ubahTagihanKhusus(tagihanId, payload) {
  try {
    const res = await api('/keuangan/tagihan-khusus/' + tagihanId, {
      method: 'PUT',
      body: payload,
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function hapusTagihanKhusus(tagihanId, alasan) {
  try {
    const res = await api('/keuangan/tagihan-khusus/' + tagihanId + '/hapus', {
      method: 'POST',
      body: { alasan: alasan || '' },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function laporan(bulan) {
  try {
    const q = bulan ? ('?bulan=' + encodeURIComponent(bulan)) : ''
    const res = await api('/keuangan/laporan' + q)
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
    const res = await api('/keuangan/pastikan-tagihan', {
      method: 'POST',
      body: { periode },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listIuranKomponen() {
  try {
    const res = await api('/keuangan/iuran-komponen')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function buatIuranKomponen(payload) {
  try {
    const res = await api('/keuangan/iuran-komponen', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function ubahIuranKomponen(id, payload) {
  try {
    const res = await api('/keuangan/iuran-komponen/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function hapusIuranKomponen(id) {
  try {
    const res = await api('/keuangan/iuran-komponen/' + id, { method: 'DELETE' })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function terapkanKomponenBulanIni() {
  try {
    const res = await api('/keuangan/iuran-komponen/terapkan', { method: 'POST' })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

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

export async function listRekening() {
  try {
    const res = await api('/keuangan/rekening')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function createRekening(payload) {
  try {
    const res = await api('/keuangan/rekening', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function updateRekening(id, payload) {
  try {
    const res = await api('/keuangan/rekening/' + id, { method: 'PUT', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function deleteRekening(id) {
  try {
    await api('/keuangan/rekening/' + id, { method: 'DELETE' })
    return { ok: true }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
export async function portalListRekening() {
  try {
    const res = await api('/portal/keuangan/rekening')
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

// ---- Sub pos (T4) ----

export async function listSubPos(params = {}) {
  try {
    const q = new URLSearchParams()
    if (params.aktif) q.set('aktif', params.aktif)
    const qs = q.toString()
    const res = await api('/keuangan/sub-pos' + (qs ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function detailSubPos(id) {
  try {
    const res = await api('/keuangan/sub-pos/' + id)
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function listSubPosMutasi(id, params = {}) {
  try {
    const q = new URLSearchParams()
    if (params.tipe) q.set('tipe', params.tipe)
    if (params.bulan) q.set('bulan', params.bulan)
    if (params.limit) q.set('limit', String(params.limit))
    const qs = q.toString()
    const res = await api('/keuangan/sub-pos/' + id + '/mutasi' + (qs ? '?' + qs : ''))
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function subPosMasuk(id, payload) {
  try {
    const res = await api('/keuangan/sub-pos/' + id + '/masuk', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function subPosKeluar(id, payload) {
  try {
    const res = await api('/keuangan/sub-pos/' + id + '/keluar', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function subPosAlokasi(id, payload) {
  try {
    const res = await api('/keuangan/sub-pos/' + id + '/alokasi', { method: 'POST', body: payload })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}

export async function subPosBatalMutasi(mutasiId, alasan) {
  try {
    const res = await api('/keuangan/sub-pos/mutasi/' + mutasiId + '/batal', {
      method: 'POST',
      body: { alasan: alasan || '' },
    })
    return { ok: true, data: res.data }
  } catch (e) {
    return { ok: false, error: e.message }
  }
}
