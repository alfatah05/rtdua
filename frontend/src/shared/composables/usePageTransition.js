import { ref } from 'vue'

/** Nama transition Vue saat ini: page-forward | page-back | page-fade | page-none */
export const pageTransitionName = ref('page-forward')

let lastHistoryPos = typeof window !== 'undefined'
  ? (window.history.state?.position ?? 0)
  : 0

/**
 * Tentukan animasi berdasarkan arah navigasi.
 * - Tab ↔ tab (mainTab): fade singkat
 * - Mundur (history pop / ke tab dari child): slide turun
 * - Maju: page baru bergeser naik (native sheet-ish)
 */
export function resolvePageTransition(to, from) {
  if (!from || !from.name) {
    pageTransitionName.value = 'page-none'
    return pageTransitionName.value
  }

  const pos = typeof window !== 'undefined'
    ? (window.history.state?.position ?? lastHistoryPos)
    : lastHistoryPos
  const goingBack = typeof pos === 'number' && pos < lastHistoryPos
  lastHistoryPos = typeof pos === 'number' ? pos : lastHistoryPos

  if (to.meta?.mainTab && from.meta?.mainTab) {
    pageTransitionName.value = 'page-fade'
    return pageTransitionName.value
  }

  if (goingBack || (to.meta?.mainTab && !from.meta?.mainTab)) {
    pageTransitionName.value = 'page-back'
    return pageTransitionName.value
  }

  const toDepth = String(to.path || '').split('/').filter(Boolean).length
  const fromDepth = String(from.path || '').split('/').filter(Boolean).length
  if (toDepth < fromDepth) {
    pageTransitionName.value = 'page-back'
  } else {
    pageTransitionName.value = 'page-forward'
  }
  return pageTransitionName.value
}

export function syncHistoryPosition() {
  if (typeof window === 'undefined') return
  const pos = window.history.state?.position
  if (typeof pos === 'number') lastHistoryPos = pos
}
