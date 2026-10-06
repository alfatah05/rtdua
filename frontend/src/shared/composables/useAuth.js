import { ref, computed } from 'vue'

// State auth mock (shared per app instance)
const user = ref(null)
const loading = ref(false)

export function useAuth() {
  const isLoggedIn = computed(() => !!user.value)
  const role = computed(() => user.value?.role || null)
  const isKetua = computed(() => user.value?.role === 'ketua')
  const isDeveloper = computed(() => !!user.value?.is_developer)

  // Mock login — terima PIN/password apa saja untuk Stage 1
  async function loginWarga(username, pin) {
    loading.value = true
    await delay(400)
    // skenario mock berdasarkan username
    const u = (username || '').trim().toLowerCase().replace(/\s+/g, '')
    if (!u) {
      loading.value = false
      return { ok: false, error: 'Username wajib diisi' }
    }
    if (!pin || pin.length < 4) {
      loading.value = false
      return { ok: false, error: 'PIN minimal 4 angka' }
    }
    // akses warga belum dibuka
    if (u === 'tutup') {
      loading.value = false
      return { ok: false, error: 'Aplikasi belum dibuka' }
    }
    const mustChange = u === 'baru' || pin === '123456'
    user.value = {
      id: 1,
      role: 'warga',
      username: u,
      nama: 'Keluarga ' + u.toUpperCase(),
      keluarga_id: 1,
      harus_ganti_kredensial: mustChange,
    }
    try { localStorage.setItem('rtdua-auth-warga', JSON.stringify(user.value)) } catch (e) {}
    loading.value = false
    return { ok: true, mustChange }
  }

  async function loginPengurus(username, password) {
    loading.value = true
    await delay(400)
    const u = (username || '').trim().toLowerCase()
    if (!u) {
      loading.value = false
      return { ok: false, error: 'Username wajib diisi' }
    }
    if (!password || password.length < 4) {
      loading.value = false
      return { ok: false, error: 'Password minimal 4 karakter' }
    }
    const isKetuaUser = u === 'ketua' || u === 'developer'
    user.value = {
      id: isKetuaUser ? 1 : 2,
      role: isKetuaUser ? 'ketua' : 'pengurus',
      username: u,
      nama: isKetuaUser ? 'Budi Ketua' : 'Ani Pengurus',
      jabatan: isKetuaUser ? 'Ketua RT' : 'Bendahara',
      is_developer: u === 'developer',
      harus_ganti_kredensial: password === '12345678',
    }
    try { localStorage.setItem('rtdua-auth-pengurus', JSON.stringify(user.value)) } catch (e) {}
    loading.value = false
    return { ok: true }
  }

  function logout(side) {
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

  return {
    user,
    loading,
    isLoggedIn,
    role,
    isKetua,
    isDeveloper,
    loginWarga,
    loginPengurus,
    logout,
    restore,
  }
}

function delay(ms) {
  return new Promise((r) => setTimeout(r, ms))
}
