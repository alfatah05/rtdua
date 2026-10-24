import { ref, watch, onMounted } from 'vue'

const mode = ref('light')

/** Samakan dengan --bg di main.css */
const BAR = {
  light: '#FFFFFF',
  dark: '#111314',
}

/** Warna status bar (atas). Mengikuti --bg light/dark. */
function setSystemBarColor(value) {
  const color = BAR[value] || BAR.light

  let meta = document.querySelector('meta[name="theme-color"]:not([media])')
  if (!meta) {
    meta = document.createElement('meta')
    meta.setAttribute('name', 'theme-color')
    document.head.appendChild(meta)
  }
  meta.setAttribute('content', color)

  // media prefers-color-scheme (kalau ada di HTML)
  document.querySelectorAll('meta[name="theme-color"][media]').forEach((el) => {
    const m = (el.getAttribute('media') || '').toLowerCase()
    if (m.includes('dark')) el.setAttribute('content', BAR.dark)
    else el.setAttribute('content', BAR.light)
  })

  let apple = document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]')
  if (!apple) {
    apple = document.createElement('meta')
    apple.setAttribute('name', 'apple-mobile-web-app-status-bar-style')
    document.head.appendChild(apple)
  }
  // iOS: default = terang + ikon gelap; black = gelap + ikon terang
  apple.setAttribute('content', value === 'dark' ? 'black' : 'default')
}

function applyMode(value) {
  const v = value === 'dark' ? 'dark' : 'light'
  mode.value = v
  document.documentElement.setAttribute('data-mode', v)
  try {
    localStorage.setItem('rt_theme', v)
  } catch (_) {}
  setSystemBarColor(v)
}

function initTheme() {
  let saved = null
  try {
    saved = localStorage.getItem('rt_theme')
  } catch (_) {}
  if (saved === 'dark' || saved === 'light') {
    applyMode(saved)
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    applyMode('dark')
  } else {
    applyMode('light')
  }
}

function toggleTheme() {
  applyMode(mode.value === 'dark' ? 'light' : 'dark')
}

export function useTheme() {
  onMounted(() => {
    if (!document.documentElement.getAttribute('data-mode')) initTheme()
    else setSystemBarColor(mode.value)
  })
  return { mode, applyMode, toggleTheme, initTheme }
}

// init segera agar FOUC minimal
if (typeof document !== 'undefined') {
  try {
    const saved = localStorage.getItem('rt_theme')
    if (saved === 'dark' || saved === 'light') {
      document.documentElement.setAttribute('data-mode', saved)
      setSystemBarColor(saved)
    }
  } catch (_) {}
}
