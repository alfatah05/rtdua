import { USE_MOCK } from '../config/dataSource.js'
import * as mock from './auth.mock.js'
import * as real from './auth.real.js'

const impl = () => (USE_MOCK.auth ? mock : real)

export function loginWarga(username, pin) {
  return impl().loginWarga(username, pin)
}
export function loginPengurus(username, password) {
  return impl().loginPengurus(username, password)
}
export function logout() {
  return impl().logout()
}
export function me() {
  return impl().me()
}
export function changeCredential(payload) {
  return impl().changeCredential(payload)
}
