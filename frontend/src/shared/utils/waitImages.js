/**
 * Tunggu URL gambar selesai load (atau gagal/timeout).
 * Dipakai sebelum menutup spinner/skeleton agar banner & avatar tidak "kedip".
 */
export function waitImages(urls, { timeoutMs = 6000 } = {}) {
  const list = [...new Set((urls || []).filter((u) => typeof u === 'string' && u.trim()))]
  if (!list.length) return Promise.resolve()

  return Promise.all(
    list.map(
      (src) =>
        new Promise((resolve) => {
          let done = false
          const finish = () => {
            if (done) return
            done = true
            resolve()
          }
          try {
            const img = new Image()
            img.onload = finish
            img.onerror = finish
            img.src = src
            if (img.complete) finish()
          } catch {
            finish()
          }
          setTimeout(finish, timeoutMs)
        })
    )
  )
}

/** Ambil max N URL non-kosong (prioritas gambar above-the-fold). */
export function takeImageUrls(urls, max = 6) {
  return [...new Set((urls || []).filter((u) => typeof u === 'string' && u.trim()))].slice(0, max)
}
