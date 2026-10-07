import { USE_MOCK } from '../config/dataSource.js'
import * as mock from './pengaturan.mock.js'
import * as real from './pengaturan.real.js'

const impl = () => (USE_MOCK.pengaturan ? mock : real)

export function getPengaturan() {
  return impl().getPengaturan()
}
export function getBlok() {
  return impl().getBlok()
}
export function updateAplikasi(payload) {
  if (USE_MOCK.pengaturan) return Promise.resolve({ ok: true, data: null })
  return real.updateAplikasi(payload)
}
export function updateWarga(payload) {
  if (USE_MOCK.pengaturan) return Promise.resolve({ ok: true, data: null })
  return real.updateWarga(payload)
}
export function tambahBlok(nama) {
  if (USE_MOCK.pengaturan) return Promise.resolve({ ok: true, data: { nama } })
  return real.tambahBlok(nama)
}
