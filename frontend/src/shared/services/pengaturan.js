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
  return (USE_MOCK.pengaturan ? mock : real).updateAplikasi
    ? (USE_MOCK.pengaturan ? Promise.resolve({ ok: true, data: null }) : real.updateAplikasi(payload))
    : Promise.resolve({ ok: true })
}
export function updateWarga(payload) {
  return USE_MOCK.pengaturan
    ? Promise.resolve({ ok: true, data: null })
    : real.updateWarga(payload)
}
export function tambahBlok(nama) {
  return USE_MOCK.pengaturan
    ? Promise.resolve({ ok: true, data: { nama } })
    : real.tambahBlok(nama)
}
