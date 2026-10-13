import { ref } from 'vue'
import { badgeNotifikasi } from '@shared/services/notifikasi.js'

/** State bersama — tidak reset saat ganti halaman */
const badge = ref(0)
let started = false
let timer = null
let inflight = null

async function refresh() {
  if (inflight) return inflight
  inflight = (async () => {
    try {
      const res = await badgeNotifikasi()
      if (res.ok) badge.value = Number(res.data?.belum_dibaca || 0)
    } catch (_) {
      /* diam */
    } finally {
      inflight = null
    }
  })()
  return inflight
}

function onVis() {
  if (document.visibilityState === 'visible') refresh()
}

function ensurePolling() {
  if (started) return
  started = true
  refresh()
  timer = setInterval(refresh, 60000)
  document.addEventListener('visibilitychange', onVis)
}

/**
 * Badge notifikasi global untuk header (main & back).
 * Panggil ensure() sekali di onMounted komponen header.
 */
export function useNotifBadge() {
  function ensure() {
    ensurePolling()
  }

  return { badge, refresh, ensure }
}
