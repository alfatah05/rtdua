import { compressImage, makeThumb } from '../utils/imageCompress.js'

/**
 * Upload file gambar. jenis: bukti | galeri | banner | foto_profil
 */
export async function uploadGambar(file, jenis, { maxSide = 1600, withThumb = false } = {}) {
  try {
    const compressed = await compressImage(file, { maxSide })
    const fd = new FormData()
    fd.append('jenis', jenis)
    fd.append('file', compressed)
    if (withThumb) {
      const thumb = await makeThumb(file, 400)
      fd.append('thumb', thumb)
    }
    const res = await fetch('/api/upload', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
      body: fd,
    })
    const json = await res.json().catch(() => null)
    if (!res.ok || !json?.ok) {
      return { ok: false, error: json?.error || json?.message || 'Upload gagal' }
    }
    return { ok: true, data: json.data }
  } catch (e) {
    return { ok: false, error: e.message || 'Upload gagal' }
  }
}

export function mediaUrl(path) {
  if (!path) return ''
  if (String(path).startsWith('http') || String(path).startsWith('/api/')) return path
  return '/api/media/' + String(path).replace(/^\//, '')
}
