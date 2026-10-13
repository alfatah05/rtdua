import { USE_MOCK } from '../config.js'
import * as real from './keuangan.real.js'

export function daftarIuran(params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.daftarIuran(params)
}
export function ringkasanKeluarga(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { total: 0, tagihan: [] } }) : real.ringkasanKeluarga(id)
}
export function pratinjauAlokasi(id, nominal) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.pratinjauAlokasi(id, nominal)
}
export function catatPembayaran(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.catatPembayaran(payload)
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
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { saldo: 0 } }) : real.saldoKas()
}
export function listKas(params) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { items: [], saldo: 0 } }) : real.listKas(params)
}
export function tambahKas(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.tambahKas(payload)
}
export function batalkanKas(id, alasan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.batalkanKas(id, alasan)
}
export function listIuranKhusus() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listIuranKhusus()
}
export function buatIuranKhusus(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.buatIuranKhusus(payload)
}
export function laporan(bulan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.laporan(bulan)
}
export function ubahNominal(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahNominal(payload)
}
export function pastikanTagihanKas(periode) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { dibuat: 0 } }) : real.pastikanTagihanKas(periode)
}
export function portalRingkasan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { total: 0, tagihan: [] } }) : real.portalRingkasan()
}
export function portalAjukanTransfer(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.portalAjukanTransfer(payload)
}
export function portalStatusPermintaan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalStatusPermintaan()
}
export function portalKas(bulan) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { saldo: 0, items: [] } }) : real.portalKas(bulan)
}
export function listRekening() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.listRekening()
}
export function createRekening(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.createRekening(payload)
}
export function updateRekening(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: {} }) : real.updateRekening(id, payload)
}
export function deleteRekening(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.deleteRekening(id)
}
export function portalListRekening() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalListRekening()
}
export function listIuranKomponen() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { items: [], total: 0 } }) : real.listIuranKomponen()
}
export function buatIuranKomponen(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1, total: 0 } }) : real.buatIuranKomponen(payload)
}
export function ubahIuranKomponen(id, payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { total: 0 } }) : real.ubahIuranKomponen(id, payload)
}
export function hapusIuranKomponen(id) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { total: 0 } }) : real.hapusIuranKomponen(id)
}
export function terapkanKomponenBulanIni() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { total: 0, keluarga_terdampak: 0 } }) : real.terapkanKomponenBulanIni()
}
