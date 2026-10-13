import { compressImage, makeThumb } from '../utils/imageCompress.js'

/**
 * Upload file gambar.
 * jenis: bukti | ronda | galeri | banner | foto_profil | logo
 *
 * Semua jenis (kecuali galeri) dikompres ke WebP (alpha aman).
 * Galeri RT: hanya di-resize, format asli dipertahankan.
 */
export async function uploadGambar(file, jenis, { maxSide = 1600, withThumb = false } = {}) {
  try {
    const isGaleri = jenis === 'galeri'
    const side = jenis === 'logo' ? Math.min(maxSide, 800) : maxSide
    const compressed = await compressImage(file, {
      maxSide: side,
      quality: isGaleri ? 0.85 : 0.8,
      preferOriginal: isGaleri,
    })
    const fd = new FormData()
    fd.append('jenis', jenis)
    fd.append('file', compressed, compressed.name)
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

/**
 * Upload lampiran pengumuman: PDF atau gambar.
 * Gambar → WebP; PDF tidak disentuh.
 */
export async function uploadLampiran(file) {
  try {
    const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '')
    let bodyFile = file
    if (!isPdf) {
      bodyFile = await compressImage(file, { maxSide: 1600, quality: 0.8, preferOriginal: false })
    }
    const fd = new FormData()
    fd.append('jenis', 'lampiran')
    fd.append(
      'file',
      bodyFile,
      isPdf ? file.name || 'lampiran.pdf' : bodyFile.name || 'lampiran.webp',
    )
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
