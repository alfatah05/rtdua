/**
 * Aktifkan Web Push lewat tombol (wajib gesture pengguna).
 * iPhone: hanya setelah Add to Home Screen (iOS 16.4+).
 */
import { ref } from 'vue'
import { getVapidPublic, subscribePush, unsubscribePush } from '../services/notifikasi.js'

const supported = typeof window !== 'undefined' && 'serviceWorker' in navigator && 'PushManager' in window
const status = ref('unknown') // unknown | denied | granted | unsupported

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw = atob(base64)
  const arr = new Uint8Array(raw.length)
  for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i)
  return arr
}

export function usePush() {
  async function aktifkan() {
    if (!supported) {
      status.value = 'unsupported'
      return { ok: false, error: 'Browser tidak mendukung push (atau belum dipasang ke layar utama).' }
    }
    const perm = await Notification.requestPermission()
    if (perm !== 'granted') {
      status.value = 'denied'
      return { ok: false, error: 'Izin notifikasi ditolak.' }
    }
    status.value = 'granted'

    const vapid = await getVapidPublic()
    if (!vapid.ok || !vapid.data?.publicKey) {
      return { ok: false, error: vapid.error || 'VAPID belum diset di server.' }
    }

    const reg = await navigator.serviceWorker.register('/sw.js').catch(() => null)
    if (!reg) {
      return { ok: false, error: 'Service worker gagal didaftarkan.' }
    }
    await navigator.serviceWorker.ready

    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapid.data.publicKey),
    })
    const json = sub.toJSON()
    const res = await subscribePush(json, navigator.userAgent?.slice(0, 120))
    if (!res.ok) return { ok: false, error: res.error }
    return { ok: true }
  }

  async function matikan() {
    if (!supported) return { ok: true }
    try {
      const reg = await navigator.serviceWorker.getRegistration()
      const sub = await reg?.pushManager?.getSubscription()
      const endpoint = sub?.endpoint
      if (sub) await sub.unsubscribe()
      await unsubscribePush(endpoint)
    } catch {
      /* ignore */
    }
    return { ok: true }
  }

  return { supported, status, aktifkan, matikan }
}
