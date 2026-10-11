import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({
  gfm: true,
  breaks: true,
})

/** Deteksi konten lama yang disimpan sebagai HTML */
export function isProbablyHtml(s) {
  if (!s || typeof s !== 'string') return false
  const t = s.trim()
  return /^</.test(t) && /<\/[a-z][\w-]*>/i.test(t)
}

/**
 * Render markdown (atau HTML lama) → HTML aman untuk v-html.
 */
export function renderMarkdown(src) {
  const raw = String(src || '')
  if (!raw.trim()) return ''
  let html
  if (isProbablyHtml(raw)) {
    html = raw
  } else {
    html = marked.parse(raw)
  }
  return DOMPurify.sanitize(html, {
    USE_PROFILES: { html: true },
    ADD_ATTR: ['target', 'rel'],
  })
}
