/**
 * Kompres gambar di browser (canvas).
 * - PNG / WebP: format dipertahankan (transparansi aman).
 * - JPEG / lainnya: keluar JPEG, kanvas diisi putih dulu agar tidak hitam.
 */
export async function compressImage(file, { maxSide = 1600, quality = 0.82, mime } = {}) {
  const srcType = String(file.type || '').toLowerCase()
  const wantPng = srcType === 'image/png'
  const wantWebp = srcType === 'image/webp'

  // Logo & aset transparan: jangan paksa JPEG
  let outMime = mime
  if (!outMime) {
    if (wantPng) outMime = 'image/png'
    else if (wantWebp) outMime = 'image/webp'
    else outMime = 'image/jpeg'
  }

  const bitmap = await createImageBitmap(file)
  let { width, height } = bitmap
  if (width > maxSide || height > maxSide) {
    const r = Math.min(maxSide / width, maxSide / height)
    width = Math.round(width * r)
    height = Math.round(height * r)
  }

  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  const ctx = canvas.getContext('2d')

  // JPEG tidak punya alpha — isi putih supaya area transparan tidak jadi hitam
  if (outMime === 'image/jpeg') {
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, width, height)
  }

  ctx.drawImage(bitmap, 0, 0, width, height)
  bitmap.close()

  const blob = await new Promise((resolve) => {
    if (outMime === 'image/png') {
      canvas.toBlob(resolve, 'image/png')
    } else if (outMime === 'image/webp') {
      canvas.toBlob(resolve, 'image/webp', quality)
    } else {
      canvas.toBlob(resolve, 'image/jpeg', quality)
    }
  })
  if (!blob) throw new Error('Gagal kompres gambar')

  const base = String(file.name || 'img').replace(/\.\w+$/, '')
  const ext = outMime === 'image/png' ? '.png' : outMime === 'image/webp' ? '.webp' : '.jpg'
  return new File([blob], base + ext, { type: outMime })
}

export async function makeThumb(file, side = 400) {
  const compressed = await compressImage(file, { maxSide: side, quality: 0.75 })
  return new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload = () => resolve(r.result)
    r.onerror = reject
    r.readAsDataURL(compressed)
  })
}
