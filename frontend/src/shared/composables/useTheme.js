import { ref, watch, onMounted } from 'vue'

const mode = ref('light')

function applyMode(value) {
  const root = document.documentElement
  if (value === 'dark') {
    root.setAttribute('data-mode', 'dark')
  } else {
    root.removeAttribute('data-mode')
  }
  try {
    localStorage.setItem('rtdua-mode', value)
  } catch (e) {}
}

export function useTheme() {
  onMounted(() => {
    try {
      const saved = localStorage.getItem('rtdua-mode')
      if (saved === 'dark' || saved === 'light') {
        mode.value = saved
      }
    } catch (e) {}
    applyMode(mode.value)
  })

  watch(mode, (v) => applyMode(v))

  function toggle() {
    mode.value = mode.value === 'dark' ? 'light' : 'dark'
  }

  function setMode(v) {
    if (v === 'dark' || v === 'light') mode.value = v
  }

  return { mode, toggle, setMode }
}
