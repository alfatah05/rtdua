import { ref } from 'vue'

/**
 * Selalu animasi page-up (geser naik) — maju, back, dan antar-tab.
 * Pola Meimo-like: page baru selalu naik dari bawah; page lama fade tanpa turun.
 */
export const pageTransitionName = ref('page-up')

export function resolvePageTransition(to, from) {
  // First paint / hard load: tanpa animasi
  if (!from || !from.name) {
    pageTransitionName.value = 'page-none'
    return pageTransitionName.value
  }
  // Semua navigasi berikutnya: naik
  pageTransitionName.value = 'page-up'
  return pageTransitionName.value
}

export function syncHistoryPosition() {
  // no-op — arah tidak lagi membedakan animasi
}
