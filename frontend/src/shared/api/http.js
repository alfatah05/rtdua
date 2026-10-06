/**
 * Wrapper fetch ke /api. Cookie sesi ikut otomatis (same-origin).
 * Tidak dipanggil langsung dari halaman — lewat services/*.
 */
export async function api(path, options = {}) {
  const opts = {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
    ...options,
  }
  if (opts.body && typeof opts.body === 'object' && !(opts.body instanceof FormData)) {
    opts.body = JSON.stringify(opts.body)
  }

  const res = await fetch('/api' + path, opts)
  let json = null
  try {
    json = await res.json()
  } catch {
    json = { ok: false, error: 'Respons bukan JSON', data: null }
  }

  if (!res.ok || json?.ok === false) {
    const err = new Error(json?.error || json?.message || 'Permintaan gagal')
    err.status = res.status
    err.payload = json
    throw err
  }
  return json
}
