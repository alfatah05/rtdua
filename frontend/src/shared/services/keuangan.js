import { USE_MOCK } from '../config/dataSource.js'
import * as real from './keuangan.real.js'

function mockRingkasan() {
  return Promise.resolve({
    ok: true,
    data: { total: 0, status: 'lunas', menunggak: false, tagihan: [], pembayaran: [] },
  })
}

export function ringkasanKeluarga(keluargaId) {
  return USE_MOCK.keuangan ? mockRingkasan() : real.ringkasanKeluarga(keluargaId)
}
export function pratinjauAlokasi(keluargaId, nominal) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { potongan: [], sisaBayar: nominal } })
    : real.pratinjauAlokasi(keluargaId, nominal)
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
export function catatPembayaran(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.catatPembayaran(payload)
}
export function daftarIuran(params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.daftarIuran(params)
}
export function saldoKas() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { saldo: 0 } }) : real.saldoKas()
}
export function listKas(params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { items: [] } }) : real.listKas(params)
}
export function tambahKas(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.tambahKas(payload)
}
export function batalkanKas(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.batalkanKas(id, alasan)
}
export function portalRingkasan() {
  return USE_MOCK.keuangan ? mockRingkasan() : real.portalRingkasan()
}
export function portalAjukanTransfer(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.portalAjukanTransfer(payload)
}
export function portalStatusPermintaan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalStatusPermintaan()
}
export function portalKas(bulan) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { saldo: 0, items: [] } })
    : real.portalKas(bulan)
}
