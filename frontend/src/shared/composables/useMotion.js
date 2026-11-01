import { ref, onMounted } from 'vue'

const animEnabled = ref(true)

function applyAnim(on) {
  const v = !!on
  animEnabled.value = v
  document.documentElement.setAttribute('data-anim', v ? 'on' : 'off')
  try {
    localStorage.setItem('rt_anim', v ? 'on' : 'off')
  } catch (_) {}
}

function toggleAnim() {
  applyAnim(!animEnabled.value)
}

function initAnim() {
  let saved = null
  try {
    saved = localStorage.getItem('rt_anim')
  } catch (_) {}
  if (saved === 'off') applyAnim(false)
  else applyAnim(true)
}

export function useMotion() {
  onMounted(() => {
    const attr = document.documentElement.getAttribute('data-anim')
    if (attr === 'on' || attr === 'off') {
      animEnabled.value = attr === 'on'
    } else {
      initAnim()
    }
  })
  return { animEnabled, applyAnim, toggleAnim, initAnim }
}

// Early init
if (typeof document !== 'undefined') {
  try {
    const saved = localStorage.getItem('rt_anim')
    if (saved === 'off') {
      animEnabled.value = false
      document.documentElement.setAttribute('data-anim', 'off')
    } else {
      document.documentElement.setAttribute('data-anim', 'on')
    }
  } catch (_) {}
}
