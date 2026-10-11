/**
 * Tunggu URL gambar selesai load (atau gagal).
 * Skeleton boleh hilang setelah API + gambar siap.
 */
export function waitImages(urls, { timeoutMs = 8000 } = {}) {
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
          const img = new Image()
          img.onload = finish
          img.onerror = finish
          img.src = src
          if (img.complete) finish()
          setTimeout(finish, timeoutMs)
        })
    )
  )
}
