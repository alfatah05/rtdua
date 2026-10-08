import { ref, watch, onMounted } from 'vue'

const mode = ref('light')

const THEME_LIGHT = '#FFFFFF'
const THEME_DARK = '#111314'

function setThemeColorMeta(color) {
  // Chrome/Android status bar + (sebagian device) system nav bar
  let meta = document.querySelector('meta[name="theme-color"]:not([media])')
  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'theme-color')
    document.head.appendChild(meta)
  }
  meta.setAttribute('content', color)

  document.querySelectorAll('meta[name="theme-color"][media]').forEach((el) => {
    el.setAttribute('content', color)
  })

  let apple = document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]')
  if (!apple) {
    apple = document.createElement('meta')
    apple.setAttribute('name', 'apple-mobile-web-app-status-bar-style')
    document.head.appendChild(apple)
  }
  apple.setAttribute('content', color === THEME_DARK ? 'black' : 'default')

  // Paksa background html/body supaya area di belakang system nav ikut warna
  try {
    document.documentElement.style.backgroundColor = color
    document.body.style.backgroundColor = color
  } catch (e) {}
}

function applyMode(value) {
  const root = document.documentElement
  if (value === 'dark') {
    root.setAttribute('data-mode', 'dark')
    setThemeColorMeta(THEME_DARK)
  } else {
    root.removeAttribute('data-mode')
    setThemeColorMeta(THEME_LIGHT)
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
