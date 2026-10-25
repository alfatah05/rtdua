import { ref, computed } from 'vue'

/**
 * Custom pull-to-refresh with overscroll resistance.
 * Spinner fixed top-center.
 */
export function usePullToRefresh(onRefresh, rootRef) {
  const PTR_THRESHOLD = 70
  const PTR_MAX = 120
  const PTR_HIDDEN_Y = -52
  const PTR_LOADING_OFFSET = 108

  const ptrOffset = ref(0)
  const ptrRefreshing = ref(false)
  const ptrAnimating = ref(false)

  let ptrStartY = 0
  let ptrStartX = 0
  let ptrTracking = false
  let ptrArmed = false

  function scrollTop() {
    return window.scrollY || document.documentElement.scrollTop || 0
  }

  function applyResistance(dy) {
    if (dy <= 0) return 0
    return PTR_MAX * (1 - 1 / (dy / PTR_MAX + 1))
  }

  const ptrProgress = computed(() => Math.min(1, ptrOffset.value / PTR_THRESHOLD))

  const ptrIndicatorStyle = computed(() => {
    const y = PTR_HIDDEN_Y + ptrOffset.value
    const op = ptrRefreshing.value ? 1 : Math.min(1, ptrOffset.value / 24)
    return {
      transform: `translate3d(-50%, ${y}px, 0)`,
      opacity: op,
    }
  })

  const ptrCircleInnerStyle = computed(() => {
    const s = ptrRefreshing.value ? 1 : (0.72 + 0.28 * ptrProgress.value)
    return { transform: `scale(${s})` }
  })

  const ptrSpinnerStyle = computed(() => {
    if (ptrRefreshing.value) return {}
    const deg = Math.round(ptrProgress.value * 270)
    return { transform: `rotate(${deg}deg)` }
  })

  function onPtrStart(e) {
    if (ptrRefreshing.value || ptrAnimating.value) return
    if (scrollTop() > 1) { ptrArmed = false; return }
    const touch = e.touches?.[0]
    if (!touch) return
    ptrArmed = true
    ptrTracking = false
    ptrStartY = touch.clientY
    ptrStartX = touch.clientX
    ptrAnimating.value = false
  }

  function onPtrMove(e) {
    if (!ptrArmed || ptrRefreshing.value) return
    const touch = e.touches?.[0]
    if (!touch) return
    const dy = touch.clientY - ptrStartY
    const dx = touch.clientX - ptrStartX
    if (!ptrTracking) {
      if (dy < 6) return
      if (Math.abs(dx) > Math.abs(dy) * 0.85) { ptrArmed = false; return }
      if (scrollTop() > 1) { ptrArmed = false; return }
      ptrTracking = true
    }
    if (dy > 0) {
      e.preventDefault()
      ptrOffset.value = applyResistance(dy)
    } else {
      ptrOffset.value = 0
    }
  }

  function animateOffsetTo(target, ms = 280) {
    return new Promise((resolve) => {
      ptrAnimating.value = true
      ptrOffset.value = target
      window.setTimeout(() => {
        ptrAnimating.value = false
        resolve()
      }, ms)
    })
  }

  async function onPtrEnd() {
    if (!ptrTracking && !ptrArmed) return
    const shouldRefresh =
      ptrTracking && ptrOffset.value >= PTR_THRESHOLD && !ptrRefreshing.value
    ptrArmed = false
    ptrTracking = false

    if (shouldRefresh) {
      ptrRefreshing.value = true
      await animateOffsetTo(PTR_LOADING_OFFSET, 240)
      try {
        await onRefresh()
      } finally {
        await animateOffsetTo(0, 300)
        ptrRefreshing.value = false
        ptrOffset.value = 0
        ptrAnimating.value = false
      }
    } else {
      await animateOffsetTo(0, 260)
      ptrOffset.value = 0
    }
  }

  function bind() {
    const el = rootRef?.value
    if (!el) return
    el.addEventListener('touchstart', onPtrStart, { passive: true })
    el.addEventListener('touchmove', onPtrMove, { passive: false })
    el.addEventListener('touchend', onPtrEnd, { passive: true })
    el.addEventListener('touchcancel', onPtrEnd, { passive: true })
  }

  function unbind() {
    const el = rootRef?.value
    if (!el) return
    el.removeEventListener('touchstart', onPtrStart)
    el.removeEventListener('touchmove', onPtrMove)
    el.removeEventListener('touchend', onPtrEnd)
    el.removeEventListener('touchcancel', onPtrEnd)
  }

  return {
    ptrAnimating,
    ptrRefreshing,
    ptrIndicatorStyle,
    ptrCircleInnerStyle,
    ptrSpinnerStyle,
    bind,
    unbind,
  }
}
