import { ref } from 'vue'

/** Nama fixed — animasi selalu meimo-page (sama maju/back/tab). */
export const pageTransitionName = ref('meimo-page')

export function resolvePageTransition(_to, _from) {
  pageTransitionName.value = 'meimo-page'
  return pageTransitionName.value
}

export function syncHistoryPosition() {}
