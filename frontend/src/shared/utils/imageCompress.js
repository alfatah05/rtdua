/** Kompres gambar di browser (canvas). maxSide default 1600. */
export async function compressImage(file, { maxSide = 1600, quality = 0.82, mime = 'image/jpeg' } = {}) {
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
  ctx.drawImage(bitmap, 0, 0, width, height)
  bitmap.close()
  const blob = await new Promise((resolve) => canvas.toBlob(resolve, mime, quality))
  if (!blob) throw new Error('Gagal kompres gambar')
  return new File([blob], (file.name || 'img').replace(/\.\w+$/, '') + '.jpg', { type: mime })
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
