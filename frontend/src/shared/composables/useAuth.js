import { ref, computed } from 'vue'
import * as authService from '../services/auth.js'

const user = ref(null)
const loading = ref(false)
const sessionReady = ref(false)
let validatePromise = null

function storageKey(side) {
  return side === 'pengurus' ? 'rtdua-auth-pengurus' : 'rtdua-auth-warga'
}

/** Cache lokal minimal (bukan token) — hanya untuk UI sebelum /auth/me selesai. */
function slimUser(u) {
  if (!u || typeof u !== 'object') return null
  return {
    id: u.id ?? null,
    nama: u.nama ?? null,
    role: u.role ?? null,
    foto: u.foto ?? null,
    jabatan: u.jabatan ?? null,
    alamat: u.alamat ?? null,
    keluarga_id: u.keluarga_id ?? null,
    warga_id: u.warga_id ?? null,
    harus_ganti_kredensial: !!u.harus_ganti_kredensial,
    is_developer: !!u.is_developer,
  }
}

function persist(side, u) {
  try {
    const s = slimUser(u)
    if (s) localStorage.setItem(storageKey(side), JSON.stringify(s))
    else localStorage.removeItem(storageKey(side))
  } catch (e) {}
}

function clearLocal(side) {
  user.value = null
  try {
    localStorage.removeItem(storageKey(side))
    // bersihkan sisa key lama bila ada
    localStorage.removeItem('rtdua-auth-warga')
    localStorage.removeItem('rtdua-auth-pengurus')
  } catch (e) {}
}

export function useAuth() {
  const isLoggedIn = computed(() => !!user.value?.id)
  const role = computed(() => user.value?.role || null)
  const isKetua = computed(() => user.value?.role === 'ketua')
  const isDeveloper = computed(() => !!user.value?.is_developer)

  async function loginWarga(username, pin) {
    loading.value = true
    try {
      const res = await authService.loginWarga(username, pin)
      if (!res.ok) return { ok: false, error: res.error }
      user.value = res.user
      persist('warga', user.value)
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
      persist('pengurus', user.value)
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
      if (raw) user.value = slimUser(JSON.parse(raw))
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
      try {
        const res = await authService.me()
        if (res.ok && res.user) {
          user.value = res.user
          persist(side, res.user)
        } else {
          clearLocal(side)
        }
      } catch (e) {
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
