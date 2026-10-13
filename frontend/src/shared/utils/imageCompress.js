/**
 * Kompres gambar di browser (canvas).
 *
 * Default (preferOriginal=false): keluar WebP (alpha tetap aman).
 * Fallback: PNG jika sumber transparan & WebP encode gagal; selain itu JPEG.
 * preferOriginal=true (Galeri RT): pertahankan PNG/WebP; JPEG tetap JPEG.
 */
let _webpEncodeOk = null

function canEncodeWebp() {
  if (_webpEncodeOk !== null) return _webpEncodeOk
  try {
    const c = document.createElement('canvas')
    c.width = 2
    c.height = 2
    _webpEncodeOk = c.toDataURL('image/webp').startsWith('data:image/webp')
  } catch (_) {
    _webpEncodeOk = false
  }
  return _webpEncodeOk
}

function hasAlphaSource(srcType) {
  return srcType === 'image/png' || srcType === 'image/webp' || srcType === 'image/gif'
}

export async function compressImage(
  file,
  { maxSide = 1600, quality = 0.8, mime, preferOriginal = false } = {},
) {
  const srcType = String(file.type || '').toLowerCase()
  const wantPng = srcType === 'image/png'
  const wantWebp = srcType === 'image/webp'

  let outMime = mime
  if (!outMime) {
    if (preferOriginal) {
      if (wantPng) outMime = 'image/png'
      else if (wantWebp) outMime = 'image/webp'
      else outMime = 'image/jpeg'
    } else if (canEncodeWebp()) {
      outMime = 'image/webp'
    } else if (wantPng || hasAlphaSource(srcType)) {
      outMime = 'image/png'
    } else {
      outMime = 'image/jpeg'
    }
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

  // Fallback jika toBlob webp mengembalikan null di browser aneh
  let finalMime = outMime
  let finalBlob = blob
  if (!finalBlob && outMime === 'image/webp') {
    finalMime = hasAlphaSource(srcType) ? 'image/png' : 'image/jpeg'
    finalBlob = await new Promise((resolve) => {
      if (finalMime === 'image/png') canvas.toBlob(resolve, 'image/png')
      else {
        ctx.globalCompositeOperation = 'destination-over'
        ctx.fillStyle = '#ffffff'
        ctx.fillRect(0, 0, width, height)
        canvas.toBlob(resolve, 'image/jpeg', quality)
      }
    })
  }
  if (!finalBlob) throw new Error('Gagal kompres gambar')

  const base = String(file.name || 'img').replace(/\.\w+$/, '')
  const ext =
    finalMime === 'image/png' ? '.png' : finalMime === 'image/webp' ? '.webp' : '.jpg'
  return new File([finalBlob], base + ext, { type: finalMime })
}

export async function makeThumb(file, side = 400) {
  // Thumb galeri: tetap ringkas, prefer original format sumber
  const compressed = await compressImage(file, {
    maxSide: side,
    quality: 0.72,
    preferOriginal: true,
  })
  return new Promise((resolve, reject) => {
    const r = new FileReader()
    r.onload = () => resolve(r.result)
    r.onerror = reject
    r.readAsDataURL(compressed)
  })
}
