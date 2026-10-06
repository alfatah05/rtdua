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
