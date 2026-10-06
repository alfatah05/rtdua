import { ref, computed } from 'vue'
import * as authService from '../services/auth.js'

const user = ref(null)
const loading = ref(false)

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
      return { ok: true, mustChange: res.mustChange || !!res.user?.harus_ganti_kredensial }
    } finally { loading.value = false }
  }

  async function logout(side) {
    try { await authService.logout() } catch (e) {}
    user.value = null
    try {
      if (side === 'warga') localStorage.removeItem('rtdua-auth-warga')
      else localStorage.removeItem('rtdua-auth-pengurus')
    } catch (e) {}
  }

  function restore(side) {
    try {
      const key = side === 'warga' ? 'rtdua-auth-warga' : 'rtdua-auth-pengurus'
      const raw = localStorage.getItem(key)
      if (raw) user.value = JSON.parse(raw)
    } catch (e) {}
  }

  return { user, loading, isLoggedIn, role, isKetua, isDeveloper, loginWarga, loginPengurus, logout, restore }
}
