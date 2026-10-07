import { USE_MOCK } from '../config/dataSource.js'
import * as real from './keuangan.real.js'
import { mockTagihan, mockKasSaldo } from '../mock/keuangan.js'
import { alokasiPembayaran } from '../utils/alokasiPembayaran.js'

const impl = () => (USE_MOCK.keuangan ? null : real)

// ---- Mock fallbacks (UI Stage 1–8) ----
async function mockDaftarIuran() {
  return {
    ok: true,
    data: [
      { keluarga_id: 1, nama: 'Budi', alamat: 'AB2-22', total: 50000, status: 'belum_lunas', menunggak: false },
      { keluarga_id: 2, nama: 'Siti', alamat: 'AB2-22a', total: 0, status: 'lunas', menunggak: false },
      { keluarga_id: 3, nama: 'Andi', alamat: 'AB1-5', total: 70000, status: 'menunggak', menunggak: true },
    ],
  }
}

async function mockRingkasan(keluargaId) {
  const tagihan = mockTagihan.filter((t) => t.keluarga_id === Number(keluargaId))
  const total = tagihan.reduce((s, t) => s + (t.sisa || 0), 0)
  return {
    ok: true,
    data: {
      keluarga_id: Number(keluargaId),
      total,
      status: total === 0 ? 'lunas' : 'belum_lunas',
      menunggak: false,
      tagihan,
      pembayaran: [],
    },
  }
}

export function daftarIuran(params) {
  return USE_MOCK.keuangan ? mockDaftarIuran() : real.daftarIuran(params)
}
export function ringkasanKeluarga(id) {
  return USE_MOCK.keuangan ? mockRingkasan(id) : real.ringkasanKeluarga(id)
}
export function pratinjauAlokasi(keluargaId, nominal) {
  if (USE_MOCK.keuangan) {
    const tagihan = mockTagihan.filter((t) => t.keluarga_id === Number(keluargaId))
    return Promise.resolve({ ok: true, data: alokasiPembayaran(tagihan, nominal) })
  }
  return real.pratinjauAlokasi(keluargaId, nominal)
}
export function catatPembayaran(payload) {
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { id: 1 } })
    : real.catatPembayaran(payload)
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
    ? Promise.resolve({ ok: true, data: { saldo: mockKasSaldo, items: [] } })
    : real.listKas(params)
}
export function tambahKas(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.tambahKas(payload)
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
  return USE_MOCK.keuangan
    ? Promise.resolve({ ok: true, data: { bulan, total_masuk: 0, total_keluar: 0, transaksi: [] } })
    : real.laporan(bulan)
}
export function ubahNominal(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true }) : real.ubahNominal(payload)
}
export function pastikanTagihanKas(periode) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { dibuat: 0 } }) : real.pastikanTagihanKas(periode)
}
export function portalRingkasan() {
  return USE_MOCK.keuangan ? mockRingkasan(1) : real.portalRingkasan()
}
export function portalAjukanTransfer(payload) {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: { id: 1 } }) : real.portalAjukanTransfer(payload)
}
export function portalStatusPermintaan() {
  return USE_MOCK.keuangan ? Promise.resolve({ ok: true, data: [] }) : real.portalStatusPermintaan()
}

// suppress unused
void impl
