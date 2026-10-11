import { USE_MOCK } from '../config/dataSource.js'
import * as mock from './warga.mock.js'
import * as real from './warga.real.js'

const impl = () => (USE_MOCK.warga ? mock : real)

export function listKeluarga(params) {
  return impl().listKeluarga(params)
}
export function getKeluarga(id, opts) {
  return USE_MOCK.warga ? mock.getKeluarga(id) : real.getKeluarga(id, opts)
}
export function createKeluarga(payload) {
  return USE_MOCK.warga
    ? Promise.resolve({ ok: true, data: { id: 99, username: 'dummy', pin_awal: '123456' } })
    : real.createKeluarga(payload)
}
export function updateKeluarga(id, payload) {
  return USE_MOCK.warga ? Promise.resolve({ ok: true }) : real.updateKeluarga(id, payload)
}
export function updateAnggota(id, payload) {
  return USE_MOCK.warga ? Promise.resolve({ ok: true }) : real.updateAnggota(id, payload)
}
export function resetPin(id) {
  return USE_MOCK.warga ? Promise.resolve({ ok: true }) : real.resetPin(id)
}
export function pindahKeluarga(id, payload) {
  return USE_MOCK.warga ? Promise.resolve({ ok: true }) : real.pindahKeluarga(id, payload)
}
export function listPortalWarga() {
  return USE_MOCK.warga
    ? mock.listKeluarga({ status: 'aktif' }).then((r) => ({
        ok: r.ok,
        data: (r.data || []).map((k) => ({ id: k.id, nama: k.nama, alamat: k.alamat })),
      }))
    : real.listPortalWarga()
}

export function tambahAnggota(keluargaId, payload) {
  if (USE_MOCK.warga) return Promise.resolve({ ok: true, data: { id: 0 } })
  return real.tambahAnggota(keluargaId, payload)
}
export function setStatusAnggota(anggotaId, payload) {
  if (USE_MOCK.warga) return Promise.resolve({ ok: true })
  return real.setStatusAnggota(anggotaId, payload)
}

export function portalKeluargaSaya() {
  return USE_MOCK.warga
    ? Promise.resolve({ ok: true, data: { id: 1, alamat: '—', anggota: [] } })
    : real.portalKeluargaSaya()
}
