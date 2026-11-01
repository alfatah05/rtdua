import { ref, onMounted, onUnmounted } from 'vue'

/** @type {BeforeInstallPromptEvent | null} */
let deferredPrompt = null
const canInstall = ref(false)
const isInstalled = ref(false)

function detectInstalled() {
  if (typeof window === 'undefined') return true
  const standalone =
    window.matchMedia('(display-mode: standalone)').matches ||
    // iOS Safari
    window.navigator.standalone === true ||
    document.referrer.includes('android-app://')
  return standalone
}

function refresh() {
  isInstalled.value = detectInstalled()
  // Tampilkan aksi install hanya jika belum terpasang
  canInstall.value = !isInstalled.value
}

function onBeforeInstall(e) {
  e.preventDefault()
  deferredPrompt = e
  refresh()
}

function onAppInstalled() {
  deferredPrompt = null
  refresh()
}

let listening = false
function ensureListeners() {
  if (listening || typeof window === 'undefined') return
  listening = true
  window.addEventListener('beforeinstallprompt', onBeforeInstall)
  window.addEventListener('appinstalled', onAppInstalled)
  refresh()
}

// Start early when module loads (warga/pengurus bundle)
ensureListeners()

/**
 * @returns {{ canInstall: import('vue').Ref<boolean>, isInstalled: import('vue').Ref<boolean>, install: () => Promise<{ ok: boolean, message?: string }> }}
 */
export function usePwaInstall() {
  onMounted(() => {
    ensureListeners()
    refresh()
  })

  async function install() {
    refresh()
    if (isInstalled.value) {
      return { ok: false, message: 'Aplikasi sudah terpasang' }
    }

    if (deferredPrompt) {
      try {
        deferredPrompt.prompt()
        const choice = await deferredPrompt.userChoice
        deferredPrompt = null
        refresh()
        if (choice?.outcome === 'accepted') {
          isInstalled.value = true
          canInstall.value = false
          return { ok: true, message: 'Aplikasi dipasang' }
        }
        return { ok: false, message: 'Instalasi dibatalkan' }
      } catch (err) {
        return { ok: false, message: 'Gagal membuka dialog install' }
      }
    }

    // iOS: tidak ada beforeinstallprompt
    const ua = navigator.userAgent || ''
    const isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    if (isIOS) {
      return {
        ok: false,
        message: 'Di iPhone: ketuk Bagikan → Tambah ke Layar Utama',
      }
    }

    return {
      ok: false,
      message: 'Buka di Chrome/Edge, lalu menu browser → Install aplikasi',
    }
  }

  return { canInstall, isInstalled, install }
}
