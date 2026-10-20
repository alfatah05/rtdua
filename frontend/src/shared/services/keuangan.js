import { USE_MOCK } from '../config/dataSource.js'
import * as real from './keuangan.real.js'
import { mockTagihan, mockKasSaldo } from '../mock/keuangan.js'
import { alokasiPembayaran } from '../utils/alokasiPembayaran.js'

export function daftarIuran(params) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: mockTagihan })
    : real.daftarIuran(params)
}

export function ringkasanKeluarga(keluargaId) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.ringkasanKeluarga(keluargaId)
}

export function pratinjauAlokasi(keluargaId, nominal) {
  if (USE_MOCK.keuangan) {
    return Promise.resolve({ ok: true, data: alokasiPembayaran([], nominal) })
  }
  return real.pratinjauAlokasi(keluargaId, nominal)
}

export function catatPembayaran(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.catatPembayaran(payload)
}

export function batalkanPembayaran(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.batalkanPembayaran(id, alasan)
}

export function listPermintaan(status) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listPermintaan(status)
}

export function konfirmasiPermintaan(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.konfirmasiPermintaan(id, payload)
}

export function tolakPermintaan(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.tolakPermintaan(id, alasan)
}

export function saldoKas() {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { saldo: mockKasSaldo } })
    : real.saldoKas()
}

export function listKas(params) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { saldo: mockKasSaldo, items: [], pos: [] } })
    : real.listKas(params)
}

export function tambahKas(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.tambahKas(payload)
}

export function batalkanKas(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.batalkanKas(id, alasan)
}

export function listKasPos() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listKasPos()
}
export function buatKasPos(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.buatKasPos(payload)
}
export function ubahKasPos(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahKasPos(id, payload)
}
export function nonaktifKasPos(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.nonaktifKasPos(id)
}
export function alokasiKasPos(payload) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { keluar_id: 1, masuk_id: 2 } })
    : real.alokasiKasPos(payload)
}

export function listIuranKhusus() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listIuranKhusus()
}
export function buatIuranKhusus(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.buatIuranKhusus(payload)
}
export function buatTagihanKhususKeluarga(keluargaId, payload) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { id: 1 } })
    : real.buatTagihanKhususKeluarga(keluargaId, payload)
}
export function ubahTagihanKhusus(tagihanId, payload) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true })
    : real.ubahTagihanKhusus(tagihanId, payload)
}
export function hapusTagihanKhusus(tagihanId, alasan) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true })
    : real.hapusTagihanKhusus(tagihanId, alasan)
}
export function ubahIuranKhusus(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahIuranKhusus(id, payload)
}
export function batalkanIuranKhusus(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.batalkanIuranKhusus(id, alasan)
}

export function laporan(bulan) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { bulan, saldo_keseluruhan: mockKasSaldo, total_masuk: 0, total_keluar: 0, per_kategori: {}, transaksi: [], rekap_iuran: [] } })
    : real.laporan(bulan)
}

export function ubahNominal(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahNominal(payload)
}
export function pastikanTagihanKas(periode) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { dibuat: 0 } }) : real.pastikanTagihanKas(periode)
}

export function portalRingkasan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.portalRingkasan()
}
export function portalAjukanTransfer(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.portalAjukanTransfer(payload)
}
export function portalStatusPermintaan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalStatusPermintaan()
}
export function portalKas(bulan) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { saldo: mockKasSaldo, items: [] } })
    : real.portalKas(bulan)
}

export function listRekening() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listRekening()
}
export function createRekening(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.createRekening(payload)
}
export function updateRekening(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.updateRekening(id, payload)
}
export function deleteRekening(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.deleteRekening(id)
}
export function portalListRekening() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalListRekening()
}

export function listIuranKomponen() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listIuranKomponen()
}
export function buatIuranKomponen(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.buatIuranKomponen(payload)
}
export function ubahIuranKomponen(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahIuranKomponen(id, payload)
}
export function hapusIuranKomponen(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.hapusIuranKomponen(id)
}
export function terapkanKomponenBulanIni() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { dibuat: 0 } }) : real.terapkanKomponenBulanIni()
}

// ---- Sub pos (T4) ----
export function listSubPos(params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listSubPos(params || {})
}
export function detailSubPos(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: null }) : real.detailSubPos(id)
}
export function listSubPosMutasi(id, params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { sub_pos: null, items: [] } }) : real.listSubPosMutasi(id, params || {})
}
export function subPosMasuk(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.subPosMasuk(id, payload)
}
export function subPosKeluar(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.subPosKeluar(id, payload)
}
export function subPosAlokasi(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.subPosAlokasi(id, payload)
}
export function subPosBatalMutasi(mutasiId, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.subPosBatalMutasi(mutasiId, alasan)
}
