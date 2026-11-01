/**
 * Web Push PWA — wajib gesture pengguna untuk requestPermission.
 * iPhone: hanya setelah Add to Home Screen (iOS 16.4+).
 * Multi-device: setiap perangkat punya endpoint sendiri di push_langganan.
 * Card "aktifkan" di halaman notifikasi = perangkat ini belum punya subscription.
 */
import { ref, onMounted } from 'vue'
import { getVapidPublic, subscribePush, unsubscribePush } from '../services/notifikasi.js'

const supported =
  typeof window !== 'undefined' &&
  'serviceWorker' in navigator &&
  'PushManager' in window &&
  'Notification' in window

/** idle | unsupported | denied | default | active | loading */
const status = ref('idle')
/** true hanya jika perangkat ini punya PushSubscription lokal */
const hasSubscription = ref(false)
const errorMsg = ref('')

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const arr = new Uint8Array(raw.length)
  for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i)
  return arr
}

async function getSwRegistration() {
  const existing = await navigator.serviceWorker.getRegistration()
  if (existing) {
    await navigator.serviceWorker.ready
    return existing
  }
  try {
    return await navigator.serviceWorker.ready
  } catch {
    return null
  }
}

async function getLocalSubscription() {
  try {
    const reg = await getSwRegistration()
    if (!reg?.pushManager) return null
    return await reg.pushManager.getSubscription()
  } catch {
    return null
  }
}

export function usePush() {
  async function refreshStatus() {
    errorMsg.value = ''
    hasSubscription.value = false
    if (!supported) {
      status.value = 'unsupported'
      return
    }
    const perm = Notification.permission
    if (perm === 'denied') {
      status.value = 'denied'
      return
    }
    const sub = await getLocalSubscription()
    if (sub) {
      hasSubscription.value = true
      status.value = 'active'
      return
    }
    status.value = 'default'
  }

  /**
   * Aktifkan push di perangkat ini: izin + subscribe + simpan ke server.
   * Harus dipanggil dari klik tombol (user gesture).
   */
  async function aktifkan() {
    errorMsg.value = ''
    if (!supported) {
      status.value = 'unsupported'
      return { ok: false, error: 'Browser tidak mendukung push. Di iPhone, pasang aplikasi ke Layar Utama dulu.' }
    }

    status.value = 'loading'
    try {
      const perm = await Notification.requestPermission()
      if (perm !== 'granted') {
        status.value = 'denied'
        hasSubscription.value = false
        return { ok: false, error: 'Izin notifikasi ditolak. Aktifkan di pengaturan browser/HP.' }
      }

      const vapid = await getVapidPublic()
      if (!vapid.ok || !vapid.data?.publicKey) {
        status.value = 'default'
        return { ok: false, error: vapid.error || 'VAPID belum diset di server (.env VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY).' }
      }

      const reg = await getSwRegistration()
      if (!reg) {
        status.value = 'default'
        return { ok: false, error: 'Service worker belum siap. Tutup & buka lagi aplikasi (mode terpasang).' }
      }

      let sub = await reg.pushManager.getSubscription()
      if (!sub) {
        sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapid.data.publicKey),
        })
      }

      const json = sub.toJSON()
      const res = await subscribePush(json, navigator.userAgent?.slice(0, 120))
      if (!res.ok) {
        status.value = 'default'
        hasSubscription.value = false
        return { ok: false, error: res.error || 'Gagal menyimpan langganan push.' }
      }
      hasSubscription.value = true
      status.value = 'active'
      return { ok: true }
    } catch (e) {
      status.value = 'default'
      hasSubscription.value = false
      const msg = e?.message || 'Gagal mengaktifkan push.'
      errorMsg.value = msg
      return { ok: false, error: msg }
    }
  }

  async function matikan() {
    errorMsg.value = ''
    if (!supported) return { ok: true }
    try {
      const reg = await getSwRegistration()
      const sub = await reg?.pushManager?.getSubscription()
      const endpoint = sub?.endpoint
      if (sub) await sub.unsubscribe()
      if (endpoint) await unsubscribePush(endpoint)
    } catch {
      /* ignore */
    }
    hasSubscription.value = false
    status.value = 'default'
    return { ok: true }
  }

  /**
   * Izin sudah granted + ada subscription lokal → re-POST ke server (multi-device aman).
   * Izin granted tapi belum ada subscription → biarkan card muncul (status default).
   */
  async function syncIfGranted() {
    if (!supported || Notification.permission !== 'granted') return
    try {
      const sub = await getLocalSubscription()
      if (!sub) {
        hasSubscription.value = false
        status.value = 'default'
        return
      }
      await subscribePush(sub.toJSON(), navigator.userAgent?.slice(0, 120))
      hasSubscription.value = true
      status.value = 'active'
    } catch {
      /* ignore */
    }
  }

  onMounted(() => {
    refreshStatus().then(() => syncIfGranted())
  })

  return {
    supported,
    status,
    hasSubscription,
    errorMsg,
    aktifkan,
    matikan,
    refreshStatus,
    syncIfGranted,
  }
}
