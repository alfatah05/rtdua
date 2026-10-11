import { marked } from 'marked'
import DOMPurify from 'dompurify'

marked.setOptions({
  gfm: true,
  breaks: true,
  pedantic: false,
})

/** Konten lama full-HTML (bukan markdown). */
export function isProbablyHtml(s) {
  if (!s || typeof s !== 'string') return false
  const t = s.trim()
  // Hanya anggap HTML jika dibuka & ditutup tag, dan sedikit sekali baris markdown
  if (!/^</.test(t) || !/<\/[a-z][\w-]*>/i.test(t)) return false
  // Kalau ada heading markdown di luar tag, tetap parse sebagai markdown
  if (/^#{1,6}\s/m.test(t) || /\|\s*---/.test(t) || /^```/m.test(t)) return false
  return true
}

const PURIFY = {
  USE_PROFILES: { html: true },
  ADD_TAGS: [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
    'input', 'del', 's', 'ins', 'u',
    'details', 'summary', 'kbd', 'abbr', 'mark', 'sub', 'sup',
  ],
  ADD_ATTR: [
    'target', 'rel', 'class', 'id',
    'align', 'colspan', 'rowspan',
    'type', 'checked', 'disabled',
    'start', 'width', 'height', 'alt', 'title',
    'open',
  ],
  ALLOW_DATA_ATTR: false,
}

function enhanceLinks(html) {
  // Target blank untuk link eksternal
  return String(html).replace(
    /<a\s+([^>]*href=["']https?:\/\/[^"']+["'][^>]*)>/gi,
    (full, attrs) => {
      let a = attrs
      if (!/\btarget\s*=/i.test(a)) a += ' target="_blank"'
      if (!/\brel\s*=/i.test(a)) a += ' rel="noopener noreferrer"'
      return `<a ${a}>`
    }
  )
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
    try {
      html = marked.parse(raw, { async: false })
    } catch (e) {
      html = raw
        .replace(/&/g, '&')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/\n/g, '<br>')
    }
  }
  html = enhanceLinks(html)
  return DOMPurify.sanitize(html, PURIFY)
}
