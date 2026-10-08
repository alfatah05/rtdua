import { ref, computed } from 'vue'
import * as authService from '../services/auth.js'

const user = ref(null)
const loading = ref(false)
const sessionReady = ref(false)
let validatePromise = null

function storageKey(side) {
  return side === 'warga' ? 'rtdua-auth-warga' : 'rtdua-auth-pengurus'
}

function clearLocal(side) {
  user.value = null
  try {
    localStorage.removeItem(storageKey(side))
    // bersihkan juga sisi lain agar tidak nyasar
    localStorage.removeItem('rtdua-auth-warga')
    localStorage.removeItem('rtdua-auth-pengurus')
  } catch (e) {}
}

export function useAuth() {
  const isLoggedIn = computed(() => !!user.value)
  const role = computed(() => user.value?.role || null)
  const isKetua = computed(() => user.value?.role === 'ketua')
  const isDeveloper = computed(() => !!user.value?.is_developer)

  async function loginWarga(username, pin) {
    loading.value = true
    try {
      const res = await authService.loginWarga(username, pin)
      if (!res.ok) return { ok: false, error: res.error }
      user.value = res.user
      try { localStorage.setItem('rtdua-auth-warga', JSON.stringify(user.value)) } catch (e) {}
      sessionReady.value = true
      return { ok: true, mustChange: res.mustChange || !!res.user?.harus_ganti_kredensial }
    } finally { loading.value = false }
  }

  async function loginPengurus(username, password) {
    loading.value = true
    try {
      const res = await authService.loginPengurus(username, password)
      if (!res.ok) return { ok: false, error: res.error }
      user.value = res.user
      try { localStorage.setItem('rtdua-auth-pengurus', JSON.stringify(user.value)) } catch (e) {}
      sessionReady.value = true
      return { ok: true, mustChange: res.mustChange || !!res.user?.harus_ganti_kredensial }
    } finally { loading.value = false }
  }

  async function logout(side) {
    try { await authService.logout() } catch (e) {}
    clearLocal(side)
    sessionReady.value = true
  }

  /** Hanya baca localStorage — tidak cek server. */
  function restore(side) {
    try {
      const raw = localStorage.getItem(storageKey(side))
      if (raw) user.value = JSON.parse(raw)
      else user.value = null
    } catch (e) {
      user.value = null
    }
  }

  /**
   * Cek cookie sesi di server. Kalau expired → bersihkan localStorage.
   * Dipanggil sekali di guard (cache promise supaya tidak dobel request).
   */
  async function ensureSession(side) {
    if (sessionReady.value) return isLoggedIn.value
    if (validatePromise) return validatePromise

    validatePromise = (async () => {
      restore(side)
      // tanpa cache lokal pun tetap cek server (kalau cookie masih hidup)
      try {
        const res = await authService.me()
        if (res.ok && res.user) {
          user.value = res.user
          try { localStorage.setItem(storageKey(side), JSON.stringify(res.user)) } catch (e) {}
        } else {
          clearLocal(side)
        }
      } catch (e) {
        // network error: jangan langsung kick kalau masih ada cache;
        // tapi kalau server bilang 401, clear
        if (e && e.status === 401) clearLocal(side)
      }
      sessionReady.value = true
      return isLoggedIn.value
    })()

    try {
      return await validatePromise
    } finally {
      validatePromise = null
    }
  }

  /** Dipanggil saat API mengembalikan 401. */
  function forceLogout(side = 'warga') {
    clearLocal(side)
    sessionReady.value = true
  }

  return {
    user,
    loading,
    sessionReady,
    isLoggedIn,
    role,
    isKetua,
    isDeveloper,
    loginWarga,
    loginPengurus,
    logout,
    restore,
    ensureSession,
    forceLogout,
  }
}
