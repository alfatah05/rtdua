import { USE_MOCK } from '../config/dataSource.js'
import * as mock from './warga.mock.js'
import * as real from './warga.real.js'

const impl = () => (USE_MOCK.warga ? mock : real)

export function listKeluarga(params) {
  return impl().listKeluarga(params)
}
export function getKeluarga(id) {
  return impl().getKeluarga(id)
}
