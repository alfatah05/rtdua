import { ref, watch, onMounted } from 'vue'

const mode = ref('light')

// Android system nav bar sering tidak ikut toggle realtime → tetap hitam
const SYSTEM_BAR = '#000000'

function setSystemBarColor() {
  let meta = document.querySelector('meta[name="theme-color"]:not([media])')
  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'theme-color')
    document.head.appendChild(meta)
  }
  meta.setAttribute('content', SYSTEM_BAR)

  document.querySelectorAll('meta[name="theme-color"][media]').forEach((el) => {
    el.setAttribute('content', SYSTEM_BAR)
  })

  let apple = document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]')
  if (!apple) {
    apple = document.createElement('meta')
    apple.setAttribute('name', 'apple-mobile-web-app-status-bar-style')
    document.head.appendChild(apple)
  }
  // hitam + ikon terang (status bar)
  apple.setAttribute('content', 'black')
}

function applyMode(value) {
  const root = document.documentElement
  if (value === 'dark') {
    root.setAttribute('data-mode', 'dark')
  } else {
    root.removeAttribute('data-mode')
  }
  // System bar selalu hitam; isi app tetap ikut --bg (putih/dark)
  setSystemBarColor()
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
